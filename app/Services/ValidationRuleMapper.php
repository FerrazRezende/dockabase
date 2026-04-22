<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ValidationPresetEnum;

class ValidationRuleMapper
{
    /**
     * Convert JSON validations to Laravel rules array.
     *
     * @param  array<string, array<string, bool|numeric|string|null>>  $columnValidations
     * @return array<string, array<int, string>>
     */
    public static function toLaravelRules(array $columnValidations, ?string $tableName = null): array
    {
        $rules = [];

        foreach ($columnValidations as $columnName => $presets) {
            $columnRules = [];

            foreach ($presets as $presetName => $config) {
                if ($config === false || $config === null) {
                    continue;
                }

                $preset = ValidationPresetEnum::from($presetName);
                $value = is_bool($config) ? null : (string) $config;

                if ($preset === ValidationPresetEnum::UNIQUE) {
                    $columnRules[] = $preset->toLaravelRule(null, $tableName, $columnName);
                } else {
                    $columnRules[] = $preset->toLaravelRule($value);
                }
            }

            if ($columnRules !== []) {
                $rules[$columnName] = $columnRules;
            }
        }

        return $rules;
    }

    /**
     * Get validation presets applicable to a type category.
     *
     * @return array<int, ValidationPresetEnum>
     */
    public static function getPresetsForCategory(string $category): array
    {
        return array_values(
            array_filter(
                ValidationPresetEnum::cases(),
                fn (ValidationPresetEnum $preset): bool => in_array($category, $preset->applicableCategories(), true),
            )
        );
    }
}
