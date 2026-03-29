<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Database;
use App\Models\DatabaseSchemaHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class DatabaseSchemaHistoryFactory extends Factory
{
    protected $model = DatabaseSchemaHistory::class;

    public function definition(): array
    {
        return [
            'database_id' => Database::factory(),
            'action' => 'table_created',
            'table_name' => $this->faker->word(),
            'column_name' => null,
            'old_value' => null,
            'new_value' => null,
            'user_id' => null,
        ];
    }
}
