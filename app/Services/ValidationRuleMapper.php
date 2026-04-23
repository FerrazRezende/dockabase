<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PostgresTypeEnum;
use App\Enums\ValidationPresetEnum;

class ValidationRuleMapper
{
    public function toJsonRules(array $uiInput): array
    {
        $result = [];

        foreach ($uiInput as $column => $presets) {
            foreach ($presets as $preset) {
                if (! ($preset['enabled'] ?? false)) {
                    continue;
                }

                $enum = ValidationPresetEnum::from($preset['preset']);

                $key = match ($enum) {
                    ValidationPresetEnum::MIN_LENGTH => 'min',
                    ValidationPresetEnum::MAX_LENGTH => 'max',
                    ValidationPresetEnum::MIN_VALUE => 'min',
                    ValidationPresetEnum::MAX_VALUE => 'max',
                    default => $enum->value,
                };

                $value = $preset['value'] ?? true;

                if ($enum === ValidationPresetEnum::REQUIRED || $enum === ValidationPresetEnum::INTEGER
                    || $enum === ValidationPresetEnum::NUMERIC || $enum === ValidationPresetEnum::ALPHA
                    || $enum === ValidationPresetEnum::ALPHA_NUM || $enum === ValidationPresetEnum::ALPHA_DASH
                    || $enum === ValidationPresetEnum::EMAIL || $enum === ValidationPresetEnum::URL
                    || $enum === ValidationPresetEnum::UUID || $enum === ValidationPresetEnum::DATE
                    || $enum === ValidationPresetEnum::BOOLEAN
                ) {
                    $value = true;
                }

                $result[$column][$key] = $value;
            }
        }

        return $result;
    }

    public function toLaravelRules(array $jsonRules): array
    {
        $result = [];

        foreach ($jsonRules as $column => $rules) {
            foreach ($rules as $key => $value) {
                $ruleString = is_bool($value) ? $key : "{$key}:{$value}";
                $result[$column][] = $ruleString;
            }
        }

        return $result;
    }

    public function getApplicablePresets(string $postgresType): array
    {
        try {
            $type = PostgresTypeEnum::from($postgresType);
        } catch (\ValueError $e) {
            return [];
        }

        return array_filter(
            ValidationPresetEnum::cases(),
            fn (ValidationPresetEnum $preset) => in_array($type, $preset->applicableTypes(), true)
        );
    }
}
