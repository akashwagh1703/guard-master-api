<?php

namespace App\Http\Requests\Guard;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateGuardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $guardId = $this->route('guard');

        return [
            'employee_id' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('guards', 'employee_id')->ignore($guardId)],
            'photo' => ['nullable', 'image', 'max:2048'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'joining_date' => ['nullable', 'date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'overtime_rate' => ['nullable', 'numeric', 'min:0'],
            'username' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('guards', 'username')->ignore($guardId)],
            'password' => ['nullable', Password::defaults()],
            'status' => ['nullable', Rule::enum(RecordStatus::class)],
        ];
    }
}
