<?php

namespace App\Repositories;

use App\Enums\PayrollStatus;
use App\Models\Payroll;
use App\Repositories\Contracts\PayrollRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PayrollRepository extends BaseRepository implements PayrollRepositoryInterface
{
    protected function model(): string
    {
        return Payroll::class;
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['guard_id'])) {
            $query->where('guard_id', $filters['guard_id']);
        }

        if (! empty($filters['month'])) {
            $query->where('month', $filters['month']);
        }

        if (! empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('securityGuard', function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        return $query->with(['securityGuard', 'items'])->orderByDesc('year')->orderByDesc('month');
    }

    public function findByGuardMonthYear(int $securityGuardId, int $month, int $year): ?Payroll
    {
        return $this->query()
            ->where('guard_id', $securityGuardId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();
    }

    public function countPending(): int
    {
        return $this->query()->where('status', PayrollStatus::Pending)->count();
    }

    public function getByMonthYear(int $month, int $year, array $filters = []): Collection
    {
        $filters['month'] = $month;
        $filters['year'] = $year;

        return $this->applyFilters($this->query(), $filters)->get();
    }
}
