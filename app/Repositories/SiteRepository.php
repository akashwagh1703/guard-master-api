<?php

namespace App\Repositories;

use App\Enums\RecordStatus;
use App\Models\Site;
use App\Repositories\Contracts\SiteRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class SiteRepository extends BaseRepository implements SiteRepositoryInterface
{
    protected function model(): string
    {
        return Site::class;
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    public function countActive(): int
    {
        return $this->query()->where('status', RecordStatus::Active)->count();
    }
}
