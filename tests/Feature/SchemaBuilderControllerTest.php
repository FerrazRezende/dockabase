<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Database, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaBuilderControllerTest extends TestCase
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

    public function test_index_requires_authentication(): void
    {
        $response = $this->get(route('app.databases.schema', $this->database));

        $response->assertRedirect(route('login'));
    }

    public function test_index_returns_schema_tree(): void
    {
        try {
            \DB::connection('pgsql')->getPdo();
        } catch (\Exception $e) {
            $this->markTestSkipped('PostgreSQL connection not available');
        }

        $response = $this->actingAs($this->user)
            ->get(route('app.databases.schema', $this->database));

        $response->assertStatus(200);
        $response->assertJsonStructure(['schemas']);
    }

    public function test_table_data_returns_paginated_data(): void
    {
        try {
            \DB::connection('pgsql')->getPdo();
        } catch (\Exception $e) {
            $this->markTestSkipped('PostgreSQL connection not available');
        }

        $response = $this->actingAs($this->user)
            ->get(route('app.databases.tables.data', [
                'database' => $this->database,
                'schema' => 'public',
                'table' => 'users',
                'page' => 1,
                'per_page' => 50,
            ]));

        $response->assertStatus(200);
    }
}
