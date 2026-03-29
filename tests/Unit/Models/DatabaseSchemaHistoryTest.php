<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Database;
use App\Models\DatabaseSchemaHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSchemaHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_schema_history(): void
    {
        $database = Database::factory()->create();
        $history = DatabaseSchemaHistory::create([
            'database_id' => $database->id,
            'action' => 'table_created',
            'table_name' => 'users',
            'new_value' => ['columns' => ['id', 'name']],
        ]);

        $this->assertEquals('table_created', $history->action);
        $this->assertEquals('users', $history->table_name);
    }

    public function test_belongs_to_database(): void
    {
        $database = Database::factory()->create();
        $history = DatabaseSchemaHistory::factory()->create(['database_id' => $database->id]);

        $this->assertTrue($history->database->is($database));
    }

    public function test_scope_by_action(): void
    {
        $database = Database::factory()->create();
        DatabaseSchemaHistory::factory()->create(['database_id' => $database->id, 'action' => 'table_created']);
        DatabaseSchemaHistory::factory()->create(['database_id' => $database->id, 'action' => 'column_added']);

        $results = DatabaseSchemaHistory::ofAction('table_created')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('table_created', $results->first()->action);
    }
}
