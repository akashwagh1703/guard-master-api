<?php

namespace App\Repositories;

use App\Models\Incident;
use App\Repositories\Contracts\IncidentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class IncidentRepository extends BaseRepository implements IncidentRepositoryInterface
{
    protected function model(): string
    {
        return Incident::class;
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (! empty($filters['guard_id'])) {
            $query->where('guard_id', $filters['guard_id']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('incident_time', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('incident_time', '<=', $filters['to_date']);
        }

        return $query->with(['site', 'securityGuard'])->orderByDesc('incident_time');
    }

    public function countToday(): int
    {
        return $this->query()->whereDate('incident_time', now()->toDateString())->count();
    }

    public function getRecent(int $limit = 5): \Illuminate\Support\Collection
    {
        return $this->query()
            ->with(['site', 'securityGuard'])
            ->orderByDesc('incident_time')
            ->limit($limit)
            ->get();
    }
}
