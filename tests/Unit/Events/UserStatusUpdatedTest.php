<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Events\UserStatusUpdated;
use App\Enums\UserStatusEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserStatusUpdatedTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_broadcasts_on_private_channel(): void
    {
        $user = User::factory()->create();
        $status = UserStatusEnum::ONLINE;

        $event = new UserStatusUpdated(
            user: $user,
            status: $status,
            message: 'Available for work',
        );

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertSame('private-presence', $channels[0]->name);
    }

    public function test_broadcast_as_returns_correct_event_name(): void
    {
        $user = User::factory()->create();
        $status = UserStatusEnum::BUSY;

        $event = new UserStatusUpdated(
            user: $user,
            status: $status,
            message: 'In client call',
        );

        $this->assertSame('status.updated', $event->broadcastAs());
    }

    public function test_broadcast_with_includes_all_required_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane Cooper',
            'email' => 'jane@example.com',
        ]);
        $status = UserStatusEnum::AWAY;

        $event = new UserStatusUpdated(
            user: $user,
            status: $status,
            message: 'Deep work session',
        );

        $payload = $event->broadcastWith();

        $this->assertSame($user->id, $payload['user_id']);
        $this->assertSame('Jane Cooper', $payload['name']);
        $this->assertSame('jane@example.com', $payload['email']);
        $this->assertSame('away', $payload['status']);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('status_label', $payload);
        $this->assertArrayHasKey('status_color', $payload);
        $this->assertSame('Deep work session', $payload['message']);
        $this->assertArrayHasKey('updated_at', $payload);
    }

    public function test_event_is_immutable(): void
    {
        $user = User::factory()->create();
        $status = UserStatusEnum::OFFLINE;

        $event = new UserStatusUpdated(
            user: $user,
            status: $status,
            message: 'Signing off',
        );

        $this->assertObjectHasProperty('user', $event);
        $this->assertObjectHasProperty('status', $event);
        $this->assertObjectHasProperty('message', $event);
    }
}
