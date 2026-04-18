<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Database;
use App\Models\SystemMigration;
use App\Enums\MigrationOperationEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

class SystemMigrationFactory extends Factory
{
    protected $model = SystemMigration::class;

    public function definition(): array
    {
        $operation = fake()->randomElement(MigrationOperationEnum::cases());

        return [
            'database_id' => Database::factory(),
            'batch' => fake()->numberBetween(1, 100),
            'name' => fake()->sentence(3),
            'operation' => $operation->value,
            'table_name' => fake()->word(),
            'schema_name' => 'public',
            'sql_up' => '-- SQL up',
            'sql_down' => '-- SQL down',
            'status' => 'pending',
            'error_message' => null,
            'executed_at' => null,
        ];
    }
}
