<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Helpers\GeoHelper;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\User;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Contracts\GuardAssignmentRepositoryInterface;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Services\Concerns\HasCrudAliases;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    use HasCrudAliases;

    public function __construct(
        protected AttendanceRepositoryInterface $attendanceRepository,
        protected GuardAssignmentRepositoryInterface $assignmentRepository,
        protected SettingRepositoryInterface $settingRepository,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $this->attendanceRepository->paginate($filters, $perPage);
    }

    public function get(int $id): Attendance
    {
        return $this->attendanceRepository->findOrFail($id);
    }

    public function checkInForGuard(int $guardId, array $data, ?UploadedFile $photo = null): Attendance
    {
        $today = now()->toDateString();

        if ($this->attendanceRepository->findByGuardAndDate($guardId, $today)?->check_in_time) {
            throw ValidationException::withMessages([
                'check_in' => ['Already checked in for today.'],
            ]);
        }

        $assignment = $this->assignmentRepository->findActiveForGuard($guardId, $today);

        if (! $assignment) {
            throw ValidationException::withMessages([
                'assignment' => ['No active assignment found for today.'],
            ]);
        }

        $site = $assignment->site;

        if (! ($data['admin_override'] ?? false)) {
            $this->validateGps(
                (float) $data['latitude'],
                (float) $data['longitude'],
                (float) $site->latitude,
                (float) $site->longitude,
                (float) $site->attendance_radius
            );
        }

        $checkInTime = now()->format('H:i:s');
        $shift = $assignment->shift;
        $lateMinutes = $this->calculateLateMinutes($checkInTime, $shift);
        $status = $this->determineCheckInStatus($lateMinutes, $shift);

        $photoPath = $photo ? $photo->store('attendance/check-in', 'public') : null;

        $attendance = $this->attendanceRepository->updateOrCreate($guardId, $today, [
            'guard_id' => $guardId,
            'site_id' => $assignment->site_id,
            'shift_id' => $assignment->shift_id,
            'assignment_id' => $assignment->id,
            'date' => $today,
            'check_in_time' => $checkInTime,
            'check_in_latitude' => $data['latitude'],
            'check_in_longitude' => $data['longitude'],
            'check_in_photo' => $photoPath,
            'late_minutes' => $lateMinutes,
            'status' => $status,
            'admin_override' => $data['admin_override'] ?? false,
        ]);

        $this->logAction($attendance, 'check_in', null, $attendance->toArray());

        return $attendance->fresh(['securityGuard', 'site', 'shift']);
    }

    public function checkOutForGuard(int $guardId, array $data, ?UploadedFile $photo = null): Attendance
    {
        $today = now()->toDateString();
        $attendance = $this->attendanceRepository->findByGuardAndDate($guardId, $today);

        if (! $attendance || ! $attendance->check_in_time) {
            throw ValidationException::withMessages([
                'check_out' => ['Must check in before checking out.'],
            ]);
        }

        if ($attendance->check_out_time) {
            throw ValidationException::withMessages([
                'check_out' => ['Already checked out for today.'],
            ]);
        }

        $site = $attendance->site;

        if (! ($data['admin_override'] ?? false)) {
            $this->validateGps(
                (float) $data['latitude'],
                (float) $data['longitude'],
                (float) $site->latitude,
                (float) $site->longitude,
                (float) $site->attendance_radius
            );
        }

        $checkOutTime = now()->format('H:i:s');
        $workingHours = $this->calculateWorkingHours($attendance->check_in_time, $checkOutTime);
        $overtimeHours = $this->calculateOvertimeHours($workingHours, $attendance->shift);

        $photoPath = $photo ? $photo->store('attendance/check-out', 'public') : null;

        $oldValues = $attendance->toArray();

        $attendance = $this->attendanceRepository->update($attendance->id, [
            'check_out_time' => $checkOutTime,
            'check_out_latitude' => $data['latitude'],
            'check_out_longitude' => $data['longitude'],
            'check_out_photo' => $photoPath,
            'working_hours' => $workingHours,
            'overtime_hours' => $overtimeHours,
            'status' => $this->determineFinalStatus($attendance, $workingHours),
            'admin_override' => $data['admin_override'] ?? $attendance->admin_override,
        ]);

        $this->logAction($attendance, 'check_out', $oldValues, $attendance->toArray());

        return $attendance->fresh(['securityGuard', 'site', 'shift']);
    }

    public function correct(int $id, array $data): Attendance
    {
        $attendance = $this->attendanceRepository->findOrFail($id);
        $oldValues = $attendance->toArray();

        $updateData = array_filter([
            'check_in_time' => $data['check_in_time'] ?? null,
            'check_out_time' => $data['check_out_time'] ?? null,
            'status' => $data['status'] ?? null,
            'late_minutes' => $data['late_minutes'] ?? null,
            'working_hours' => $data['working_hours'] ?? null,
            'overtime_hours' => $data['overtime_hours'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'admin_override' => true,
        ], fn ($value) => $value !== null);

        if (isset($updateData['check_in_time'], $updateData['check_out_time'])) {
            $updateData['working_hours'] = $this->calculateWorkingHours(
                $updateData['check_in_time'],
                $updateData['check_out_time']
            );
        }

        $attendance = $this->attendanceRepository->update($id, $updateData);

        $this->logAction($attendance, 'correction', $oldValues, $attendance->toArray());

        return $attendance->fresh(['securityGuard', 'site', 'shift']);
    }

    public function exportList(array $filters = []): Collection
    {
        return $this->attendanceRepository->getForExport($filters);
    }

    public function exportCsv(array $filters = []): string
    {
        $records = $this->exportList($filters);
        $lines = ['Date,Guard,Site,Shift,Check In,Check Out,Hours,Late,Overtime,Status'];

        foreach ($records as $row) {
            $lines[] = implode(',', [
                $row->date?->toDateString(),
                '"' . ($row->securityGuard?->name ?? '') . '"',
                '"' . ($row->site?->name ?? '') . '"',
                '"' . ($row->shift?->name ?? '') . '"',
                $row->check_in_time ?? '',
                $row->check_out_time ?? '',
                $row->working_hours ?? '',
                $row->late_minutes ?? 0,
                $row->overtime_hours ?? 0,
                $row->status?->value ?? '',
            ]);
        }

        return implode("\n", $lines);
    }

    public function export(array $filters = []): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $csv = $this->exportCsv($filters);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'attendance-export.csv', ['Content-Type' => 'text/csv']);
    }

    public function checkIn(User $user, array $data): Attendance
    {
        $guardId = $user->guard_id ?? throw ValidationException::withMessages([
            'guard' => ['Guard profile not linked to this account.'],
        ]);

        return $this->checkInForGuard($guardId, $data, $data['photo'] ?? null);
    }

    public function checkOut(User $user, array $data): Attendance
    {
        $guardId = $user->guard_id ?? throw ValidationException::withMessages([
            'guard' => ['Guard profile not linked to this account.'],
        ]);

        return $this->checkOutForGuard($guardId, $data, $data['photo'] ?? null);
    }

    public function getMyAttendance(User $user, array $filters = []): LengthAwarePaginator
    {
        $filters['guard_id'] = $user->guard_id;

        return $this->list($filters);
    }

    protected function validateGps(
        float $lat,
        float $lng,
        float $siteLat,
        float $siteLng,
        float $radius
    ): void {
        if (! GeoHelper::isWithinRadius($lat, $lng, $siteLat, $siteLng, $radius)) {
            throw ValidationException::withMessages([
                'location' => ['You are outside the allowed attendance radius.'],
            ]);
        }
    }

    protected function calculateLateMinutes(string $checkInTime, $shift): int
    {
        if (! $shift) {
            return 0;
        }

        $shiftStart = Carbon::parse($shift->start_time)->format('H:i:s');
        $graceMinutes = (int) ($shift->grace_minutes ?? 0);
        $lateAfter = (int) ($shift->late_after ?? 0);

        $allowedTime = Carbon::parse($shiftStart)->addMinutes($graceMinutes + $lateAfter);
        $actualTime = Carbon::parse($checkInTime);

        if ($actualTime->lte($allowedTime)) {
            return 0;
        }

        return (int) $allowedTime->diffInMinutes($actualTime);
    }

    protected function determineCheckInStatus(int $lateMinutes, $shift): AttendanceStatus
    {
        return $lateMinutes > 0 ? AttendanceStatus::Late : AttendanceStatus::Present;
    }

    protected function calculateWorkingHours(string $checkIn, string $checkOut): float
    {
        $start = Carbon::parse($checkIn);
        $end = Carbon::parse($checkOut);

        if ($end->lt($start)) {
            $end->addDay();
        }

        return round($start->diffInMinutes($end) / 60, 2);
    }

    protected function calculateOvertimeHours(float $workingHours, $shift): float
    {
        $rules = $this->getAttendanceRules();
        $standardHours = (float) ($rules['standard_hours'] ?? 8);

        if ($shift) {
            $start = Carbon::parse($shift->start_time);
            $end = Carbon::parse($shift->end_time);
            if ($end->lte($start)) {
                $end->addDay();
            }
            $standardHours = max($standardHours, round($start->diffInMinutes($end) / 60, 2));
        }

        return max(0, round($workingHours - $standardHours, 2));
    }

    protected function determineFinalStatus(Attendance $attendance, float $workingHours): AttendanceStatus
    {
        $rules = $this->getAttendanceRules();
        $halfDayHours = (float) ($rules['half_day_hours'] ?? 4);

        if ($workingHours < $halfDayHours) {
            return AttendanceStatus::HalfDay;
        }

        if ($attendance->late_minutes > 0) {
            return AttendanceStatus::Late;
        }

        return AttendanceStatus::Present;
    }

    protected function getAttendanceRules(): array
    {
        $settings = $this->settingRepository->getByGroup('attendance');

        return $settings->pluck('value', 'key')->map(function ($value) {
            $decoded = json_decode($value, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        })->toArray();
    }

    protected function logAction(Attendance $attendance, string $action, ?array $old, ?array $new): void
    {
        AttendanceLog::create([
            'attendance_id' => $attendance->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
