<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuardAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'guard_id' => $this->guard_id,
            'site_id' => $this->site_id,
            'shift_id' => $this->shift_id,
            'from_date' => $this->from_date?->toDateString(),
            'to_date' => $this->to_date?->toDateString(),
            'status' => $this->status?->value,
            'guard' => new GuardResource($this->whenLoaded('securityGuard')),
            'site' => new SiteResource($this->whenLoaded('site')),
            'shift' => new ShiftResource($this->whenLoaded('shift')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
