<?php

namespace App\Repositories;

use App\Models\Holiday;
use App\Models\Setting;
use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SettingRepository extends BaseRepository implements SettingRepositoryInterface
{
    protected function model(): string
    {
        return Setting::class;
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['group'])) {
            $query->where('group', $filters['group']);
        }

        if (! empty($filters['key'])) {
            $query->where('key', $filters['key']);
        }

        return $query;
    }

    public function getByKey(string $key): ?Setting
    {
        return $this->query()->where('key', $key)->first();
    }

    public function getByGroup(string $group): Collection
    {
        return $this->query()->where('group', $group)->get();
    }

    public function setValue(string $group, string $key, mixed $value): Setting
    {
        $encoded = is_array($value) ? json_encode($value) : (string) $value;

        return $this->query()->updateOrCreate(
            ['key' => $key],
            ['group' => $group, 'value' => $encoded]
        );
    }

    public function getAllGrouped(): array
    {
        return $this->query()
            ->get()
            ->groupBy('group')
            ->map(fn (Collection $items) => $items->pluck('value', 'key')->toArray())
            ->toArray();
    }

    public function getHolidays(array $filters = []): Collection
    {
        $query = Holiday::query()->orderBy('date');

        if (! empty($filters['year'])) {
            $query->whereYear('date', $filters['year']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->get();
    }

    public function findHoliday(int $id): ?Holiday
    {
        return Holiday::find($id);
    }

    public function createHoliday(array $data): Holiday
    {
        return Holiday::create($data);
    }

    public function updateHoliday(int $id, array $data): Holiday
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->update($data);

        return $holiday->fresh();
    }

    public function deleteHoliday(int $id): bool
    {
        return (bool) Holiday::findOrFail($id)->delete();
    }
}
