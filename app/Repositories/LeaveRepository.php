<?php

namespace App\Repositories;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Repositories\Contracts\LeaveRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LeaveRepository extends BaseRepository implements LeaveRepositoryInterface
{
    protected function model(): string
    {
        return LeaveRequest::class;
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['guard_id'])) {
            $query->where('guard_id', $filters['guard_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('from_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('to_date', '<=', $filters['to_date']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('securityGuard', function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        return $query->with(['securityGuard', 'approver'])->orderByDesc('created_at');
    }

    public function getPending(): Collection
    {
        return $this->query()
            ->where('status', LeaveStatus::Pending)
            ->with('securityGuard')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getByGuard(int $securityGuardId, array $filters = []): Collection
    {
        $filters['guard_id'] = $securityGuardId;

        return $this->applyFilters($this->query(), $filters)->get();
    }
}
