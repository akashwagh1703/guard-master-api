<?php

namespace App\Repositories\Contracts;

use App\Models\GuardAssignment;
use Illuminate\Support\Collection;

interface GuardAssignmentRepositoryInterface extends BaseRepositoryInterface
{
    public function findActiveForGuard(int $guardId, ?string $date = null): ?GuardAssignment;

    public function hasOverlappingAssignment(
        int $guardId,
        int $siteId,
        int $shiftId,
        string $fromDate,
        string $toDate,
        ?int $excludeId = null
    ): bool;

    public function getActiveAssignments(?string $date = null): Collection;
}
