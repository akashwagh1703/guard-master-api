<?php

namespace App\Services;

use App\Models\Shift;
use App\Repositories\Contracts\ShiftRepositoryInterface;
use App\Services\Concerns\HasCrudAliases;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ShiftService
{
    use HasCrudAliases;

    public function __construct(
        protected ShiftRepositoryInterface $shiftRepository
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $this->shiftRepository->paginate($filters, $perPage);
    }

    public function get(int $id): Shift
    {
        return $this->shiftRepository->findOrFail($id);
    }

    public function create(array $data): Shift
    {
        return $this->shiftRepository->create($data);
    }

    public function update(int $id, array $data): Shift
    {
        return $this->shiftRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->shiftRepository->delete($id);
    }
}
