<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Models\GuardAssignment;
use App\Repositories\Contracts\GuardAssignmentRepositoryInterface;
use App\Services\Concerns\HasCrudAliases;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class GuardAssignmentService
{
    use HasCrudAliases;

    public function __construct(
        protected GuardAssignmentRepositoryInterface $assignmentRepository
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $this->assignmentRepository->paginate($filters, $perPage);
    }

    public function get(int $id): GuardAssignment
    {
        return $this->assignmentRepository->findOrFail($id);
    }

    public function assign(array $data): GuardAssignment
    {
        $this->assertNoDuplicate(
            $data['guard_id'],
            $data['site_id'],
            $data['shift_id'],
            $data['from_date'],
            $data['to_date']
        );

        return $this->assignmentRepository->create([
            'guard_id' => $data['guard_id'],
            'site_id' => $data['site_id'],
            'shift_id' => $data['shift_id'],
            'from_date' => $data['from_date'],
            'to_date' => $data['to_date'],
            'status' => $data['status'] ?? RecordStatus::Active,
        ]);
    }

    public function update(int $id, array $data): GuardAssignment
    {
        $assignment = $this->assignmentRepository->findOrFail($id);

        $guardId = $data['guard_id'] ?? $assignment->guard_id;
        $siteId = $data['site_id'] ?? $assignment->site_id;
        $shiftId = $data['shift_id'] ?? $assignment->shift_id;
        $fromDate = $data['from_date'] ?? $assignment->from_date->toDateString();
        $toDate = $data['to_date'] ?? $assignment->to_date->toDateString();

        $this->assertNoDuplicate($guardId, $siteId, $shiftId, $fromDate, $toDate, $id);

        return $this->assignmentRepository->update($id, array_filter([
            'guard_id' => $data['guard_id'] ?? null,
            'site_id' => $data['site_id'] ?? null,
            'shift_id' => $data['shift_id'] ?? null,
            'from_date' => $data['from_date'] ?? null,
            'to_date' => $data['to_date'] ?? null,
            'status' => $data['status'] ?? null,
        ], fn ($value) => $value !== null));
    }

    public function remove(int $id): bool
    {
        return $this->assignmentRepository->delete($id);
    }

    protected function assertNoDuplicate(
        int $guardId,
        int $siteId,
        int $shiftId,
        string $fromDate,
        string $toDate,
        ?int $excludeId = null
    ): void {
        if ($this->assignmentRepository->hasOverlappingAssignment(
            $guardId,
            $siteId,
            $shiftId,
            $fromDate,
            $toDate,
            $excludeId
        )) {
            throw ValidationException::withMessages([
                'assignment' => ['This guard already has an overlapping assignment for the same site and shift.'],
            ]);
        }
    }

    public function create(array $data): GuardAssignment
    {
        return $this->assign($data);
    }

    public function delete(int $id): bool
    {
        return $this->remove($id);
    }
}
