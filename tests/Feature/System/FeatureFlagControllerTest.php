<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureFlagControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // God Admin - uses is_admin boolean
        $this->admin = User::factory()->create(['is_admin' => true]);

        // Regular user
        $this->user = User::factory()->create(['is_admin' => false]);
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

        $this->assertCount(2, $response->json('data'));
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

    public function test_show_returns_feature_details(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('system.features.show', 'database-creator'));

        $response->assertOk()
            ->assertJsonPath('data.name', 'database-creator')
            ->assertJsonPath('data.display_name', 'Database Creator');
    }

    public function test_show_returns_404_for_unknown_feature(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('system.features.show', 'unknown-feature'));

        $response->assertNotFound();
    }

    public function test_activate_enables_feature(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('system.features.activate', 'database-creator'), [
                'strategy' => 'all',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.strategy', 'all');

        $this->assertDatabaseHas('feature_settings', [
            'feature_name' => 'database-creator',
            'is_active' => true,
        ]);
    }

    public function test_activate_with_percentage(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('system.features.activate', 'database-creator'), [
                'strategy' => 'percentage',
                'percentage' => 25,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.strategy', 'percentage')
            ->assertJsonPath('data.percentage', 25);
    }

    public function test_activate_with_users(): void
    {
        $userIds = ['user-1', 'user-2', 'user-3'];

        $response = $this->actingAs($this->admin)
            ->postJson(route('system.features.activate', 'credentials-manager'), [
                'strategy' => 'users',
                'user_ids' => $userIds,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.strategy', 'users');
    }

    public function test_activate_requires_strategy(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('system.features.activate', 'database-creator'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['strategy']);
    }

    public function test_activate_percentage_requires_percentage_value(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('system.features.activate', 'database-creator'), [
                'strategy' => 'percentage',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['percentage']);
    }

    public function test_deactivate_disables_feature(): void
    {
        // Activate first
        $this->actingAs($this->admin)
            ->postJson(route('system.features.activate', 'database-creator'), ['strategy' => 'all']);

        $response = $this->actingAs($this->admin)
            ->postJson(route('system.features.deactivate', 'database-creator'));

        $response->assertOk()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.strategy', 'inactive');
    }

    public function test_update_changes_percentage(): void
    {
        // Activate first with percentage
        $this->actingAs($this->admin)
            ->postJson(route('system.features.activate', 'database-creator'), [
                'strategy' => 'percentage',
                'percentage' => 25,
            ]);

        $response = $this->actingAs($this->admin)
            ->patchJson(route('system.features.update', 'database-creator'), [
                'percentage' => 75,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.percentage', 75);
    }

    public function test_history_returns_changes(): void
    {
        // Make some changes
        $this->actingAs($this->admin)
            ->postJson(route('system.features.activate', 'database-creator'), ['strategy' => 'all']);

        $response = $this->actingAs($this->admin)
            ->getJson(route('system.features.history', 'database-creator'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'action', 'actor', 'created_at'],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));
    }

    public function test_non_admin_cannot_activate(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('system.features.activate', 'database-creator'), ['strategy' => 'all']);

        $response->assertForbidden();
    }

    public function test_non_admin_cannot_deactivate(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('system.features.deactivate', 'database-creator'));

        $response->assertForbidden();
    }

    public function test_add_user_to_feature(): void
    {
        // Activate with users strategy
        $this->actingAs($this->admin)
            ->postJson(route('system.features.activate', 'credentials-manager'), [
                'strategy' => 'users',
                'user_ids' => ['user-1'],
            ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('system.features.users.add', 'credentials-manager'), [
                'user_id' => 'user-2',
            ]);

        $response->assertOk();
    }

    public function test_remove_user_from_feature(): void
    {
        // Activate with users strategy
        $this->actingAs($this->admin)
            ->postJson(route('system.features.activate', 'credentials-manager'), [
                'strategy' => 'users',
                'user_ids' => ['user-1', 'user-2'],
            ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('system.features.users.remove', ['feature' => 'credentials-manager', 'userId' => 'user-1']));

        $response->assertOk();
    }
}
