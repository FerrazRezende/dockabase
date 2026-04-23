<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Database;
use App\Models\DatabaseTableMetadata;
use Illuminate\Database\Eloquent\Factories\Factory;

class DatabaseTableMetadataFactory extends Factory
{
    protected $model = DatabaseTableMetadata::class;

    public function definition(): array
    {
        return [
            'database_id' => Database::factory(),
            'schema_name' => 'public',
            'table_name' => fake()->unique()->word(),
            'columns' => [
                ['name' => 'id', 'type' => 'uuid', 'nullable' => false],
                ['name' => 'name', 'type' => 'varchar', 'nullable' => false],
            ],
            'validations' => [
                'name' => ['required' => true, 'max' => 255],
            ],
        ];
    }
}
