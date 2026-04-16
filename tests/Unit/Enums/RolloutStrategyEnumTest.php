<?php

namespace Tests\Unit\Enums;

use App\Enums\RolloutStrategyEnum;
use Tests\TestCase;

class RolloutStrategyEnumTest extends TestCase
{
    public function test_has_all_required_cases(): void
    {
        $cases = RolloutStrategyEnum::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(RolloutStrategyEnum::INACTIVE, $cases);
        $this->assertContains(RolloutStrategyEnum::PERCENTAGE, $cases);
        $this->assertContains(RolloutStrategyEnum::USERS, $cases);
        $this->assertContains(RolloutStrategyEnum::ALL, $cases);
    }

    public function test_labels_are_correct(): void
    {
        $this->assertEquals('Inactive', RolloutStrategyEnum::INACTIVE->label());
        $this->assertEquals('Percentage', RolloutStrategyEnum::PERCENTAGE->label());
        $this->assertEquals('Specific Users', RolloutStrategyEnum::USERS->label());
        $this->assertEquals('All', RolloutStrategyEnum::ALL->label());
    }

    public function test_is_active_returns_correct_boolean(): void
    {
        $this->assertFalse(RolloutStrategyEnum::INACTIVE->isActive());
        $this->assertTrue(RolloutStrategyEnum::PERCENTAGE->isActive());
        $this->assertTrue(RolloutStrategyEnum::USERS->isActive());
        $this->assertTrue(RolloutStrategyEnum::ALL->isActive());
    }
}
