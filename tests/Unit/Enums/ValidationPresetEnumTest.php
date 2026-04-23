<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ValidationPresetEnum;
use Tests\TestCase;

class ValidationPresetEnumTest extends TestCase
{
    public function test_to_laravel_rule_required(): void
    {
        $this->assertSame('required', ValidationPresetEnum::REQUIRED->toLaravelRule());
    }

    public function test_to_laravel_rule_min_length_with_value(): void
    {
        $this->assertSame('min:3', ValidationPresetEnum::MIN_LENGTH->toLaravelRule('3'));
    }

    public function test_to_laravel_rule_max_length_with_value(): void
    {
        $this->assertSame('max:255', ValidationPresetEnum::MAX_LENGTH->toLaravelRule('255'));
    }

    public function test_to_laravel_rule_min_value(): void
    {
        $this->assertSame('min:0', ValidationPresetEnum::MIN_VALUE->toLaravelRule('0'));
    }

    public function test_to_laravel_rule_max_value(): void
    {
        $this->assertSame('max:999999.99', ValidationPresetEnum::MAX_VALUE->toLaravelRule('999999.99'));
    }

    public function test_to_laravel_rule_integer(): void
    {
        $this->assertSame('integer', ValidationPresetEnum::INTEGER->toLaravelRule());
    }

    public function test_to_laravel_rule_numeric(): void
    {
        $this->assertSame('numeric', ValidationPresetEnum::NUMERIC->toLaravelRule());
    }

    public function test_to_laravel_rule_regex(): void
    {
        $this->assertSame('regex:/^[a-z]+$/', ValidationPresetEnum::REGEX->toLaravelRule('/^[a-z]+$/'));
    }

    public function test_to_laravel_rule_unique_with_table_and_column(): void
    {
        $this->assertSame('unique:products,name', ValidationPresetEnum::UNIQUE->toLaravelRule(null, 'products', 'name'));
    }

    public function test_to_laravel_rule_exists_with_table_and_column(): void
    {
        $this->assertSame('exists:categories,id', ValidationPresetEnum::EXISTS->toLaravelRule('categories,id'));
    }

    public function test_to_laravel_rule_email(): void
    {
        $this->assertSame('email', ValidationPresetEnum::EMAIL->toLaravelRule());
    }

    public function test_to_laravel_rule_url(): void
    {
        $this->assertSame('url', ValidationPresetEnum::URL->toLaravelRule());
    }

    public function test_to_laravel_rule_uuid(): void
    {
        $this->assertSame('uuid', ValidationPresetEnum::UUID->toLaravelRule());
    }

    public function test_to_laravel_rule_date(): void
    {
        $this->assertSame('date', ValidationPresetEnum::DATE->toLaravelRule());
    }

    public function test_to_laravel_rule_boolean(): void
    {
        $this->assertSame('boolean', ValidationPresetEnum::BOOLEAN->toLaravelRule());
    }

    public function test_to_laravel_rule_in_list(): void
    {
        $this->assertSame('in:active,inactive,pending', ValidationPresetEnum::IN_LIST->toLaravelRule('active,inactive,pending'));
    }

    public function test_to_laravel_rule_alpha(): void
    {
        $this->assertSame('alpha', ValidationPresetEnum::ALPHA->toLaravelRule());
    }

    public function test_to_laravel_rule_alpha_num(): void
    {
        $this->assertSame('alpha_num', ValidationPresetEnum::ALPHA_NUM->toLaravelRule());
    }

    public function test_to_laravel_rule_alpha_dash(): void
    {
        $this->assertSame('alpha_dash', ValidationPresetEnum::ALPHA_DASH->toLaravelRule());
    }

    public function test_has_value_returns_true_for_presets_with_params(): void
    {
        $this->assertTrue(ValidationPresetEnum::MIN_LENGTH->hasValue());
        $this->assertTrue(ValidationPresetEnum::MAX_LENGTH->hasValue());
        $this->assertTrue(ValidationPresetEnum::MIN_VALUE->hasValue());
        $this->assertTrue(ValidationPresetEnum::MAX_VALUE->hasValue());
        $this->assertTrue(ValidationPresetEnum::REGEX->hasValue());
        $this->assertTrue(ValidationPresetEnum::EXISTS->hasValue());
        $this->assertTrue(ValidationPresetEnum::IN_LIST->hasValue());
    }

    public function test_has_value_returns_false_for_presets_without_params(): void
    {
        $this->assertFalse(ValidationPresetEnum::REQUIRED->hasValue());
        $this->assertFalse(ValidationPresetEnum::INTEGER->hasValue());
        $this->assertFalse(ValidationPresetEnum::EMAIL->hasValue());
        $this->assertFalse(ValidationPresetEnum::UNIQUE->hasValue());
    }

    public function test_applicable_categories_required_is_all(): void
    {
        $categories = ValidationPresetEnum::REQUIRED->applicableCategories();
        $this->assertContains('text', $categories);
        $this->assertContains('numeric', $categories);
        $this->assertContains('boolean', $categories);
        $this->assertContains('datetime', $categories);
        $this->assertContains('uuid', $categories);
        $this->assertContains('json', $categories);
        $this->assertContains('array', $categories);
        $this->assertContains('network', $categories);
    }

    public function test_applicable_categories_text_only(): void
    {
        $categories = ValidationPresetEnum::MIN_LENGTH->applicableCategories();
        $this->assertEquals(['text'], $categories);
    }

    public function test_applicable_categories_numeric_only(): void
    {
        $categories = ValidationPresetEnum::MIN_VALUE->applicableCategories();
        $this->assertEquals(['numeric'], $categories);
    }

    public function test_applicable_categories_boolean(): void
    {
        $categories = ValidationPresetEnum::BOOLEAN->applicableCategories();
        $this->assertEquals(['boolean'], $categories);
    }

    public function test_applicable_categories_datetime(): void
    {
        $categories = ValidationPresetEnum::DATE->applicableCategories();
        $this->assertEquals(['datetime'], $categories);
    }

    public function test_label_returns_human_readable(): void
    {
        $this->assertEquals('Required', ValidationPresetEnum::REQUIRED->label());
        $this->assertEquals('Min Length', ValidationPresetEnum::MIN_LENGTH->label());
        $this->assertEquals('Max Length', ValidationPresetEnum::MAX_LENGTH->label());
        $this->assertEquals('Min Value', ValidationPresetEnum::MIN_VALUE->label());
        $this->assertEquals('Max Value', ValidationPresetEnum::MAX_VALUE->label());
        $this->assertEquals('Regex', ValidationPresetEnum::REGEX->label());
        $this->assertEquals('Unique', ValidationPresetEnum::UNIQUE->label());
        $this->assertEquals('Exists', ValidationPresetEnum::EXISTS->label());
        $this->assertEquals('In List', ValidationPresetEnum::IN_LIST->label());
        $this->assertEquals('Alpha', ValidationPresetEnum::ALPHA->label());
        $this->assertEquals('Alpha Num', ValidationPresetEnum::ALPHA_NUM->label());
        $this->assertEquals('Alpha Dash', ValidationPresetEnum::ALPHA_DASH->label());
    }
}
