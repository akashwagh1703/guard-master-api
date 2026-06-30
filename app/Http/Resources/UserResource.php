<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'role' => $this->role?->value,
            'phone' => $this->phone,
            'photo' => $this->photo,
            'guard_id' => $this->guard_id,
            'device_name' => $this->device_name,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'guard_profile' => new GuardResource($this->whenLoaded('guardProfile')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
