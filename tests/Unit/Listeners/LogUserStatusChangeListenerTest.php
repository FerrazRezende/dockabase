<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\UserStatusUpdatedEvent;
use App\Enums\UserStatusEnum;
use App\Listeners\LogUserStatusChangeListener;
use App\Models\User;
use App\Services\UserActivityService;
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
        $user = User::factory()->create();

        $event = new UserStatusUpdatedEvent(
            user: $user,
            status: UserStatusEnum::AWAY,
            message: 'Grabbing lunch',
        );

        $activityService = $this->mock(UserActivityService::class);
        $activityService->shouldReceive('logStatusChange')
            ->once()
            ->with(
                \Mockery::on(fn ($u) => $u->id === $user->id),
                \Mockery::on(fn ($from) => $from === UserStatusEnum::OFFLINE),
                \Mockery::on(fn ($to) => $to === UserStatusEnum::AWAY),
            );

        $listener = new LogUserStatusChangeListener($activityService);
        $listener->handle($event);
    }

    public function test_listener_implements_should_queue(): void
    {
        $listener = app(LogUserStatusChangeListener::class);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $listener);
    }
}
