<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Models\Guard;
use App\Models\User;
use App\Repositories\Contracts\GuardRepositoryInterface;
use App\Services\Concerns\HasCrudAliases;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class GuardService
{
    use HasCrudAliases;

    public function __construct(
        protected GuardRepositoryInterface $guardRepository
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $this->guardRepository->paginate($filters, $perPage);
    }

    public function get(int $id): Guard
    {
        return $this->guardRepository->findOrFail($id);
    }

    public function create(array $data, ?UploadedFile $photo = null): Guard
    {
        return DB::transaction(function () use ($data, $photo) {
            if ($this->guardRepository->findByEmployeeId($data['employee_id'])) {
                throw ValidationException::withMessages([
                    'employee_id' => ['Employee ID already exists.'],
                ]);
            }

            if (! empty($data['username']) && $this->guardRepository->findByUsername($data['username'])) {
                throw ValidationException::withMessages([
                    'username' => ['Username already exists.'],
                ]);
            }

            $photoPath = $photo ? $this->uploadPhoto($photo) : null;

            $guard = $this->guardRepository->create([
                'employee_id' => $data['employee_id'],
                'name' => $data['name'],
                'mobile' => $data['mobile'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'joining_date' => $data['joining_date'] ?? null,
                'salary' => $data['salary'] ?? 0,
                'overtime_rate' => $data['overtime_rate'] ?? 0,
                'username' => $data['username'] ?? null,
                'photo' => $photoPath,
                'status' => $data['status'] ?? RecordStatus::Active,
            ]);

            if (! empty($data['password'])) {
                $user = User::create([
                    'name' => $guard->name,
                    'email' => $guard->email ?? ($guard->username . '@guards.local'),
                    'username' => $guard->username,
                    'password' => Hash::make($data['password']),
                    'role' => UserRole::Guard,
                    'guard_id' => $guard->id,
                    'phone' => $guard->mobile,
                ]);

                $guard->update(['user_id' => $user->id]);
            }

            return $guard->fresh(['user']);
        });
    }

    public function update(int $id, array $data, ?UploadedFile $photo = null): Guard
    {
        return DB::transaction(function () use ($id, $data, $photo) {
            $guard = $this->guardRepository->findOrFail($id);

            if (! empty($data['employee_id'])) {
                $existing = $this->guardRepository->findByEmployeeId($data['employee_id']);
                if ($existing && $existing->id !== $guard->id) {
                    throw ValidationException::withMessages([
                        'employee_id' => ['Employee ID already exists.'],
                    ]);
                }
            }

            if (! empty($data['username'])) {
                $existing = $this->guardRepository->findByUsername($data['username']);
                if ($existing && $existing->id !== $guard->id) {
                    throw ValidationException::withMessages([
                        'username' => ['Username already exists.'],
                    ]);
                }
            }

            if ($photo) {
                if ($guard->photo) {
                    Storage::disk('public')->delete($guard->photo);
                }
                $data['photo'] = $this->uploadPhoto($photo);
            }

            $guard = $this->guardRepository->update($id, array_filter([
                'employee_id' => $data['employee_id'] ?? null,
                'name' => $data['name'] ?? null,
                'mobile' => $data['mobile'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'joining_date' => $data['joining_date'] ?? null,
                'salary' => $data['salary'] ?? null,
                'overtime_rate' => $data['overtime_rate'] ?? null,
                'username' => $data['username'] ?? null,
                'photo' => $data['photo'] ?? null,
                'status' => $data['status'] ?? null,
            ], fn ($value) => $value !== null));

            if ($guard->user && (! empty($data['password']) || ! empty($data['name']) || ! empty($data['email']))) {
                $guard->user->update(array_filter([
                    'name' => $data['name'] ?? null,
                    'email' => $data['email'] ?? null,
                    'username' => $data['username'] ?? null,
                    'phone' => $data['mobile'] ?? null,
                    'password' => ! empty($data['password']) ? Hash::make($data['password']) : null,
                ], fn ($value) => $value !== null));
            }

            return $guard->fresh(['user']);
        });
    }

    public function delete(int $id): bool
    {
        $guard = $this->guardRepository->findOrFail($id);

        if ($guard->photo) {
            Storage::disk('public')->delete($guard->photo);
        }

        return $this->guardRepository->delete($id);
    }

    protected function uploadPhoto(UploadedFile $photo): string
    {
        return $photo->store('guards', 'public');
    }
}
