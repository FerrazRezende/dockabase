<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\{PostgresTypeEnum, ValidationPresetEnum};
use Tests\TestCase;

class ValidationPresetEnumTest extends TestCase
{
    public function test_to_laravel_rule_returns_correct_rule(): void
    {
        $this->assertEquals('required', ValidationPresetEnum::REQUIRED->toLaravelRule());
        $this->assertEquals('min:3', ValidationPresetEnum::MIN_LENGTH->toLaravelRule(3));
        $this->assertEquals('max:255', ValidationPresetEnum::MAX_LENGTH->toLaravelRule(255));
        $this->assertEquals('min:0', ValidationPresetEnum::MIN_VALUE->toLaravelRule(0));
        $this->assertEquals('email', ValidationPresetEnum::EMAIL->toLaravelRule());
        $this->assertEquals('regex:/^[a-Z]+$/', ValidationPresetEnum::REGEX->toLaravelRule('/^[a-Z]+$/'));
    }

    public function test_applicable_types_returns_correct_types(): void
    {
        $stringTypes = ValidationPresetEnum::REQUIRED->applicableTypes();
        $this->assertContains(PostgresTypeEnum::VARCHAR, $stringTypes);
        $this->assertContains(PostgresTypeEnum::INTEGER, $stringTypes);

        $minLengthTypes = ValidationPresetEnum::MIN_LENGTH->applicableTypes();
        $this->assertContains(PostgresTypeEnum::VARCHAR, $minLengthTypes);
        $this->assertContains(PostgresTypeEnum::TEXT, $minLengthTypes);
        $this->assertNotContains(PostgresTypeEnum::INTEGER, $minLengthTypes);
    }

    public function test_all_presets_have_labels(): void
    {
        foreach (ValidationPresetEnum::cases() as $case) {
            $this->assertIsString($case->label());
        }
    }
}
