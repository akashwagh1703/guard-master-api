<?php

namespace App\Http\Requests\Shift;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'grace_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'late_after' => ['nullable', 'integer', 'min:0', 'max:120'],
            'status' => ['nullable', Rule::enum(RecordStatus::class)],
        ];
    }
}
