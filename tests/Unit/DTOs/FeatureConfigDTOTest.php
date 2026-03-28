<?php

namespace Tests\Unit\DTOs;

use App\DTOs\FeatureConfigDTO;
use App\Enums\RolloutStrategyEnum;
use PHPUnit\Framework\TestCase;

class FeatureConfigDTOTest extends TestCase
{
    public function test_can_create_dto_from_definition(): void
    {
        $dto = FeatureConfigDTO::fromDefinition(
            name: 'dynamic-api',
            definition: [
                'name' => 'Dynamic REST API',
                'description' => 'API REST auto-gerada',
                'default' => false,
            ]
        );

        $this->assertEquals('dynamic-api', $dto->name);
        $this->assertEquals('Dynamic REST API', $dto->displayName);
        $this->assertEquals('API REST auto-gerada', $dto->description);
        $this->assertFalse($dto->isActive);
        $this->assertEquals(RolloutStrategyEnum::Inactive, $dto->strategy);
    }

    public function test_can_create_dto_with_active_status(): void
    {
        $dto = FeatureConfigDTO::fromDefinition(
            name: 'realtime',
            definition: [
                'name' => 'Realtime',
                'description' => 'Websockets',
                'default' => false,
            ],
            isActive: true,
            strategy: RolloutStrategyEnum::Percentage,
            percentage: 25
        );

        $this->assertTrue($dto->isActive);
        $this->assertEquals(RolloutStrategyEnum::Percentage, $dto->strategy);
        $this->assertEquals(25, $dto->percentage);
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $dto = FeatureConfigDTO::fromDefinition(
            name: 'storage',
            definition: [
                'name' => 'Storage',
                'description' => 'File storage',
                'default' => false,
            ],
            isActive: true,
            strategy: RolloutStrategyEnum::All
        );

        $array = $dto->toArray();

        $this->assertEquals('storage', $array['name']);
        $this->assertEquals('Storage', $array['display_name']);
        $this->assertEquals('File storage', $array['description']);
        $this->assertTrue($array['is_active']);
        $this->assertEquals('all', $array['strategy']);
        $this->assertEquals('Todos', $array['strategy_label']);
    }
}
