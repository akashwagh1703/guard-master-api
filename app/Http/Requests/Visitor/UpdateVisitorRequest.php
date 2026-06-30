<?php

namespace App\Http\Requests\Visitor;

use App\Enums\VisitorStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVisitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visitor_name' => ['sometimes', 'required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'person_to_meet' => ['nullable', 'string', 'max:255'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'id_type' => ['nullable', 'string', 'max:50'],
            'id_number' => ['nullable', 'string', 'max:100'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'exit_time' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
            'exit_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'exit_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['nullable', Rule::enum(VisitorStatus::class)],
        ];
    }
}
