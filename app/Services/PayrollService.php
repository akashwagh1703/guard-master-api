<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\PayrollStatus;
use App\Enums\RecordStatus;
use App\Models\Attendance;
use App\Models\Guard;
use App\Models\Payroll;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Contracts\GuardRepositoryInterface;
use App\Repositories\Contracts\PayrollRepositoryInterface;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Services\Concerns\HasCrudAliases;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    use HasCrudAliases;

    public function __construct(
        protected PayrollRepositoryInterface $payrollRepository,
        protected GuardRepositoryInterface $guardRepository,
        protected AttendanceRepositoryInterface $attendanceRepository,
        protected SettingRepositoryInterface $settingRepository,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $this->payrollRepository->paginate($filters, $perPage);
    }

    public function get(int $id): Payroll
    {
        return $this->payrollRepository->findOrFail($id);
    }

    public function generateMonthly(int $month, int $year, ?int $guardId = null): array
    {
        $guards = $guardId
            ? collect([$this->guardRepository->findOrFail($guardId)])
            : $this->guardRepository->paginate(['status' => RecordStatus::Active->value], 1000)->getCollection();

        $generated = [];

        foreach ($guards as $guard) {
            $existing = $this->payrollRepository->findByGuardMonthYear($guard->id, $month, $year);

            if ($existing) {
                continue;
            }

            $generated[] = $this->generateForGuard($guard, $month, $year);
        }

        return $generated;
    }

    public function generateForGuard(Guard $guard, int $month, int $year): Payroll
    {
        $attendance = Attendance::query()
            ->where('guard_id', $guard->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $presentDays = $attendance->whereIn('status', [
            AttendanceStatus::Present,
            AttendanceStatus::Late,
            AttendanceStatus::OnDuty,
        ])->count();

        $absentDays = $attendance->where('status', AttendanceStatus::Absent)->count();
        $halfDays = $attendance->where('status', AttendanceStatus::HalfDay)->count();
        $lateCount = $attendance->where('status', AttendanceStatus::Late)->count();
        $totalOvertimeHours = $attendance->sum('overtime_hours');

        $rules = $this->getPayrollRules();
        $workingDays = (int) ($rules['working_days_per_month'] ?? 26);
        $baseSalary = (float) $guard->salary;
        $dailyRate = $workingDays > 0 ? $baseSalary / $workingDays : 0;

        $earnedSalary = ($presentDays * $dailyRate) + ($halfDays * $dailyRate * 0.5);
        $overtimeAmount = $totalOvertimeHours * (float) $guard->overtime_rate;
        $bonus = 0;
        $advance = 0;
        $deduction = $absentDays * $dailyRate;

        $netSalary = $this->calculateNetSalary(
            $earnedSalary,
            $overtimeAmount,
            $bonus,
            $advance,
            $deduction
        );

        return DB::transaction(function () use (
            $guard, $month, $year, $baseSalary, $presentDays, $absentDays,
            $halfDays, $lateCount, $overtimeAmount, $bonus, $advance, $deduction, $netSalary
        ) {
            $payroll = $this->payrollRepository->create([
                'guard_id' => $guard->id,
                'month' => $month,
                'year' => $year,
                'base_salary' => $baseSalary,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'half_days' => $halfDays,
                'late_count' => $lateCount,
                'overtime_amount' => round($overtimeAmount, 2),
                'bonus' => $bonus,
                'advance' => $advance,
                'deduction' => round($deduction, 2),
                'net_salary' => round($netSalary, 2),
                'status' => PayrollStatus::Pending,
                'generated_at' => now(),
                'created_by' => Auth::id(),
            ]);

            $payroll->items()->createMany([
                ['type' => 'earning', 'description' => 'Base salary (earned)', 'amount' => round($baseSalary - $deduction, 2)],
                ['type' => 'earning', 'description' => 'Overtime', 'amount' => round($overtimeAmount, 2)],
                ['type' => 'deduction', 'description' => 'Absences', 'amount' => round($deduction, 2)],
            ]);

            return $payroll->fresh(['securityGuard', 'items']);
        });
    }

    public function calculateNetSalary(
        float $earnedSalary,
        float $overtimeAmount,
        float $bonus,
        float $advance,
        float $deduction
    ): float {
        return $earnedSalary + $overtimeAmount + $bonus - $advance - $deduction;
    }

    public function update(int $id, array $data): Payroll
    {
        $payroll = $this->payrollRepository->findOrFail($id);

        $bonus = (float) ($data['bonus'] ?? $payroll->bonus);
        $advance = (float) ($data['advance'] ?? $payroll->advance);
        $deduction = (float) ($data['deduction'] ?? $payroll->deduction);

        $netSalary = $this->calculateNetSalary(
            (float) $payroll->base_salary - (float) $payroll->deduction,
            (float) $payroll->overtime_amount,
            $bonus,
            $advance,
            $deduction
        );

        return $this->payrollRepository->update($id, array_filter([
            'bonus' => $data['bonus'] ?? null,
            'advance' => $data['advance'] ?? null,
            'deduction' => $data['deduction'] ?? null,
            'net_salary' => round($netSalary, 2),
            'status' => $data['status'] ?? null,
        ], fn ($value) => $value !== null));
    }

    public function generatePdf(int $id): \Barryvdh\DomPDF\PDF
    {
        $payroll = $this->payrollRepository->findOrFail($id);
        $payroll->load(['securityGuard', 'items']);

        $company = $this->settingRepository->getByGroup('company')
            ->pluck('value', 'key')
            ->toArray();

        return Pdf::loadView('payroll.slip', [
            'payroll' => $payroll,
            'company' => $company,
        ]);
    }

    protected function getPayrollRules(): array
    {
        return $this->settingRepository->getByGroup('payroll')
            ->pluck('value', 'key')
            ->map(function ($value) {
                $decoded = json_decode($value, true);

                return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
            })
            ->toArray();
    }

    public function generate(array $data): array
    {
        return $this->generateMonthly(
            (int) $data['month'],
            (int) $data['year'],
            isset($data['guard_id']) ? (int) $data['guard_id'] : null
        );
    }

    public function getPayslip(int $id): \Symfony\Component\HttpFoundation\Response
    {
        return $this->generatePdf($id)->download('payslip-'.$id.'.pdf');
    }

    public function getMyPayroll(User $user, array $filters = []): LengthAwarePaginator
    {
        $filters['guard_id'] = $user->guard_id;

        return $this->list($filters);
    }

    public function getMyPayslip(User $user, int $id): \Symfony\Component\HttpFoundation\Response
    {
        $payroll = $this->get($id);

        if ($payroll->guard_id !== $user->guard_id) {
            abort(403, 'Unauthorized access to payslip.');
        }

        return $this->generatePdf($id)->download('payslip-'.$id.'.pdf');
    }
}
