<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Jobs\CreateDatabaseJob;
use App\Models\Credential;
use App\Models\Database;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DatabaseControllerTest extends TestCase
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

    public function test_index_returns_databases_for_admin(): void
    {
        Database::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson(route('app.databases.index'));

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_index_forbidden_for_non_admin(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('app.databases.index'));

        $response->assertForbidden();
    }

    public function test_store_creates_database(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->admin)
            ->postJson(route('app.databases.store'), [
                'name' => 'dev',
                'display_name' => 'Development',
                'database_name' => 'dockabase_dev',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'dev');

        $this->assertDatabaseHas('databases', ['name' => 'dev']);
    }

    public function test_show_returns_database(): void
    {
        $database = Database::factory()->create();

        $response = $this->actingAs($this->admin)
            ->getJson(route('app.databases.show', $database));

        $response->assertOk()
            ->assertJsonPath('data.id', $database->id);
    }

    public function test_update_modifies_database(): void
    {
        $database = Database::factory()->create();

        $response = $this->actingAs($this->admin)
            ->patchJson(route('app.databases.update', $database), [
                'display_name' => 'Updated Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.display_name', 'Updated Name');
    }

    public function test_destroy_deletes_database(): void
    {
        $database = Database::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('app.databases.destroy', $database));

        $response->assertNoContent();

        $this->assertDatabaseMissing('databases', ['id' => $database->id]);
    }

    public function test_attach_credential(): void
    {
        $database = Database::factory()->create();
        $credential = Credential::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson(route('app.databases.credentials.attach', $database), [
                'credential_id' => $credential->id,
            ]);

        $response->assertOk();

        $this->assertTrue($database->fresh()->credentials->contains($credential));
    }

    public function test_detach_credential(): void
    {
        $database = Database::factory()->create();
        $credential = Credential::factory()->create();
        $database->credentials()->attach($credential);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('app.databases.credentials.detach', [$database, $credential]));

        $response->assertNoContent();

        $this->assertFalse($database->fresh()->credentials->contains($credential));
    }

    public function test_store_dispatches_create_database_job(): void
    {
        Queue::fake();

        $credential = Credential::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson(route('app.databases.store'), [
                'name' => 'testdb',
                'database_name' => 'testdb',
                'credential_ids' => [$credential->id],
            ]);

        $response->assertCreated();

        Queue::assertPushed(CreateDatabaseJob::class);
    }

    public function test_store_creates_database_with_pending_status(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->admin)
            ->postJson(route('app.databases.store'), [
                'name' => 'pending_db',
                'database_name' => 'pending_db',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('databases', [
            'name' => 'pending_db',
            'status' => 'pending',
        ]);
    }
}
