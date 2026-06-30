<?php

namespace App\Http\Requests\Visitor;

use Illuminate\Foundation\Http\FormRequest;

class GuardStoreVisitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visitor_name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'purpose' => ['required', 'string', 'max:255'],
            'person_to_meet' => ['nullable', 'string', 'max:255'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'id_type' => ['nullable', 'string', 'max:50'],
            'id_number' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string'],
            'entry_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'entry_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
