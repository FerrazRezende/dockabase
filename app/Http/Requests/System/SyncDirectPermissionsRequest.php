<?php

declare(strict_types=1);

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;

class SyncDirectPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
            'denied_permissions' => ['nullable', 'array'],
            'denied_permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}
