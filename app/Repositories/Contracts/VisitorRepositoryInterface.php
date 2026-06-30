<?php

namespace App\Repositories\Contracts;

use App\Models\VisitorEntry;

interface VisitorRepositoryInterface extends BaseRepositoryInterface
{
    public function countToday(): int;

    public function getRecent(int $limit = 5): \Illuminate\Support\Collection;
}
