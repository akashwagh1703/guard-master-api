<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_id' => $this->site_id,
            'guard_id' => $this->guard_id,
            'category' => $this->category,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority?->value,
            'status' => $this->status?->value,
            'images' => $this->images,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'incident_time' => $this->incident_time?->toIso8601String(),
            'admin_comments' => $this->admin_comments,
            'site' => new SiteResource($this->whenLoaded('site')),
            'guard' => new GuardResource($this->whenLoaded('securityGuard')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
