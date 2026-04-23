<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Database;
use App\Services\MigrationExecutorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MigrationExecutorServiceTest extends TestCase
{
    use RefreshDatabase;

    private MigrationExecutorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MigrationExecutorService::class);

        try {
            \DB::connection('pgsql')->getPdo();
        } catch (\Exception $e) {
            $this->markTestSkipped('PostgreSQL connection not available');
        }
    }

    public function test_execute_runs_sql_on_database(): void
    {
        $database = Database::factory()->create();

        $sql = 'SELECT 1';

        $this->expectNotToPerformAssertions();

        $this->service->execute($database, $sql);
    }

    public function test_execute_throws_on_invalid_sql(): void
    {
        $database = Database::factory()->create();

        $sql = 'INVALID SQL';

        $this->expectException(\Exception::class);

        $this->service->execute($database, $sql);
    }

    public function test_test_connection_returns_true_for_valid_database(): void
    {
        $database = Database::factory()->create();

        $result = $this->service->testConnection($database);

        $this->assertTrue($result);
    }
}
