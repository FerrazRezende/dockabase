<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Events\UserRemovedFromCredential;
use App\Models\Credential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserRemovedFromCredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_broadcasts_on_private_user_channel(): void
    {
        $user = User::factory()->create();
        $removedBy = User::factory()->create();
        $credential = Credential::factory()->create(['name' => 'Dev Team']);

        $event = new UserRemovedFromCredential(
            user: $user,
            credential: $credential,
            removedBy: $removedBy,
        );

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertSame('private-users.' . $user->id, $channels[0]->name);
    }

    public function test_broadcast_as_returns_correct_event_name(): void
    {
        $user = User::factory()->create();
        $removedBy = User::factory()->create();
        $credential = Credential::factory()->create();

        $event = new UserRemovedFromCredential(
            user: $user,
            credential: $credential,
            removedBy: $removedBy,
        );

        $this->assertSame('user.removed-from-credential', $event->broadcastAs());
    }

    public function test_broadcast_with_includes_credential_and_actor_data(): void
    {
        $user = User::factory()->create();
        $removedBy = User::factory()->create(['name' => 'Admin User']);
        $credential = Credential::factory()->create(['name' => 'Staging']);

        $event = new UserRemovedFromCredential(
            user: $user,
            credential: $credential,
            removedBy: $removedBy,
        );

        $payload = $event->broadcastWith();

        $this->assertSame($credential->id, $payload['credential_id']);
        $this->assertSame('Staging', $payload['credential_name']);
        $this->assertSame($removedBy->id, $payload['removed_by']['id']);
        $this->assertSame('Admin User', $payload['removed_by']['name']);
    }

    public function test_event_is_immutable(): void
    {
        $user = User::factory()->create();
        $removedBy = User::factory()->create();
        $credential = Credential::factory()->create();

        $event = new UserRemovedFromCredential(
            user: $user,
            credential: $credential,
            removedBy: $removedBy,
        );

        $this->assertObjectHasProperty('user', $event);
        $this->assertObjectHasProperty('credential', $event);
        $this->assertObjectHasProperty('removedBy', $event);
    }
}
