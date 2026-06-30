<?php

namespace App\Services;

use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Contracts\GuardRepositoryInterface;
use App\Repositories\Contracts\IncidentRepositoryInterface;
use App\Repositories\Contracts\PayrollRepositoryInterface;
use App\Repositories\Contracts\SiteRepositoryInterface;
use App\Repositories\Contracts\VisitorRepositoryInterface;

class ReportService
{
    public function __construct(
        protected AttendanceRepositoryInterface $attendanceRepository,
        protected PayrollRepositoryInterface $payrollRepository,
        protected GuardRepositoryInterface $guardRepository,
        protected SiteRepositoryInterface $siteRepository,
        protected VisitorRepositoryInterface $visitorRepository,
        protected IncidentRepositoryInterface $incidentRepository,
    ) {}

    public function attendanceReport(array $filters = []): array
    {
        $records = $this->attendanceRepository->getForExport($filters);

        return [
            'title' => 'Attendance Report',
            'filters' => $filters,
            'total' => $records->count(),
            'data' => $records,
        ];
    }

    public function payrollReport(array $filters = []): array
    {
        $records = $this->payrollRepository->paginate($filters, 1000);

        return [
            'title' => 'Payroll Report',
            'filters' => $filters,
            'total' => $records->total(),
            'data' => $records->items(),
        ];
    }

    public function guardReport(array $filters = []): array
    {
        $records = $this->guardRepository->paginate($filters, 1000);

        return [
            'title' => 'Guard Report',
            'filters' => $filters,
            'total' => $records->total(),
            'data' => $records->items(),
        ];
    }

    public function siteReport(array $filters = []): array
    {
        $records = $this->siteRepository->paginate($filters, 1000);

        return [
            'title' => 'Site Report',
            'filters' => $filters,
            'total' => $records->total(),
            'data' => $records->items(),
        ];
    }

    public function visitorReport(array $filters = []): array
    {
        $records = $this->visitorRepository->paginate($filters, 1000);

        return [
            'title' => 'Visitor Report',
            'filters' => $filters,
            'total' => $records->total(),
            'data' => $records->items(),
        ];
    }

    public function incidentReport(array $filters = []): array
    {
        $records = $this->incidentRepository->paginate($filters, 1000);

        return [
            'title' => 'Incident Report',
            'filters' => $filters,
            'total' => $records->total(),
            'data' => $records->items(),
        ];
    }

    public function exportCsv(string $type, array $filters = []): string
    {
        $report = match ($type) {
            'attendance' => $this->attendanceReport($filters),
            'payroll' => $this->payrollReport($filters),
            'guard' => $this->guardReport($filters),
            'site' => $this->siteReport($filters),
            'visitor' => $this->visitorReport($filters),
            'incident' => $this->incidentReport($filters),
            default => ['data' => []],
        };

        $lines = [$report['title'] ?? 'Report'];

        foreach ($report['data'] as $row) {
            if (is_object($row) && method_exists($row, 'toArray')) {
                $lines[] = implode(',', array_map(
                    fn ($v) => is_scalar($v) ? $v : json_encode($v),
                    $row->toArray()
                ));
            }
        }

        return implode("\n", $lines);
    }

    public function generate(string $type, array $filters = []): array
    {
        return match ($type) {
            'attendance' => $this->attendanceReport($filters),
            'payroll' => $this->payrollReport($filters),
            'guard' => $this->guardReport($filters),
            'site' => $this->siteReport($filters),
            'visitor' => $this->visitorReport($filters),
            'incident' => $this->incidentReport($filters),
            default => throw new \InvalidArgumentException('Invalid report type.'),
        };
    }

    public function export(string $type, string $format, array $filters = []): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if ($format !== 'csv') {
            abort(400, 'Only CSV export is supported currently.');
        }

        $csv = $this->exportCsv($type, $filters);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, "{$type}-report.csv", ['Content-Type' => 'text/csv']);
    }
}
