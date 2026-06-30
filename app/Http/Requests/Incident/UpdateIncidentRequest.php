<?php

namespace App\Http\Requests\Incident;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'max:100'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'priority' => ['nullable', Rule::enum(IncidentPriority::class)],
            'status' => ['nullable', Rule::enum(IncidentStatus::class)],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:2048'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'incident_time' => ['sometimes', 'required', 'date'],
            'admin_comments' => ['nullable', 'string'],
        ];
    }
}
