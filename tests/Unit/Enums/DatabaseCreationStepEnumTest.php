<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\DatabaseCreationStepEnum;
use Tests\TestCase;

class DatabaseCreationStepEnumTest extends TestCase
{
    public function test_has_seven_steps(): void
    {
        $this->assertCount(7, DatabaseCreationStepEnum::cases());
    }

    public function test_steps_are_in_correct_order(): void
    {
        $steps = DatabaseCreationStepEnum::cases();

        $this->assertEquals('validating', $steps[0]->value);
        $this->assertEquals('creating', $steps[1]->value);
        $this->assertEquals('configuring', $steps[2]->value);
        $this->assertEquals('migrating', $steps[3]->value);
        $this->assertEquals('permissions', $steps[4]->value);
        $this->assertEquals('testing', $steps[5]->value);
        $this->assertEquals('ready', $steps[6]->value);
    }

    public function test_label_returns_human_readable_string(): void
    {
        $this->assertEquals('Validando', DatabaseCreationStepEnum::VALIDATING->label());
        $this->assertEquals('Criando', DatabaseCreationStepEnum::CREATING->label());
        $this->assertEquals('Configurando', DatabaseCreationStepEnum::CONFIGURING->label());
        $this->assertEquals('Migrações', DatabaseCreationStepEnum::MIGRATING->label());
        $this->assertEquals('Permissões', DatabaseCreationStepEnum::PERMISSIONS->label());
        $this->assertEquals('Testando', DatabaseCreationStepEnum::TESTING->label());
        $this->assertEquals('Pronto', DatabaseCreationStepEnum::READY->label());
    }

    public function test_progress_percentage(): void
    {
        $this->assertEquals(14, DatabaseCreationStepEnum::VALIDATING->progress());
        $this->assertEquals(28, DatabaseCreationStepEnum::CREATING->progress());
        $this->assertEquals(42, DatabaseCreationStepEnum::CONFIGURING->progress());
        $this->assertEquals(56, DatabaseCreationStepEnum::MIGRATING->progress());
        $this->assertEquals(71, DatabaseCreationStepEnum::PERMISSIONS->progress());
        $this->assertEquals(85, DatabaseCreationStepEnum::TESTING->progress());
        $this->assertEquals(100, DatabaseCreationStepEnum::READY->progress());
    }
}
