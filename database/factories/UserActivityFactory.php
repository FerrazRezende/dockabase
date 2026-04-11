<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserActivityTypeEnum;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserActivityFactory extends Factory
{
    protected $model = UserActivity::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'activity_type' => UserActivityTypeEnum::StatusChanged,
            'from_status' => null,
            'to_status' => null,
            'metadata' => null,
        ];
    }

    public function statusChanged(): self
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => UserActivityTypeEnum::StatusChanged,
            'from_status' => 'online',
            'to_status' => 'away',
        ]);
    }

    public function databaseCreated(): self
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => UserActivityTypeEnum::DatabaseCreated,
            'metadata' => ['database_name' => $this->faker->word()],
        ]);
    }

    public function credentialCreated(): self
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => UserActivityTypeEnum::CredentialCreated,
            'metadata' => ['credential_name' => $this->faker->words(2, true)],
        ]);
    }
}
