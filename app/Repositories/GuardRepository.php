<?php

namespace App\Repositories;

use App\Enums\RecordStatus;
use App\Models\Guard;
use App\Repositories\Contracts\GuardRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class GuardRepository extends BaseRepository implements GuardRepositoryInterface
{
    protected function model(): string
    {
        return Guard::class;
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->with('user');
    }

    public function findByUsername(string $username): ?Guard
    {
        return $this->query()->where('username', $username)->first();
    }

    public function findByEmployeeId(string $employeeId): ?Guard
    {
        return $this->query()->where('employee_id', $employeeId)->first();
    }

    public function countActive(): int
    {
        return $this->query()->where('status', RecordStatus::Active)->count();
    }
}
