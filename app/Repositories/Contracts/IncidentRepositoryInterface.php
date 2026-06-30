<?php

namespace App\Repositories\Contracts;

interface IncidentRepositoryInterface extends BaseRepositoryInterface
{
    public function countToday(): int;

    public function getRecent(int $limit = 5): \Illuminate\Support\Collection;
}
