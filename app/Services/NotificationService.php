<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    protected string $table = 'app_notifications';

    public function list(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return DB::table($this->table)
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->when(isset($filters['read']), function ($query) use ($filters) {
                $filters['read']
                    ? $query->whereNotNull('read_at')
                    : $query->whereNull('read_at');
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->through(fn ($row) => $this->formatNotification($row));
    }

    public function getUnreadCount(User $user): int
    {
        return DB::table($this->table)
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function markRead(User $user, string $id): bool
    {
        return (bool) DB::table($this->table)
            ->where('id', $id)
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->update(['read_at' => now()]);
    }

    public function markAllRead(User $user): int
    {
        return DB::table($this->table)
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function delete(User $user, string $id): bool
    {
        return (bool) DB::table($this->table)
            ->where('id', $id)
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->delete();
    }

    public function create(User $user, string $type, array $data): void
    {
        DB::table($this->table)->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => $type,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode($data),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function formatNotification(object $row): array
    {
        $data = json_decode($row->data, true) ?? [];

        return [
            'id' => $row->id,
            'type' => $row->type,
            'title' => $data['title'] ?? class_basename($row->type),
            'message' => $data['message'] ?? '',
            'data' => $data,
            'read' => $row->read_at !== null,
            'read_at' => $row->read_at,
            'created_at' => $row->created_at,
        ];
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $admin = auth()->user();
        if ($admin?->isAdmin()) {
            return DB::table($this->table)->orderByDesc('created_at')->paginate((int) ($filters['per_page'] ?? 15));
        }

        return $this->list($admin, $filters);
    }

    public function getForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->list($user, $filters);
    }

    public function markAsRead(string $id, User $user): bool
    {
        return $this->markRead($user, $id);
    }
}
