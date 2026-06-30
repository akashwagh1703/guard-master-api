<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface LeaveRepositoryInterface extends BaseRepositoryInterface
{
    public function getPending(): Collection;

    public function getByGuard(int $guardId, array $filters = []): Collection;
}
