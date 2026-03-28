<?php

declare(strict_types=1);

namespace App\Http\Requests\System;

use App\Enums\RolloutStrategyEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivateFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'strategy' => ['required', Rule::in(array_column(RolloutStrategyEnum::cases(), 'value'))],
            'percentage' => ['nullable', 'integer', 'min:0', 'max:100', 'required_if:strategy,percentage'],
            'user_ids' => ['nullable', 'array', 'required_if:strategy,users'],
            'user_ids.*' => ['string'],
        ];
    }
}
