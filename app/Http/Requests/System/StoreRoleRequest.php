<?php

declare(strict_types=1);

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'guard_name' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    protected function passedValidation(): void
    {
        $this->merge([
            'guard_name' => $this->input('guard_name', 'web'),
        ]);
    }
}
