<?php

namespace App\Http\Requests\Assignment;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guard_id' => ['sometimes', 'required', 'integer', 'exists:guards,id'],
            'site_id' => ['sometimes', 'required', 'integer', 'exists:sites,id'],
            'shift_id' => ['sometimes', 'required', 'integer', 'exists:shifts,id'],
            'from_date' => ['sometimes', 'required', 'date'],
            'to_date' => ['sometimes', 'required', 'date', 'after_or_equal:from_date'],
            'status' => ['nullable', Rule::enum(RecordStatus::class)],
        ];
    }
}
