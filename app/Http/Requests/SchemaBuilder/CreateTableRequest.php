<?php

declare(strict_types=1);

namespace App\Http\Requests\SchemaBuilder;

use App\Enums\PostgresTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Database::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:63', 'regex:/^[a-z_][a-z0-9_]*$/'],
            'schema' => ['nullable', 'string', 'max:63', 'regex:/^[a-z_][a-z0-9_]*$/'],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*.name' => ['required', 'string', 'max:63', 'regex:/^[a-z_][a-z0-9_]*$/'],
            'columns.*.type' => ['required', 'string', Rule::enum(PostgresTypeEnum::class)],
            'columns.*.nullable' => ['nullable', 'boolean'],
            'columns.*.default_value' => ['nullable', 'string'],
            'columns.*.length' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'columns.*.is_primary_key' => ['nullable', 'boolean'],
            'columns.*.foreign_key' => ['nullable', 'array'],
            'columns.*.foreign_key.table' => ['required_with:columns.*.foreign_key', 'string', 'max:63'],
            'columns.*.foreign_key.column' => ['required_with:columns.*.foreign_key', 'string', 'max:63'],
            'validations' => ['nullable', 'array'],
        ];
    }
}
