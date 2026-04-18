<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\{Database, DatabaseTableMetadata};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseTableMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_has_ksuid_trait(): void
    {
        $metadata = DatabaseTableMetadata::factory()->create();
        $this->assertEquals(27, strlen($metadata->id));
    }

    public function test_of_database_scope_filters_correctly(): void
    {
        $database1 = Database::factory()->create();
        $database2 = Database::factory()->create();

        DatabaseTableMetadata::factory()->for($database1)->create(['table_name' => 'users']);
        DatabaseTableMetadata::factory()->for($database2)->create(['table_name' => 'products']);

        $results = DatabaseTableMetadata::ofDatabase($database1->id)->get();
        $this->assertCount(1, $results);
        $this->assertEquals('users', $results->first()->table_name);
    }

    public function test_of_schema_scope_filters_correctly(): void
    {
        $database = Database::factory()->create();

        DatabaseTableMetadata::factory()->for($database)->create(['schema_name' => 'public', 'table_name' => 'users']);
        DatabaseTableMetadata::factory()->for($database)->create(['schema_name' => 'analytics', 'table_name' => 'events']);

        $results = DatabaseTableMetadata::ofSchema('public')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('users', $results->first()->table_name);
    }

    public function test_columns_and_validations_are_cast_to_array(): void
    {
        $database = Database::factory()->create();
        $metadata = DatabaseTableMetadata::factory()->for($database)->create([
            'columns' => [['name' => 'id', 'type' => 'uuid']],
            'validations' => ['id' => ['required' => true]],
        ]);

        $this->assertIsArray($metadata->columns);
        $this->assertIsArray($metadata->validations);
        $this->assertEquals('id', $metadata->columns[0]['name']);
    }
}
