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

        // Configure PostgreSQL for this test
        $this->configurePostgreSQL();

        $this->user = User::factory()->create(['is_admin' => true]);
        $this->database = Database::factory()->create(['created_by' => $this->user->id]);
    }

    protected function configurePostgreSQL(): void
    {
        // Setup PostgreSQL connection for tests
        config(['database.connections.pgsql' => [
            'driver' => 'pgsql',
            'host' => env('PG_HOST', '127.0.0.1'),
            'port' => env('PG_PORT', '5432'),
            'database' => env('PG_DATABASE', 'dockabase'),
            'username' => env('PG_USERNAME', 'dockabase'),
            'password' => env('PG_PASSWORD', 'secret'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ]]);
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

        // Use the actual database name that exists in PostgreSQL
        $this->database->database_name = 'dockabase';
        $this->database->save();

        // Setup dynamic connection like SchemaIntrospectionService does
        $tenantConnection = 'tenant_' . $this->database->id;
        $default = config('database.connections.pgsql');
        config(["database.connections.{$tenantConnection}" => [
            'driver' => 'pgsql',
            'host' => $default['host'],
            'port' => $default['port'],
            'database' => $this->database->database_name,
            'username' => $default['username'],
            'password' => $default['password'],
        ]]);

        // Create test schema and tables using the same connection the service uses
        \DB::connection($tenantConnection)->statement('CREATE SCHEMA IF NOT EXISTS test_schema');
        \DB::connection($tenantConnection)->statement('CREATE TABLE IF NOT EXISTS test_schema.users (id SERIAL PRIMARY KEY, name VARCHAR(255), email VARCHAR(255))');
        \DB::connection($tenantConnection)->statement("INSERT INTO test_schema.users (name, email) VALUES ('Test User', 'test@example.com')");

        $response = $this->actingAs($this->user)
            ->get(route('app.databases.schema', $this->database));

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['schemas']]);

        $json = $response->json();
        $this->assertNotEmpty($json['data']['schemas']);

        // Cleanup
        \DB::connection($tenantConnection)->statement('DROP SCHEMA IF EXISTS test_schema CASCADE');
    }

    public function test_table_data_returns_paginated_data(): void
    {
        try {
            \DB::connection('pgsql')->getPdo();
        } catch (\Exception $e) {
            $this->markTestSkipped('PostgreSQL connection not available');
        }

        // Use the actual database name
        $this->database->database_name = 'dockabase';
        $this->database->save();

        // Setup dynamic connection like SchemaIntrospectionService does
        $tenantConnection = 'tenant_' . $this->database->id;
        $default = config('database.connections.pgsql');
        config(["database.connections.{$tenantConnection}" => [
            'driver' => 'pgsql',
            'host' => $default['host'],
            'port' => $default['port'],
            'database' => $this->database->database_name,
            'username' => $default['username'],
            'password' => $default['password'],
        ]]);

        // Create test schema and table
        \DB::connection($tenantConnection)->statement('CREATE SCHEMA IF NOT EXISTS test_schema');
        \DB::connection($tenantConnection)->statement('CREATE TABLE IF NOT EXISTS test_schema.users (id SERIAL PRIMARY KEY, name VARCHAR(255), email VARCHAR(255))');
        \DB::connection($tenantConnection)->statement("INSERT INTO test_schema.users (name, email) VALUES ('Test User', 'test@example.com')");

        $response = $this->actingAs($this->user)
            ->get(route('app.databases.tables.data', [
                'database' => $this->database,
                'schema' => 'test_schema',
                'table' => 'users',
                'page' => 1,
                'per_page' => 50,
            ]));

        $response->assertStatus(200);

        // Cleanup
        \DB::connection($tenantConnection)->statement('DROP SCHEMA IF EXISTS test_schema CASCADE');
    }
}
