<?php

namespace App\Http\Requests\Leave;

use App\Enums\LeaveStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(LeaveStatus::class)->only([LeaveStatus::Approved, LeaveStatus::Rejected])],
            'admin_remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
