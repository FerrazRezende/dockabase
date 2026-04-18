<?php

declare(strict_types=1);

namespace App\Http\Requests\Migration;

use App\Enums\MigrationOperationEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateMigrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'operation' => ['required', 'string', Rule::enum(MigrationOperationEnum::class)],
            'table_name' => ['required', 'string', 'max:63', 'regex:/^[a-z_][a-z0-9_]*$/'],
            'schema_name' => ['nullable', 'string', 'max:63', 'regex:/^[a-z_][a-z0-9_]*$/'],
            'column' => ['nullable', 'array'],
            'column.name' => ['nullable', 'string', 'max:63', 'regex:/^[a-z_][a-z0-9_]*$/'],
            'column.type' => ['nullable', 'string'],
            'column.nullable' => ['nullable', 'boolean'],
            'column.default_value' => ['nullable', 'string'],
            'new_name' => ['nullable', 'string', 'max:63', 'regex:/^[a-z_][a-z0-9_]*$/'],
            'confirmed' => ['nullable', 'boolean'],
        ];
    }
}
