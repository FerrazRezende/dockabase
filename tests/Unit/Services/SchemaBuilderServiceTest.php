<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\SchemaBuilderService;
use Tests\TestCase;

class SchemaBuilderServiceTest extends TestCase
{
    private SchemaBuilderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SchemaBuilderService::class);
    }

    public function test_validate_table_name_accepts_valid_names(): void
    {
        $this->expectNotToPerformAssertions();

        $this->service->validateTableName('users');
        $this->service->validateTableName('user_profiles');
        $this->service->validateTableName('orders123');
    }

    public function test_validate_table_name_rejects_reserved_prefixes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('reserved prefix');

        $this->service->validateTableName('pg_temp_users');
    }

    public function test_validate_table_name_rejects_system_prefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('reserved prefix');

        $this->service->validateTableName('system_config');
    }

    public function test_validate_table_name_rejects_invalid_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid table name');

        $this->service->validateTableName('user-tables');
    }

    public function test_validate_column_name_rejects_invalid_names(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->validateColumnName('order-items');
    }

    public function test_build_column_definitions_normalizes_columns(): void
    {
        $input = [
            ['name' => 'id', 'type' => 'uuid', 'nullable' => false],
            ['name' => 'name', 'type' => 'varchar', 'nullable' => false, 'length' => 255],
            ['name' => 'price', 'type' => 'decimal', 'nullable' => true],
        ];

        $result = $this->service->buildColumnDefinitions($input);

        $this->assertCount(3, $result);
        $this->assertEquals('id', $result[0]['name']);
        $this->assertEquals('uuid', $result[0]['type']);
        $this->assertEquals('varchar(255)', $result[1]['type_definition']);
    }

    public function test_prepare_table_metadata_formats_metadata(): void
    {
        $columns = [
            ['name' => 'id', 'type' => 'uuid', 'nullable' => false],
            ['name' => 'name', 'type' => 'varchar', 'nullable' => false, 'length' => 255],
        ];

        $validations = [
            'name' => ['required' => true, 'max' => 255],
        ];

        $result = $this->service->prepareTableMetadata($columns, $validations);

        $this->assertIsArray($result['columns']);
        $this->assertEquals($validations, $result['validations']);
    }
}
