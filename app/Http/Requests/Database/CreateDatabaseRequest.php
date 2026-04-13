<?php

declare(strict_types=1);

namespace App\Http\Requests\Database;

use Illuminate\Foundation\Http\FormRequest;

class CreateDatabaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_-]*$/', 'unique:databases,name'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'database_name' => ['required', 'string', 'max:64'],
            'is_active' => ['nullable', 'boolean'],
            'credential_ids' => ['nullable', 'array'],
            'credential_ids.*' => ['string', 'size:27', 'exists:credentials,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => __('The name must start with a lowercase letter and contain only lowercase letters, numbers, underscores, and hyphens.'),
            'credential_ids.*.size' => __('Each credential ID must be a valid KSUID (27 characters).'),
        ];
    }
}
