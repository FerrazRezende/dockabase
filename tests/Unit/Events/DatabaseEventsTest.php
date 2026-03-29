<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Events\DatabaseCreated;
use App\Events\DatabaseFailed;
use App\Events\DatabaseStepUpdated;
use App\Models\Database;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;

class DatabaseEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_step_updated_broadcasts_on_database_channel(): void
    {
        $database = Database::factory()->create();
        $event = new DatabaseStepUpdated($database, 'validating', 14);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertEquals('private-database.' . $database->id, $channels[0]->name);
    }

    public function test_database_step_updated_broadcasts_with_step_data(): void
    {
        $database = Database::factory()->create();
        $event = new DatabaseStepUpdated($database, 'creating', 28);

        $broadcastData = $event->broadcastWith();

        $this->assertEquals('creating', $broadcastData['step']);
        $this->assertEquals(28, $broadcastData['progress']);
        $this->assertArrayHasKey('database', $broadcastData);
    }

    public function test_database_created_broadcasts_on_database_channel(): void
    {
        $database = Database::factory()->create();
        $event = new DatabaseCreated($database);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertEquals('private-database.' . $database->id, $channels[0]->name);
    }

    public function test_database_failed_broadcasts_with_error(): void
    {
        $database = Database::factory()->create();
        $event = new DatabaseFailed($database, 'Connection refused');

        $broadcastData = $event->broadcastWith();

        $this->assertEquals('failed', $broadcastData['status']);
        $this->assertEquals('Connection refused', $broadcastData['error']);
    }
}
