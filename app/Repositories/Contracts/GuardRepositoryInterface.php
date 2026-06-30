<?php

namespace App\Repositories\Contracts;

use App\Models\Guard;

interface GuardRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUsername(string $username): ?Guard;

    public function findByEmployeeId(string $employeeId): ?Guard;

    public function countActive(): int;
}
