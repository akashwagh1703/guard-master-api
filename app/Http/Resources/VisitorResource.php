<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_id' => $this->site_id,
            'guard_id' => $this->guard_id,
            'visitor_name' => $this->visitor_name,
            'mobile' => $this->mobile,
            'purpose' => $this->purpose,
            'person_to_meet' => $this->person_to_meet,
            'vehicle_number' => $this->vehicle_number,
            'id_type' => $this->id_type,
            'id_number' => $this->id_number,
            'photo' => $this->photo,
            'entry_time' => $this->entry_time?->toIso8601String(),
            'exit_time' => $this->exit_time?->toIso8601String(),
            'remarks' => $this->remarks,
            'entry_latitude' => $this->entry_latitude,
            'entry_longitude' => $this->entry_longitude,
            'exit_latitude' => $this->exit_latitude,
            'exit_longitude' => $this->exit_longitude,
            'status' => $this->status?->value,
            'site' => new SiteResource($this->whenLoaded('site')),
            'guard' => new GuardResource($this->whenLoaded('securityGuard')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
