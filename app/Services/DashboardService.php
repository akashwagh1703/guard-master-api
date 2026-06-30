<?php

namespace App\Services;

use App\Enums\PayrollStatus;
use App\Models\AttendanceLog;
use App\Models\AuditLog;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Contracts\GuardAssignmentRepositoryInterface;
use App\Repositories\Contracts\GuardRepositoryInterface;
use App\Repositories\Contracts\IncidentRepositoryInterface;
use App\Repositories\Contracts\PayrollRepositoryInterface;
use App\Repositories\Contracts\SiteRepositoryInterface;
use App\Repositories\Contracts\VisitorRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        protected GuardRepositoryInterface $guardRepository,
        protected SiteRepositoryInterface $siteRepository,
        protected AttendanceRepositoryInterface $attendanceRepository,
        protected VisitorRepositoryInterface $visitorRepository,
        protected IncidentRepositoryInterface $incidentRepository,
        protected PayrollRepositoryInterface $payrollRepository,
        protected GuardAssignmentRepositoryInterface $assignmentRepository,
    ) {}

    public function getDashboardData(): array
    {
        $todayStats = $this->attendanceRepository->getTodayStats();
        $activeAssignments = $this->assignmentRepository->getActiveAssignments();

        return [
            'stats' => [
                'totalGuards' => $this->guardRepository->countActive(),
                'onDuty' => $todayStats['on_duty'],
                'checkedIn' => $todayStats['checked_in'],
                'absent' => $todayStats['absent'],
                'sites' => $this->siteRepository->countActive(),
                'todayVisitors' => $this->visitorRepository->countToday(),
                'incidentsToday' => $this->incidentRepository->countToday(),
                'payrollPending' => $this->payrollRepository->countPending(),
            ],
            'charts' => [
                'attendanceTrend' => $this->formatAttendanceTrend(),
                'lateArrivals' => $this->formatLateArrivals(),
                'sitePerformance' => $this->formatSitePerformance(),
            ],
            'recentActivity' => $this->getRecentActivity(),
            'shiftSummary' => $this->formatShiftSummary(),
            'recentVisitors' => $this->formatRecentVisitors(),
            'recentIncidents' => $this->formatRecentIncidents(),
        ];
    }

    protected function formatAttendanceTrend(): array
    {
        $data = $this->attendanceRepository->getTrendData(7);
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        return collect(range(0, 6))->map(function (int $offset) use ($data, $days) {
            $date = now()->subDays(6 - $offset)->toDateString();
            $row = $data->firstWhere('day', $date);

            return [
                'day' => $days[Carbon::parse($date)->dayOfWeekIso - 1] ?? Carbon::parse($date)->format('D'),
                'present' => (int) ($row->present ?? 0),
                'absent' => (int) ($row->absent ?? 0),
            ];
        })->values()->all();
    }

    protected function formatLateArrivals(): array
    {
        $data = $this->attendanceRepository->getLateArrivalsData(7);
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        return collect(range(0, 6))->map(function (int $offset) use ($data, $days) {
            $date = now()->subDays(6 - $offset)->toDateString();
            $row = $data->firstWhere('day', $date);

            return [
                'day' => $days[Carbon::parse($date)->dayOfWeekIso - 1] ?? Carbon::parse($date)->format('D'),
                'count' => (int) ($row->count ?? 0),
            ];
        })->values()->all();
    }

    protected function formatSitePerformance(): array
    {
        $startDate = now()->subDays(30)->toDateString();

        return DB::table('attendance')
            ->join('sites', 'attendance.site_id', '=', 'sites.id')
            ->where('attendance.date', '>=', $startDate)
            ->select(
                'sites.name as site',
                DB::raw("ROUND(SUM(CASE WHEN attendance.status IN ('present','late','on_duty','half_day') THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 0) as attendance")
            )
            ->groupBy('sites.id', 'sites.name')
            ->orderByDesc('attendance')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'site' => $row->site,
                'attendance' => (int) $row->attendance,
            ])
            ->all();
    }

    protected function formatShiftSummary(): array
    {
        return $this->attendanceRepository->getShiftSummary()
            ->map(fn ($row) => [
                'shift' => explode(' ', $row->shift)[0] ?? $row->shift,
                'assigned' => (int) $row->assigned,
                'present' => (int) $row->present,
                'absent' => (int) $row->absent,
            ])
            ->all();
    }

    protected function formatRecentVisitors(): array
    {
        return $this->visitorRepository->getRecent(5)
            ->map(fn ($visitor) => [
                'id' => $visitor->id,
                'name' => $visitor->visitor_name,
                'purpose' => $visitor->purpose,
                'personToMeet' => $visitor->person_to_meet,
                'guard' => $visitor->securityGuard?->name,
                'site' => $visitor->site?->name,
                'entryTime' => $visitor->entry_time?->format('H:i'),
                'exitTime' => $visitor->exit_time?->format('H:i') ?? '-',
                'status' => $visitor->status->value,
            ])
            ->all();
    }

    protected function formatRecentIncidents(): array
    {
        return $this->incidentRepository->getRecent(5)
            ->map(fn ($incident) => [
                'id' => $incident->id,
                'title' => $incident->title,
                'description' => $incident->description,
                'site' => $incident->site?->name,
                'guard' => $incident->securityGuard?->name,
                'date' => $incident->incident_time?->toDateString(),
                'priority' => $incident->priority->value,
                'status' => $incident->status->value,
            ])
            ->all();
    }

    protected function getRecentActivity(): array
    {
        $activities = collect();

        AttendanceLog::query()
            ->with('attendance.securityGuard', 'attendance.site')
            ->latest()
            ->limit(3)
            ->get()
            ->each(function ($log) use ($activities) {
                $activities->push([
                    'id' => 'attendance-' . $log->id,
                    'action' => ucfirst(str_replace('_', ' ', $log->action)),
                    'detail' => ($log->attendance?->securityGuard?->name ?? 'Guard') . ' at ' . ($log->attendance?->site?->name ?? 'site'),
                    'time' => $log->created_at?->diffForHumans(),
                    'type' => 'success',
                ]);
            });

        $this->incidentRepository->getRecent(2)->each(function ($incident) use ($activities) {
            $activities->push([
                'id' => 'incident-' . $incident->id,
                'action' => 'Incident reported',
                'detail' => $incident->title . ' at ' . ($incident->site?->name ?? 'site'),
                'time' => $incident->created_at?->diffForHumans(),
                'type' => 'danger',
            ]);
        });

        AuditLog::query()->latest()->limit(2)->get()->each(function ($log) use ($activities) {
            $activities->push([
                'id' => 'audit-' . $log->id,
                'action' => $log->action,
                'detail' => $log->model_type ? class_basename($log->model_type) . ' #' . $log->model_id : 'System activity',
                'time' => $log->created_at?->diffForHumans(),
                'type' => 'info',
            ]);
        });

        return $activities->sortByDesc('time')->take(5)->values()->all();
    }

    public function getDashboard(array $filters = []): array
    {
        return $this->getDashboardData();
    }
}
