<?php

namespace App\Repositories\Contracts;

use App\Models\Payroll;
use Illuminate\Support\Collection;

interface PayrollRepositoryInterface extends BaseRepositoryInterface
{
    public function findByGuardMonthYear(int $guardId, int $month, int $year): ?Payroll;

    public function countPending(): int;

    public function getByMonthYear(int $month, int $year, array $filters = []): Collection;
}
