<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Enums\CredentialPermissionEnum;
use App\Events\UserAddedToCredential;
use App\Models\Credential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserAddedToCredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_broadcasts_on_private_user_channel(): void
    {
        $user = User::factory()->create();
        $addedBy = User::factory()->create();
        $credential = Credential::factory()->create([
            'name' => 'Dev Team',
            'permission' => CredentialPermissionEnum::READ_WRITE,
        ]);

        $event = new UserAddedToCredential(
            user: $user,
            credential: $credential,
            addedBy: $addedBy,
        );

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertSame('private-users.' . $user->id, $channels[0]->name);
    }

    public function test_broadcast_as_returns_correct_event_name(): void
    {
        $user = User::factory()->create();
        $addedBy = User::factory()->create();
        $credential = Credential::factory()->create();

        $event = new UserAddedToCredential(
            user: $user,
            credential: $credential,
            addedBy: $addedBy,
        );

        $this->assertSame('user.added-to-credential', $event->broadcastAs());
    }

    public function test_broadcast_with_includes_credential_and_actor_data(): void
    {
        $user = User::factory()->create();
        $addedBy = User::factory()->create(['name' => 'Admin User']);
        $credential = Credential::factory()->create([
            'name' => 'Engineering',
            'permission' => CredentialPermissionEnum::READ_WRITE,
        ]);

        $event = new UserAddedToCredential(
            user: $user,
            credential: $credential,
            addedBy: $addedBy,
        );

        $payload = $event->broadcastWith();

        $this->assertSame($credential->id, $payload['credential_id']);
        $this->assertSame('Engineering', $payload['credential_name']);
        $this->assertSame('read-write', $payload['permission']);
        $this->assertSame($addedBy->id, $payload['added_by']['id']);
        $this->assertSame('Admin User', $payload['added_by']['name']);
    }

    public function test_event_is_immutable(): void
    {
        $user = User::factory()->create();
        $addedBy = User::factory()->create();
        $credential = Credential::factory()->create();

        $event = new UserAddedToCredential(
            user: $user,
            credential: $credential,
            addedBy: $addedBy,
        );

        $this->assertObjectHasProperty('user', $event);
        $this->assertObjectHasProperty('credential', $event);
        $this->assertObjectHasProperty('addedBy', $event);
    }
}
