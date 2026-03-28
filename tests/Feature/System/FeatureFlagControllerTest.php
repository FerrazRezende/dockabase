<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FeatureFlagControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
    }

    public function test_index_returns_all_features_for_admin(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('system.features.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'display_name',
                        'description',
                        'is_active',
                        'strategy',
                        'strategy_label',
                        'percentage',
                    ],
                ],
            ]);

        $this->assertCount(8, $response->json('data'));
    }

    public function test_index_forbidden_for_non_admin(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('system.features.index'));

        $response->assertForbidden();
    }

    public function test_index_unauthenticated_returns_401(): void
    {
        $response = $this->getJson(route('system.features.index'));

        $response->assertUnauthorized();
    }
}
