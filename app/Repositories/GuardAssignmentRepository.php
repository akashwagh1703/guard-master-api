<?php

namespace App\Repositories;

use App\Enums\RecordStatus;
use App\Models\GuardAssignment;
use App\Repositories\Contracts\GuardAssignmentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GuardAssignmentRepository extends BaseRepository implements GuardAssignmentRepositoryInterface
{
    protected function model(): string
    {
        return GuardAssignment::class;
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['guard_id'])) {
            $query->where('guard_id', $filters['guard_id']);
        }

        if (! empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (! empty($filters['shift_id'])) {
            $query->where('shift_id', $filters['shift_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->with(['securityGuard', 'site', 'shift']);
    }

    public function findActiveForGuard(int $securityGuardId, ?string $date = null): ?GuardAssignment
    {
        $date = $date ?? now()->toDateString();

        return $this->query()
            ->where('guard_id', $securityGuardId)
            ->where('status', RecordStatus::Active)
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->with(['site', 'shift'])
            ->first();
    }

    public function hasOverlappingAssignment(
        int $securityGuardId,
        int $siteId,
        int $shiftId,
        string $fromDate,
        string $toDate,
        ?int $excludeId = null
    ): bool {
        $query = $this->query()
            ->where('guard_id', $securityGuardId)
            ->where('site_id', $siteId)
            ->where('shift_id', $shiftId)
            ->where('status', RecordStatus::Active)
            ->where(function (Builder $q) use ($fromDate, $toDate) {
                $q->whereBetween('from_date', [$fromDate, $toDate])
                    ->orWhereBetween('to_date', [$fromDate, $toDate])
                    ->orWhere(function (Builder $inner) use ($fromDate, $toDate) {
                        $inner->where('from_date', '<=', $fromDate)
                            ->where('to_date', '>=', $toDate);
                    });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function getActiveAssignments(?string $date = null): Collection
    {
        $date = $date ?? now()->toDateString();

        return $this->query()
            ->where('status', RecordStatus::Active)
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->with(['securityGuard', 'site', 'shift'])
            ->get();
    }
}
