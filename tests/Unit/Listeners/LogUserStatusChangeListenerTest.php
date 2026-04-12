<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\UserStatusUpdatedEvent;
use App\Enums\UserStatusEnum;
use App\Listeners\LogUserStatusChangeListener;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LogUserStatusChangeListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_can_be_instantiated(): void
    {
        $listener = app(LogUserStatusChangeListener::class);

        $this->assertInstanceOf(LogUserStatusChangeListener::class, $listener);
    }

    public function test_listener_calls_activity_service_with_correct_data(): void
    {
        // Integration test: use real service and database
        $user = User::factory()->create();

        $event = new UserStatusUpdatedEvent(
            user: $user,
            status: UserStatusEnum::AWAY,
            message: 'Grabbing lunch',
        );

        $listener = app(LogUserStatusChangeListener::class);
        $listener->handle($event);

        // Verify activity was logged to database
        $this->assertDatabaseHas('user_activities', [
            'user_id' => $user->id,
            'activity_type' => 'status_changed',
            'to_status' => 'away',
        ]);
    }

    public function test_listener_implements_should_queue(): void
    {
        $listener = app(LogUserStatusChangeListener::class);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $listener);
    }
}
