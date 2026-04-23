<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\MigrationGeneratorService;
use Tests\TestCase;

class MigrationGeneratorServiceTest extends TestCase
{
    private MigrationGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MigrationGeneratorService::class);
    }

    public function test_generate_create_table_produces_valid_sql(): void
    {
        $columns = [
            ['name' => 'id', 'type_definition' => 'uuid', 'nullable' => false, 'is_primary_key' => true],
            ['name' => 'name', 'type_definition' => 'varchar(255)', 'nullable' => false],
            ['name' => 'email', 'type_definition' => 'varchar(255)', 'nullable' => true],
        ];

        $result = $this->service->generateCreateTable('public', 'users', $columns);

        $this->assertIsArray($result);
        $this->assertStringContainsString('CREATE TABLE "public"."users"', $result['sql_up']);
        $this->assertStringContainsString('"id" uuid NOT NULL', $result['sql_up']);
        $this->assertStringContainsString('DROP TABLE IF EXISTS "public"."users"', $result['sql_down']);
        $this->assertEquals('add_column', $result['operation']);
    }

    public function test_generate_drop_table_includes_all_columns_in_down(): void
    {
        $existingColumns = [
            ['name' => 'id', 'type' => 'uuid', 'nullable' => false],
            ['name' => 'name', 'type' => 'varchar', 'nullable' => false],
        ];

        $result = $this->service->generateDropTable('public', 'users', $existingColumns);

        $this->assertStringContainsString('DROP TABLE IF EXISTS "public"."users"', $result['sql_up']);
        $this->assertStringContainsString('CREATE TABLE "public"."users"', $result['sql_down']);
    }

    public function test_generate_add_column(): void
    {
        $column = ['name' => 'status', 'type_definition' => 'varchar(50)', 'nullable' => true];

        $result = $this->service->generateAddColumn('public', 'users', $column);

        $this->assertStringContainsString('ALTER TABLE "public"."users" ADD COLUMN', $result['sql_up']);
        $this->assertStringContainsString('"status" varchar(50)', $result['sql_up']);
        $this->assertStringContainsString('ALTER TABLE "public"."users" DROP COLUMN status', $result['sql_down']);
    }

    public function test_generate_drop_column(): void
    {
        $result = $this->service->generateDropColumn('public', 'users', 'old_column', 'varchar(255)');

        $this->assertStringContainsString('ALTER TABLE "public"."users" DROP COLUMN old_column', $result['sql_up']);
        $this->assertStringContainsString('ALTER TABLE "public"."users" ADD COLUMN "old_column" varchar(255)', $result['sql_down']);
    }

    public function test_generate_alter_column_type(): void
    {
        $result = $this->service->generateAlterColumnType('public', 'users', 'age', 'integer', 'bigint');

        $this->assertStringContainsString('ALTER TABLE "public"."users" ALTER COLUMN "age" TYPE bigint', $result['sql_up']);
        $this->assertStringContainsString('ALTER TABLE "public"."users" ALTER COLUMN "age" TYPE integer', $result['sql_down']);
    }

    public function test_generate_rename_column(): void
    {
        $result = $this->service->generateRenameColumn('public', 'users', 'old_name', 'new_name');

        $this->assertStringContainsString('ALTER TABLE "public"."users" RENAME COLUMN "old_name" TO "new_name"', $result['sql_up']);
        $this->assertStringContainsString('ALTER TABLE "public"."users" RENAME COLUMN "new_name" TO "old_name"', $result['sql_down']);
    }
}
