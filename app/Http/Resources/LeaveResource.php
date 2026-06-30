<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'guard_id' => $this->guard_id,
            'type' => $this->type,
            'from_date' => $this->from_date?->toDateString(),
            'to_date' => $this->to_date?->toDateString(),
            'days' => $this->days,
            'reason' => $this->reason,
            'status' => $this->status?->value,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'admin_remarks' => $this->admin_remarks,
            'guard' => new GuardResource($this->whenLoaded('securityGuard')),
            'approver' => new UserResource($this->whenLoaded('approver')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
