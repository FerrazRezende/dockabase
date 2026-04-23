<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\ValidationPresetEnum;
use App\Services\ValidationRuleMapper;
use Tests\TestCase;

class ValidationRuleMapperTest extends TestCase
{
    private ValidationRuleMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = app(ValidationRuleMapper::class);
    }

    public function test_to_json_rules_converts_ui_input_to_json(): void
    {
        $input = [
            'name' => [
                ['preset' => 'required', 'enabled' => true],
                ['preset' => 'min_length', 'enabled' => true, 'value' => 3],
                ['preset' => 'max_length', 'enabled' => true, 'value' => 255],
            ],
            'price' => [
                ['preset' => 'required', 'enabled' => true],
                ['preset' => 'min_value', 'enabled' => true, 'value' => 0],
            ],
        ];

        $result = $this->mapper->toJsonRules($input);

        $this->assertEquals([
            'name' => ['required' => true, 'min' => 3, 'max' => 255],
            'price' => ['required' => true, 'min' => 0],
        ], $result);
    }

    public function test_to_laravel_rules_converts_json_to_laravel_rules(): void
    {
        $jsonRules = [
            'name' => ['required' => true, 'min' => 3, 'max' => 255, 'alpha' => true],
            'price' => ['required' => true, 'min' => 0, 'numeric' => true],
        ];

        $result = $this->mapper->toLaravelRules($jsonRules);

        $this->assertEquals([
            'name' => ['required', 'min:3', 'max:255', 'alpha'],
            'price' => ['required', 'min:0', 'numeric'],
        ], $result);
    }

    public function test_get_applicable_presets_filters_by_type(): void
    {
        $presets = $this->mapper->getApplicablePresets('varchar');

        $this->assertContains(ValidationPresetEnum::REQUIRED, $presets);
        $this->assertContains(ValidationPresetEnum::MIN_LENGTH, $presets);
        $this->assertContains(ValidationPresetEnum::EMAIL, $presets);
    }
}
