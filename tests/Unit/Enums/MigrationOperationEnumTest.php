<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\MigrationOperationEnum;
use Tests\TestCase;

class MigrationOperationEnumTest extends TestCase
{
    public function test_destructive_operations_correctly_identified(): void
    {
        $this->assertTrue(MigrationOperationEnum::DROP_TABLE->isDestructive());
        $this->assertTrue(MigrationOperationEnum::DROP_COLUMN->isDestructive());
        $this->assertFalse(MigrationOperationEnum::ADD_COLUMN->isDestructive());
        $this->assertFalse(MigrationOperationEnum::ADD_INDEX->isDestructive());
    }

    public function test_label_returns_human_readable(): void
    {
        $this->assertEquals('Add Column', MigrationOperationEnum::ADD_COLUMN->label());
        $this->assertEquals('Drop Table', MigrationOperationEnum::DROP_TABLE->label());
    }
}
