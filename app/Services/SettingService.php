<?php

namespace App\Services;

use App\Models\Holiday;
use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    protected const CACHE_KEY = 'app_settings_all';

    protected const CACHE_TTL = 3600;

    public function __construct(
        protected SettingRepositoryInterface $settingRepository
    ) {}

    public function getAll(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->settingRepository->getAllGrouped();
        });
    }

    public function getGroup(string $group): array
    {
        $all = $this->getAll();

        return $all[$group] ?? [];
    }

    public function updateCompanyProfile(array $data): array
    {
        foreach ($data as $key => $value) {
            $this->settingRepository->setValue('company', $key, $value);
        }

        $this->clearCache();

        return $this->getGroup('company');
    }

    public function updateAttendanceRules(array $data): array
    {
        foreach ($data as $key => $value) {
            $this->settingRepository->setValue('attendance', $key, $value);
        }

        $this->clearCache();

        return $this->getGroup('attendance');
    }

    public function updatePayrollRules(array $data): array
    {
        foreach ($data as $key => $value) {
            $this->settingRepository->setValue('payroll', $key, $value);
        }

        $this->clearCache();

        return $this->getGroup('payroll');
    }

    public function updateNotificationSettings(array $data): array
    {
        foreach ($data as $key => $value) {
            $this->settingRepository->setValue('notifications', $key, $value);
        }

        $this->clearCache();

        return $this->getGroup('notifications');
    }

    public function updateAppSettings(array $data): array
    {
        foreach ($data as $key => $value) {
            $this->settingRepository->setValue('app', $key, $value);
        }

        $this->clearCache();

        return $this->getGroup('app');
    }

    public function listHolidays(array $filters = []): Collection
    {
        return $this->settingRepository->getHolidays($filters);
    }

    public function getHoliday(int $id): Holiday
    {
        return $this->settingRepository->findHoliday($id)
            ?? throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
    }

    public function createHoliday(array $data): Holiday
    {
        $holiday = $this->settingRepository->createHoliday($data);
        $this->clearCache();

        return $holiday;
    }

    public function updateHoliday(int $id, array $data): Holiday
    {
        $holiday = $this->settingRepository->updateHoliday($id, $data);
        $this->clearCache();

        return $holiday;
    }

    public function deleteHoliday(int $id): bool
    {
        $deleted = $this->settingRepository->deleteHoliday($id);
        $this->clearCache();

        return $deleted;
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function update(array $data): array
    {
        if (isset($data['company'])) {
            $this->updateCompanyProfile($data['company']);
        }
        if (isset($data['attendance'])) {
            $this->updateAttendanceRules($data['attendance']);
        }
        if (isset($data['payroll'])) {
            $this->updatePayrollRules($data['payroll']);
        }
        if (isset($data['notifications'])) {
            $this->updateNotificationSettings($data['notifications']);
        }
        if (isset($data['app'])) {
            $this->updateAppSettings($data['app']);
        }

        return $this->getAll();
    }

    public function getHolidays(array $filters = []): Collection
    {
        return $this->listHolidays($filters);
    }
}
