<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\Credential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CredentialControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
    }

    public function test_index_returns_credentials_for_admin(): void
    {
        Credential::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson(route('app.credentials.index'));

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_index_forbidden_for_non_admin(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('app.credentials.index'));

        $response->assertForbidden();
    }

    public function test_store_creates_credential(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson(route('app.credentials.store'), [
                'name' => 'Dev Team',
                'permission' => 'read-write',
                'user_ids' => [$user->id],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Dev Team');

        $this->assertDatabaseHas('credentials', ['name' => 'Dev Team']);
    }

    public function test_show_returns_credential(): void
    {
        $credential = Credential::factory()->create();

        $response = $this->actingAs($this->admin)
            ->getJson(route('app.credentials.show', $credential));

        $response->assertOk()
            ->assertJsonPath('data.id', $credential->id);
    }

    public function test_update_modifies_credential(): void
    {
        $credential = Credential::factory()->create();

        $response = $this->actingAs($this->admin)
            ->patchJson(route('app.credentials.update', $credential), [
                'name' => 'Updated Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_destroy_deletes_credential(): void
    {
        $credential = Credential::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('app.credentials.destroy', $credential));

        $response->assertNoContent();

        $this->assertDatabaseMissing('credentials', ['id' => $credential->id]);
    }

    public function test_attach_user(): void
    {
        $credential = Credential::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson(route('app.credentials.users.attach', $credential), [
                'user_id' => $user->id,
            ]);

        $response->assertOk();

        $this->assertTrue($credential->fresh()->users->contains($user));
    }

    public function test_detach_user(): void
    {
        $credential = Credential::factory()->create();
        $user = User::factory()->create();
        $credential->users()->attach($user);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('app.credentials.users.detach', [$credential, $user]));

        $response->assertNoContent();

        $this->assertFalse($credential->fresh()->users->contains($user));
    }
}
