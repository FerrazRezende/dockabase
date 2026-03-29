<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\DatabaseCreationStepEnum;
use App\Events\DatabaseCreated;
use App\Events\DatabaseStepUpdated;
use App\Jobs\CreateDatabaseJob;
use App\Models\Database;
use App\Services\DatabaseProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateDatabaseJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_updates_database_status_to_ready(): void
    {
        $database = Database::factory()->create(['status' => 'pending']);

        // Mock the service to avoid actual database creation
        $service = $this->createMock(DatabaseProvisioningService::class);
        $service->method('getSteps')->willReturn(DatabaseCreationStepEnum::cases());
        $service->method('validateStep')->willReturn(true);
        $service->method('createDatabase')->willReturn(true);
        $service->method('configureExtensions')->willReturn(true);
        $service->method('runMigrations')->willReturn(true);
        $service->method('configurePermissions')->willReturn(true);
        $service->method('testConnection')->willReturn(true);

        $job = new CreateDatabaseJob($database);
        $job->handle($service);

        $database->refresh();

        $this->assertEquals('ready', $database->status);
        $this->assertEquals(100, $database->progress);
        $this->assertEquals(DatabaseCreationStepEnum::READY->value, $database->current_step);
    }

    public function test_job_broadcasts_step_updates(): void
    {
        Event::fake([DatabaseStepUpdated::class, DatabaseCreated::class]);

        $database = Database::factory()->create(['status' => 'pending']);

        // Mock the service to avoid actual database creation
        $service = $this->createMock(DatabaseProvisioningService::class);
        $service->method('getSteps')->willReturn(DatabaseCreationStepEnum::cases());
        $service->method('validateStep')->willReturn(true);
        $service->method('createDatabase')->willReturn(true);
        $service->method('configureExtensions')->willReturn(true);
        $service->method('runMigrations')->willReturn(true);
        $service->method('configurePermissions')->willReturn(true);
        $service->method('testConnection')->willReturn(true);

        $job = new CreateDatabaseJob($database);
        $job->handle($service);

        Event::assertDispatchedTimes(DatabaseStepUpdated::class, 7);
        Event::assertDispatched(DatabaseCreated::class);
    }

    public function test_job_handles_failure_gracefully(): void
    {
        $database = Database::factory()->create([
            'status' => 'pending',
            'database_name' => 'invalid-name!',
        ]);

        $job = new CreateDatabaseJob($database);

        try {
            $job->handle(app(DatabaseProvisioningService::class));
        } catch (\Exception $e) {
            // Expected
        }

        $database->refresh();

        $this->assertEquals('failed', $database->status);
        $this->assertNotNull($database->error_message);
    }
}
