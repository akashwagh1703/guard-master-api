<?php

namespace App\Repositories\Contracts;

use App\Models\Holiday;
use App\Models\Setting;
use Illuminate\Support\Collection;

interface SettingRepositoryInterface extends BaseRepositoryInterface
{
    public function getByKey(string $key): ?Setting;

    public function getByGroup(string $group): Collection;

    public function setValue(string $group, string $key, mixed $value): Setting;

    public function getAllGrouped(): array;

    public function getHolidays(array $filters = []): Collection;

    public function findHoliday(int $id): ?Holiday;

    public function createHoliday(array $data): Holiday;

    public function updateHoliday(int $id, array $data): Holiday;

    public function deleteHoliday(int $id): bool;
}
