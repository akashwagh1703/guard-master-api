<?php

namespace App\Repositories\Contracts;

use App\Models\Attendance;
use Illuminate\Support\Collection;

interface AttendanceRepositoryInterface extends BaseRepositoryInterface
{
    public function findByGuardAndDate(int $guardId, string $date): ?Attendance;

    public function updateOrCreate(int $guardId, string $date, array $data): Attendance;

    public function getTodayStats(): array;

    public function getTrendData(int $days = 7): Collection;

    public function getLateArrivalsData(int $days = 7): Collection;

    public function getForExport(array $filters = []): Collection;

    public function getShiftSummary(?string $date = null): Collection;
}
