<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CredentialPermissionEnum;
use App\Models\Credential;
use Illuminate\Database\Eloquent\Factories\Factory;

class CredentialFactory extends Factory
{
    protected $model = Credential::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true).' Team',
            'permission' => CredentialPermissionEnum::ReadWrite,
            'description' => $this->faker->sentence(),
        ];
    }
}
