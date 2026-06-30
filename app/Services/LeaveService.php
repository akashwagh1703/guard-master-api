<?php

namespace App\Services;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Repositories\Contracts\LeaveRepositoryInterface;
use App\Services\Concerns\HasCrudAliases;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    use HasCrudAliases;

    public function __construct(
        protected LeaveRepositoryInterface $leaveRepository
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $this->leaveRepository->paginate($filters, $perPage);
    }

    public function get(int $id): LeaveRequest
    {
        return $this->leaveRepository->findOrFail($id);
    }

    public function apply(User $user, array $data): LeaveRequest
    {
        $data['guard_id'] = $user->guard_id;

        return $this->createLeave($data);
    }

    public function getMyLeaves(User $user, array $filters = []): LengthAwarePaginator
    {
        $filters['guard_id'] = $user->guard_id;

        return $this->list($filters);
    }

    public function updateStatus(int $id, array $data): LeaveRequest
    {
        $status = $data['status'] ?? null;
        $remarks = $data['admin_remarks'] ?? null;

        return match ($status) {
            'approved', LeaveStatus::Approved->value => $this->approve($id, $remarks),
            'rejected', LeaveStatus::Rejected->value => $this->reject($id, $remarks),
            default => throw ValidationException::withMessages(['status' => ['Invalid status.']]),
        };
    }

    public function approve(int $id, ?string $remarks = null): LeaveRequest
    {
        $leave = $this->leaveRepository->findOrFail($id);

        if ($leave->status !== LeaveStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => ['Only pending leave requests can be approved.'],
            ]);
        }

        return $this->leaveRepository->update($id, [
            'status' => LeaveStatus::Approved,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'admin_remarks' => $remarks,
        ]);
    }

    public function reject(int $id, ?string $remarks = null): LeaveRequest
    {
        $leave = $this->leaveRepository->findOrFail($id);

        if ($leave->status !== LeaveStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => ['Only pending leave requests can be rejected.'],
            ]);
        }

        return $this->leaveRepository->update($id, [
            'status' => LeaveStatus::Rejected,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'admin_remarks' => $remarks,
        ]);
    }

    public function history(int $guardId, array $filters = []): Collection
    {
        return $this->leaveRepository->getByGuard($guardId, $filters);
    }

    protected function createLeave(array $data): LeaveRequest
    {
        $fromDate = Carbon::parse($data['from_date']);
        $toDate = Carbon::parse($data['to_date']);
        $days = $fromDate->diffInDays($toDate) + 1;

        return $this->leaveRepository->create([
            'guard_id' => $data['guard_id'],
            'type' => $data['type'],
            'from_date' => $fromDate->toDateString(),
            'to_date' => $toDate->toDateString(),
            'days' => $days,
            'reason' => $data['reason'],
            'status' => LeaveStatus::Pending,
        ]);
    }
}
