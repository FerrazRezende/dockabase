<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Database;
use Illuminate\Database\Eloquent\Factories\Factory;

class DatabaseFactory extends Factory
{
    protected $model = Database::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'name' => $name,
            'display_name' => ucfirst($name),
            'description' => $this->faker->sentence(),
            'host' => 'localhost',
            'port' => 5432,
            'database_name' => 'dockabase_'.$name,
            'is_active' => true,
            'settings' => null,
        ];
    }
}
