<?php

declare(strict_types=1);

namespace Tests\Feature\SchemaBuilder;

use App\Models\Database;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTableTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Database $database;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);

        $this->database = Database::factory()->create([
            'status' => 'ready',
        ]);
    }

    public function test_can_create_table_with_columns(): void
    {
        try {
            \DB::connection('pgsql')->getPdo();
        } catch (\Exception $e) {
            $this->markTestSkipped('PostgreSQL connection not available');
        }

        $response = $this->actingAs($this->admin)
            ->postJson(route('app.databases.tables.store', $this->database), [
                'name' => 'products',
                'schema' => 'public',
                'columns' => [
                    [
                        'name' => 'id',
                        'type' => 'uuid',
                        'nullable' => false,
                        'default_value' => 'gen_random_uuid()',
                        'is_primary_key' => true,
                    ],
                    [
                        'name' => 'name',
                        'type' => 'varchar',
                        'nullable' => false,
                        'length' => 255,
                    ],
                    [
                        'name' => 'price',
                        'type' => 'decimal',
                        'nullable' => false,
                    ],
                ],
            ]);

        $response->assertRedirect();

        // Metadata was stored
        $this->assertDatabaseHas('database_table_metadata', [
            'database_id' => $this->database->id,
            'schema_name' => 'public',
            'table_name' => 'products',
        ]);

        // Migration was recorded
        $this->assertDatabaseHas('system_migrations', [
            'database_id' => $this->database->id,
            'table_name' => 'products',
            'status' => 'executed',
        ]);
    }

    public function test_can_create_table_with_validations(): void
    {
        try {
            \DB::connection('pgsql')->getPdo();
        } catch (\Exception $e) {
            $this->markTestSkipped('PostgreSQL connection not available');
        }

        $response = $this->actingAs($this->admin)
            ->postJson(route('app.databases.tables.store', $this->database), [
                'name' => 'orders',
                'schema' => 'public',
                'columns' => [
                    ['name' => 'id', 'type' => 'uuid', 'is_primary_key' => true],
                    ['name' => 'total', 'type' => 'decimal', 'nullable' => false],
                ],
                'validations' => [
                    'total' => [
                        'required' => true,
                        'min_value' => '0',
                    ],
                ],
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('database_table_metadata', [
            'database_id' => $this->database->id,
            'table_name' => 'orders',
        ]);

        $metadata = $this->database->tableMetadata()->where('table_name', 'orders')->first();
        $this->assertNotNull($metadata);
        $this->assertEquals([
            'total' => [
                'required' => true,
                'min_value' => '0',
            ],
        ], $metadata->validations);
    }

    public function test_validates_table_name(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('app.databases.tables.store', $this->database), [
                'name' => 'INVALID-NAME',
                'schema' => 'public',
                'columns' => [
                    ['name' => 'id', 'type' => 'uuid'],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    public function test_validates_columns_required(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('app.databases.tables.store', $this->database), [
                'name' => 'test',
                'schema' => 'public',
                'columns' => [],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('columns');
    }

    public function test_validates_column_type(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('app.databases.tables.store', $this->database), [
                'name' => 'test',
                'schema' => 'public',
                'columns' => [
                    ['name' => 'id', 'type' => 'invalid_type'],
                ],
            ]);

        $response->assertStatus(422);
    }
}
