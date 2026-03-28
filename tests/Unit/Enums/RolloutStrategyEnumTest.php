<?php

namespace Tests\Unit\Enums;

use App\Enums\RolloutStrategyEnum;
use PHPUnit\Framework\TestCase;

class RolloutStrategyEnumTest extends TestCase
{
    public function test_has_all_required_cases(): void
    {
        $cases = RolloutStrategyEnum::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(RolloutStrategyEnum::Inactive, $cases);
        $this->assertContains(RolloutStrategyEnum::Percentage, $cases);
        $this->assertContains(RolloutStrategyEnum::Users, $cases);
        $this->assertContains(RolloutStrategyEnum::All, $cases);
    }

    public function test_labels_are_correct(): void
    {
        $this->assertEquals('Inativo', RolloutStrategyEnum::Inactive->label());
        $this->assertEquals('Percentual', RolloutStrategyEnum::Percentage->label());
        $this->assertEquals('Usuários Específicos', RolloutStrategyEnum::Users->label());
        $this->assertEquals('Todos', RolloutStrategyEnum::All->label());
    }

    public function test_is_active_returns_correct_boolean(): void
    {
        $this->assertFalse(RolloutStrategyEnum::Inactive->isActive());
        $this->assertTrue(RolloutStrategyEnum::Percentage->isActive());
        $this->assertTrue(RolloutStrategyEnum::Users->isActive());
        $this->assertTrue(RolloutStrategyEnum::All->isActive());
    }
}
