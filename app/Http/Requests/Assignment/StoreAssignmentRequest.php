<?php

namespace App\Http\Requests\Assignment;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guard_id' => ['required', 'integer', 'exists:guards,id'],
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'status' => ['nullable', Rule::enum(RecordStatus::class)],
        ];
    }
}
