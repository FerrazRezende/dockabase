<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Database, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MigrationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Database $database;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_admin' => true]);
        $this->database = Database::factory()->create(['created_by' => $this->user->id]);
    }

    public function test_index_returns_migrations(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('system.databases.migrations.index', $this->database));

        $response->assertStatus(200);
    }

    public function test_show_sql_requires_authentication(): void
    {
        $migration = $this->database->migrations()->create([
            'batch' => 1,
            'name' => 'Test migration',
            'operation' => 'add_column',
            'table_name' => 'test_table',
            'sql_up' => 'SELECT 1',
            'sql_down' => 'SELECT 1',
        ]);

        $response = $this->get(route('system.databases.migrations.sql', [
            'database' => $this->database,
            'migration' => $migration,
        ]));

        $response->assertRedirect(route('login'));
    }
}
