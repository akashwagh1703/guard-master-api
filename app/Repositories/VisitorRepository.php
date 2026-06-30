<?php

namespace App\Repositories;

use App\Models\VisitorEntry;
use App\Repositories\Contracts\VisitorRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class VisitorRepository extends BaseRepository implements VisitorRepositoryInterface
{
    protected function model(): string
    {
        return VisitorEntry::class;
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('visitor_name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhere('person_to_meet', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (! empty($filters['guard_id'])) {
            $query->where('guard_id', $filters['guard_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('entry_time', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('entry_time', '<=', $filters['to_date']);
        }

        return $query->with(['site', 'securityGuard'])->orderByDesc('entry_time');
    }

    public function countToday(): int
    {
        return $this->query()->whereDate('entry_time', now()->toDateString())->count();
    }

    public function getRecent(int $limit = 5): \Illuminate\Support\Collection
    {
        return $this->query()
            ->with(['site', 'securityGuard'])
            ->orderByDesc('entry_time')
            ->limit($limit)
            ->get();
    }
}
