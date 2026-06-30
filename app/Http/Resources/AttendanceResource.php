<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'guard_id' => $this->guard_id,
            'site_id' => $this->site_id,
            'shift_id' => $this->shift_id,
            'assignment_id' => $this->assignment_id,
            'date' => $this->date?->toDateString(),
            'check_in_time' => $this->check_in_time,
            'check_out_time' => $this->check_out_time,
            'check_in_latitude' => $this->check_in_latitude,
            'check_in_longitude' => $this->check_in_longitude,
            'check_out_latitude' => $this->check_out_latitude,
            'check_out_longitude' => $this->check_out_longitude,
            'check_in_photo' => $this->check_in_photo,
            'check_out_photo' => $this->check_out_photo,
            'working_hours' => $this->working_hours,
            'late_minutes' => $this->late_minutes,
            'overtime_hours' => $this->overtime_hours,
            'status' => $this->status?->value,
            'admin_override' => $this->admin_override,
            'remarks' => $this->remarks,
            'guard' => new GuardResource($this->whenLoaded('securityGuard')),
            'site' => new SiteResource($this->whenLoaded('site')),
            'shift' => new ShiftResource($this->whenLoaded('shift')),
            'assignment' => new GuardAssignmentResource($this->whenLoaded('assignment')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
