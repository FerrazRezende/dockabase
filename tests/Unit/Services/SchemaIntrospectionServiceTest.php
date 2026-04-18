<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Database;
use App\Services\SchemaIntrospectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaIntrospectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private SchemaIntrospectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SchemaIntrospectionService::class);

        try {
            \DB::connection('pgsql')->getPdo();
        } catch (\Exception $e) {
            $this->markTestSkipped('PostgreSQL connection not available');
        }
    }

    public function test_get_schemas_excludes_system_schemas(): void
    {
        $database = Database::factory()->create();

        $schemas = $this->service->getSchemas($database);

        $this->assertIsArray($schemas);
        $this->assertNotContains('pg_catalog', $schemas);
        $this->assertNotContains('information_schema', $schemas);
        $this->assertNotContains('pg_toast', $schemas);
    }

    public function test_get_tables_returns_tables_for_schema(): void
    {
        $database = Database::factory()->create();

        $tables = $this->service->getTables($database, 'public');

        $this->assertIsArray($tables);
    }

    public function test_get_columns_returns_column_info(): void
    {
        $database = Database::factory()->create();

        $columns = $this->service->getColumns($database, 'public', 'users');

        $this->assertIsArray($columns);
    }
}
