<?php

namespace App\Services;

use App\Models\Site;
use App\Repositories\Contracts\SiteRepositoryInterface;
use App\Services\Concerns\HasCrudAliases;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SiteService
{
    use HasCrudAliases;

    public function __construct(
        protected SiteRepositoryInterface $siteRepository
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $this->siteRepository->paginate($filters, $perPage);
    }

    public function get(int $id): Site
    {
        return $this->siteRepository->findOrFail($id);
    }

    public function create(array $data): Site
    {
        return $this->siteRepository->create($data);
    }

    public function update(int $id, array $data): Site
    {
        return $this->siteRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->siteRepository->delete($id);
    }
}
