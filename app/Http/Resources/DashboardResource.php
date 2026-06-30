<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'attendance_summary' => $this->resource['attendance_summary'] ?? [],
            'present' => $this->resource['present'] ?? 0,
            'absent' => $this->resource['absent'] ?? 0,
            'late' => $this->resource['late'] ?? 0,
            'on_duty' => $this->resource['on_duty'] ?? 0,
            'visitors_today' => $this->resource['visitors_today'] ?? 0,
            'incidents_today' => $this->resource['incidents_today'] ?? 0,
            'payroll_pending' => $this->resource['payroll_pending'] ?? 0,
            'charts' => $this->resource['charts'] ?? [],
        ];
    }
}
