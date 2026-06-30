<?php

namespace App\Services;

use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Repositories\Contracts\IncidentRepositoryInterface;
use App\Services\Concerns\HasCrudAliases;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class IncidentService
{
    use HasCrudAliases;

    public function __construct(
        protected IncidentRepositoryInterface $incidentRepository
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $this->incidentRepository->paginate($filters, $perPage);
    }

    public function get(int $id): Incident
    {
        return $this->incidentRepository->findOrFail($id);
    }

    public function create(array $data, array $images = []): Incident
    {
        return $this->incidentRepository->create([
            'site_id' => $data['site_id'],
            'guard_id' => $data['guard_id'],
            'category' => $data['category'],
            'title' => $data['title'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'status' => $data['status'] ?? IncidentStatus::Open,
            'images' => $this->uploadImages($images),
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'incident_time' => $data['incident_time'] ?? now(),
        ]);
    }

    public function update(int $id, array $data, array $images = []): Incident
    {
        $incident = $this->incidentRepository->findOrFail($id);
        $existingImages = $incident->images ?? [];

        if (! empty($images)) {
            $data['images'] = array_merge($existingImages, $this->uploadImages($images));
        }

        return $this->incidentRepository->update($id, array_filter([
            'site_id' => $data['site_id'] ?? null,
            'guard_id' => $data['guard_id'] ?? null,
            'category' => $data['category'] ?? null,
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'] ?? null,
            'status' => $data['status'] ?? null,
            'images' => $data['images'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'incident_time' => $data['incident_time'] ?? null,
            'admin_comments' => $data['admin_comments'] ?? null,
        ], fn ($value) => $value !== null));
    }

    public function delete(int $id): bool
    {
        $incident = $this->incidentRepository->findOrFail($id);

        foreach ($incident->images ?? [] as $image) {
            Storage::disk('public')->delete($image);
        }

        return $this->incidentRepository->delete($id);
    }

    protected function uploadImages(array $images): array
    {
        $paths = [];

        foreach ($images as $image) {
            if ($image instanceof UploadedFile) {
                $paths[] = $image->store('incidents', 'public');
            }
        }

        return $paths;
    }
}
