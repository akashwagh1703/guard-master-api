<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Models\GuardAssignment;
use App\Models\User;
use App\Repositories\Contracts\GuardRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        protected GuardRepositoryInterface $guardRepository
    ) {}

    public function login(string $identifier, string $password, ?string $deviceName = null): array
    {
        $user = User::query()
            ->where(function ($query) use ($identifier) {
                $query->where('email', $identifier)
                    ->orWhere('username', $identifier);
            })
            ->first();

        if (! $user && ! str_contains($identifier, '@')) {
            $guard = $this->guardRepository->findByUsername($identifier);
            if ($guard?->user) {
                $user = $guard->user;
            }
        }

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->update([
            'last_login_at' => now(),
            'device_name' => $deviceName ?? $user->device_name,
        ]);

        $token = $user->createToken($deviceName ?? 'api-token')->plainTextToken;

        return [
            'user' => $this->formatUser($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function logout(?User $user = null): void
    {
        $user = $user ?? Auth::user();

        if ($user?->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }
    }

    public function getProfile(?User $user = null): array
    {
        return $this->formatUser($user ?? Auth::user());
    }

    public function getGuardAssignment(User $user): ?array
    {
        return $this->getActiveAssignment($user);
    }

    public function updateProfile(array $data, ?User $user = null): array
    {
        $user = $user ?? Auth::user();
        $user->update(array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'photo' => $data['photo'] ?? null,
        ], fn ($value) => $value !== null));

        if ($user->isGuard() && $user->guardProfile) {
            $user->guardProfile->update(array_filter([
                'name' => $data['name'] ?? null,
                'mobile' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
            ], fn ($value) => $value !== null));
        }

        return $this->formatUser($user->fresh(['guardProfile']));
    }

    public function changePassword(string $currentPassword, string $newPassword, ?User $user = null): void
    {
        $user = $user ?? Auth::user();

        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update(['password' => Hash::make($newPassword)]);
    }

    public function forgotPassword(string $email): string
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }

    public function resetPassword(array $data): string
    {
        $status = Password::reset(
            $data,
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }

    protected function formatUser(User $user): array
    {
        $user->loadMissing('guardProfile');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'phone' => $user->phone,
            'photo' => $user->photo,
            'role' => $user->role->value,
            'role_label' => $user->role->label(),
            'guard_id' => $user->guard_id,
            'guard' => $user->guardProfile ? [
                'id' => $user->guardProfile->id,
                'employee_id' => $user->guardProfile->employee_id,
                'name' => $user->guardProfile->name,
            ] : null,
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'assignment' => $this->getActiveAssignment($user),
        ];
    }

    protected function getActiveAssignment(User $user): ?array
    {
        if (! $user->isGuard() || ! $user->guard_id) {
            return null;
        }

        $assignment = GuardAssignment::query()
            ->where('guard_id', $user->guard_id)
            ->where('status', RecordStatus::Active)
            ->whereDate('from_date', '<=', now())
            ->whereDate('to_date', '>=', now())
            ->with(['site', 'shift'])
            ->first();

        if (! $assignment) {
            return null;
        }

        return [
            'id' => $assignment->id,
            'site' => $assignment->site?->name,
            'site_id' => $assignment->site_id,
            'site_lat' => (float) $assignment->site?->latitude,
            'site_lng' => (float) $assignment->site?->longitude,
            'radius' => (int) ($assignment->site?->attendance_radius ?? 100),
            'shift' => $assignment->shift?->name,
            'shift_start' => $assignment->shift?->start_time,
            'shift_end' => $assignment->shift?->end_time,
            'from_date' => $assignment->from_date?->toDateString(),
            'to_date' => $assignment->to_date?->toDateString(),
        ];
    }
}
