<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\ValidationPresetEnum;
use App\Services\ValidationRuleMapper;
use Tests\TestCase;

class ValidationRuleMapperTest extends TestCase
{
    public function test_to_laravel_rules_converts_json_to_rules(): void
    {
        $validations = [
            'name' => [
                'required' => true,
                'min_length' => '3',
                'max_length' => '255',
                'alpha' => true,
            ],
            'price' => [
                'required' => true,
                'min_value' => '0',
                'max_value' => '999999.99',
            ],
        ];

        $rules = ValidationRuleMapper::toLaravelRules($validations);

        $this->assertEquals([
            'name' => ['required', 'min:3', 'max:255', 'alpha'],
            'price' => ['required', 'min:0', 'max:999999.99'],
        ], $rules);
    }

    public function test_to_laravel_rules_skips_disabled_presets(): void
    {
        $validations = [
            'name' => [
                'required' => true,
                'min_length' => false,
                'alpha' => null,
            ],
        ];

        $rules = ValidationRuleMapper::toLaravelRules($validations);

        $this->assertEquals(['name' => ['required']], $rules);
    }

    public function test_to_laravel_rules_handles_unique_with_auto_table(): void
    {
        $validations = [
            'email' => [
                'required' => true,
                'unique' => true,
            ],
        ];

        $rules = ValidationRuleMapper::toLaravelRules($validations, 'users');

        $this->assertContains('unique:users,email', $rules['email']);
    }

    public function test_to_laravel_rules_handles_exists(): void
    {
        $validations = [
            'category_id' => [
                'exists' => 'categories,id',
            ],
        ];

        $rules = ValidationRuleMapper::toLaravelRules($validations);

        $this->assertEquals(['category_id' => ['exists:categories,id']], $rules);
    }

    public function test_to_laravel_rules_handles_empty_validations(): void
    {
        $this->assertEquals([], ValidationRuleMapper::toLaravelRules([]));
    }

    public function test_get_presets_for_category_returns_text_presets(): void
    {
        $presets = ValidationRuleMapper::getPresetsForCategory('text');

        $names = array_map(fn (ValidationPresetEnum $p) => $p->value, $presets);

        $this->assertContains('required', $names);
        $this->assertContains('min_length', $names);
        $this->assertContains('max_length', $names);
        $this->assertContains('email', $names);
        $this->assertContains('alpha', $names);
        $this->assertNotContains('min_value', $names);
        $this->assertNotContains('boolean', $names);
        $this->assertNotContains('date', $names);
    }

    public function test_get_presets_for_category_returns_numeric_presets(): void
    {
        $presets = ValidationRuleMapper::getPresetsForCategory('numeric');
        $names = array_map(fn (ValidationPresetEnum $p) => $p->value, $presets);

        $this->assertContains('required', $names);
        $this->assertContains('min_value', $names);
        $this->assertContains('max_value', $names);
        $this->assertContains('integer', $names);
        $this->assertNotContains('min_length', $names);
        $this->assertNotContains('email', $names);
    }

    public function test_get_presets_for_category_returns_boolean_presets(): void
    {
        $presets = ValidationRuleMapper::getPresetsForCategory('boolean');
        $names = array_map(fn (ValidationPresetEnum $p) => $p->value, $presets);

        $this->assertContains('required', $names);
        $this->assertContains('boolean', $names);
        $this->assertNotContains('min_length', $names);
    }
}
