<?php

namespace App\Services;

use App\Enums\VisitorStatus;
use App\Models\VisitorEntry;
use App\Repositories\Contracts\VisitorRepositoryInterface;
use App\Services\Concerns\HasCrudAliases;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class VisitorService
{
    use HasCrudAliases;

    public function __construct(
        protected VisitorRepositoryInterface $visitorRepository
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $this->visitorRepository->paginate($filters, $perPage);
    }

    public function get(int $id): VisitorEntry
    {
        return $this->visitorRepository->findOrFail($id);
    }

    public function create(array $data, ?UploadedFile $photo = null): VisitorEntry
    {
        $photoPath = $photo ? $photo->store('visitors', 'public') : null;

        return $this->visitorRepository->create([
            'site_id' => $data['site_id'],
            'guard_id' => $data['guard_id'],
            'visitor_name' => $data['visitor_name'],
            'mobile' => $data['mobile'] ?? null,
            'purpose' => $data['purpose'],
            'person_to_meet' => $data['person_to_meet'] ?? null,
            'vehicle_number' => $data['vehicle_number'] ?? null,
            'id_type' => $data['id_type'] ?? null,
            'id_number' => $data['id_number'] ?? null,
            'photo' => $photoPath,
            'remarks' => $data['remarks'] ?? null,
            'status' => VisitorStatus::Inside,
            'entry_time' => $data['entry_time'] ?? now(),
            'entry_latitude' => $data['latitude'] ?? null,
            'entry_longitude' => $data['longitude'] ?? null,
        ]);
    }

    public function update(int $id, array $data, ?UploadedFile $photo = null): VisitorEntry
    {
        if ($photo) {
            $visitor = $this->visitorRepository->findOrFail($id);
            if ($visitor->photo) {
                Storage::disk('public')->delete($visitor->photo);
            }
            $data['photo'] = $photo->store('visitors', 'public');
        }

        return $this->visitorRepository->update($id, array_filter([
            'visitor_name' => $data['visitor_name'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'purpose' => $data['purpose'] ?? null,
            'person_to_meet' => $data['person_to_meet'] ?? null,
            'vehicle_number' => $data['vehicle_number'] ?? null,
            'id_type' => $data['id_type'] ?? null,
            'id_number' => $data['id_number'] ?? null,
            'photo' => $data['photo'] ?? null,
            'remarks' => $data['remarks'] ?? null,
        ], fn ($value) => $value !== null));
    }

    public function delete(int $id): bool
    {
        $visitor = $this->visitorRepository->findOrFail($id);

        if ($visitor->photo) {
            Storage::disk('public')->delete($visitor->photo);
        }

        return $this->visitorRepository->delete($id);
    }

    public function recordEntry(int $id, array $data): VisitorEntry
    {
        $visitor = $this->visitorRepository->findOrFail($id);

        if ($visitor->status === VisitorStatus::Inside) {
            throw ValidationException::withMessages([
                'entry' => ['Visitor is already inside.'],
            ]);
        }

        return $this->visitorRepository->update($id, [
            'status' => VisitorStatus::Inside,
            'entry_time' => now(),
            'exit_time' => null,
            'entry_latitude' => $data['latitude'] ?? null,
            'entry_longitude' => $data['longitude'] ?? null,
        ]);
    }

    public function recordExit(int $id, array $data): VisitorEntry
    {
        $visitor = $this->visitorRepository->findOrFail($id);

        if ($visitor->status === VisitorStatus::Exited) {
            throw ValidationException::withMessages([
                'exit' => ['Visitor has already exited.'],
            ]);
        }

        return $this->visitorRepository->update($id, [
            'status' => VisitorStatus::Exited,
            'exit_time' => now(),
            'exit_latitude' => $data['latitude'] ?? null,
            'exit_longitude' => $data['longitude'] ?? null,
        ]);
    }
}
