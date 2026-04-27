<?php

declare(strict_types=1);

namespace App\Http\Requests\SchemaBuilder;

use Illuminate\Foundation\Http\FormRequest;

class TableDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:500'],
            'search' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'string', 'max:63'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
