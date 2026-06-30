<?php

namespace App\Repositories;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceRepository extends BaseRepository implements AttendanceRepositoryInterface
{
    protected function model(): string
    {
        return Attendance::class;
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['guard_id'])) {
            $query->where('guard_id', $filters['guard_id']);
        }

        if (! empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (! empty($filters['shift_id'])) {
            $query->where('shift_id', $filters['shift_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date'])) {
            $query->whereDate('date', $filters['date']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('date', '<=', $filters['to_date']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('securityGuard', function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        return $query->with(['securityGuard', 'site', 'shift']);
    }

    public function findByGuardAndDate(int $securityGuardId, string $date): ?Attendance
    {
        return $this->query()
            ->where('guard_id', $securityGuardId)
            ->whereDate('date', $date)
            ->first();
    }

    public function updateOrCreate(int $securityGuardId, string $date, array $data): Attendance
    {
        return $this->query()->updateOrCreate(
            ['guard_id' => $securityGuardId, 'date' => $date],
            $data
        );
    }

    public function getTodayStats(): array
    {
        $today = now()->toDateString();

        $stats = $this->query()
            ->whereDate('date', $today)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $checkedIn = $this->query()
            ->whereDate('date', $today)
            ->whereNotNull('check_in_time')
            ->count();

        return [
            'present' => (int) ($stats[AttendanceStatus::Present->value] ?? 0)
                + (int) ($stats[AttendanceStatus::Late->value] ?? 0)
                + (int) ($stats[AttendanceStatus::OnDuty->value] ?? 0),
            'absent' => (int) ($stats[AttendanceStatus::Absent->value] ?? 0),
            'late' => (int) ($stats[AttendanceStatus::Late->value] ?? 0),
            'on_duty' => (int) ($stats[AttendanceStatus::OnDuty->value] ?? 0)
                + $this->query()
                    ->whereDate('date', $today)
                    ->whereNotNull('check_in_time')
                    ->whereNull('check_out_time')
                    ->count(),
            'checked_in' => $checkedIn,
        ];
    }

    public function getTrendData(int $days = 7): Collection
    {
        $startDate = now()->subDays($days - 1)->startOfDay();

        return $this->query()
            ->where('date', '>=', $startDate->toDateString())
            ->select(
                DB::raw('DATE(date) as day'),
                DB::raw("SUM(CASE WHEN status IN ('present','late','on_duty','half_day') THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent")
            )
            ->groupBy('day')
            ->orderBy('day')
            ->get();
    }

    public function getLateArrivalsData(int $days = 7): Collection
    {
        $startDate = now()->subDays($days - 1)->startOfDay();

        return $this->query()
            ->where('date', '>=', $startDate->toDateString())
            ->where('late_minutes', '>', 0)
            ->select(DB::raw('DATE(date) as day'), DB::raw('COUNT(*) as count'))
            ->groupBy('day')
            ->orderBy('day')
            ->get();
    }

    public function getForExport(array $filters = []): Collection
    {
        return $this->applyFilters($this->query(), $filters)
            ->orderBy('date', 'desc')
            ->get();
    }

    public function getShiftSummary(?string $date = null): Collection
    {
        $date = $date ?? now()->toDateString();

        return $this->query()
            ->whereDate('date', $date)
            ->join('shifts', 'attendance.shift_id', '=', 'shifts.id')
            ->select(
                'shifts.name as shift',
                DB::raw('COUNT(*) as assigned'),
                DB::raw("SUM(CASE WHEN attendance.status IN ('present','late','on_duty') THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN attendance.status = 'absent' THEN 1 ELSE 0 END) as absent")
            )
            ->groupBy('shifts.id', 'shifts.name')
            ->get();
    }
}
