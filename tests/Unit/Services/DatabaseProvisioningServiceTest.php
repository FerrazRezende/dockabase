<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\DatabaseCreationStepEnum;
use App\Models\Database;
use App\Services\DatabaseProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseProvisioningServiceTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseProvisioningService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DatabaseProvisioningService::class);
    }

    public function test_validate_step_validates_schema(): void
    {
        $database = Database::factory()->create([
            'database_name' => 'valid_name',
        ]);

        $result = $this->service->validateStep($database);

        $this->assertTrue($result);
    }

    public function test_validate_step_rejects_invalid_name(): void
    {
        $database = Database::factory()->create([
            'database_name' => 'invalid-name-with-dash',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->validateStep($database);
    }

    public function test_get_steps_returns_ordered_steps(): void
    {
        $steps = $this->service->getSteps();

        $this->assertCount(7, $steps);
        $this->assertEquals(DatabaseCreationStepEnum::VALIDATING, $steps[0]);
        $this->assertEquals(DatabaseCreationStepEnum::READY, $steps[6]);
    }
}
