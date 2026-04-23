<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\{Database, SystemMigration};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_executed_updates_status_and_executed_at(): void
    {
        $migration = SystemMigration::factory()->create(['status' => 'pending']);

        $migration->markExecuted();

        $this->assertEquals('executed', $migration->status);
        $this->assertNotNull($migration->executed_at);
    }

    public function test_mark_failed_updates_status_and_error_message(): void
    {
        $migration = SystemMigration::factory()->create(['status' => 'pending']);

        $migration->markFailed('Connection lost');

        $this->assertEquals('failed', $migration->status);
        $this->assertEquals('Connection lost', $migration->error_message);
    }

    public function test_mark_rolled_back_updates_status(): void
    {
        $migration = SystemMigration::factory()->create(['status' => 'executed']);

        $migration->markRolledBack();

        $this->assertEquals('rolled_back', $migration->status);
    }

    public function test_of_status_scope_filters_correctly(): void
    {
        SystemMigration::factory()->create(['status' => 'pending']);
        SystemMigration::factory()->create(['status' => 'executed']);

        $results = SystemMigration::ofStatus('executed')->get();
        $this->assertCount(1, $results);
    }

    public function test_executed_at_is_cast_to_datetime(): void
    {
        $migration = SystemMigration::factory()->create([
            'status' => 'executed',
            'executed_at' => '2024-01-01 12:00:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $migration->executed_at);
    }
}
