<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'guard_id' => $this->guard_id,
            'month' => $this->month,
            'year' => $this->year,
            'base_salary' => $this->base_salary,
            'present_days' => $this->present_days,
            'absent_days' => $this->absent_days,
            'half_days' => $this->half_days,
            'late_count' => $this->late_count,
            'overtime_amount' => $this->overtime_amount,
            'bonus' => $this->bonus,
            'advance' => $this->advance,
            'deduction' => $this->deduction,
            'net_salary' => $this->net_salary,
            'status' => $this->status?->value,
            'generated_at' => $this->generated_at?->toIso8601String(),
            'guard' => new GuardResource($this->whenLoaded('securityGuard')),
            'items' => $this->whenLoaded('items'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
