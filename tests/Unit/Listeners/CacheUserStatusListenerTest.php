<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\UserStatusUpdated;
use App\Enums\UserStatusEnum;
use App\Listeners\CacheUserStatusListener;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CacheUserStatusListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_can_be_instantiated(): void
    {
        $listener = app(CacheUserStatusListener::class);

        $this->assertInstanceOf(CacheUserStatusListener::class, $listener);
    }

    public function test_listener_calls_status_service_to_cache(): void
    {
        try {
            // Integration test: use real service and Redis connection
            $user = User::factory()->create();

            $event = new UserStatusUpdated(
                user: $user,
                status: UserStatusEnum::BUSY,
                message: 'Focus time',
            );

            $listener = app(CacheUserStatusListener::class);
            $listener->handle($event);

            // Verify Redis was updated via the service
            $cachedData = \Illuminate\Support\Facades\Redis::get("user:{$user->id}:status");
            $decoded = json_decode($cachedData, true);
            $this->assertSame('busy', $decoded['status']);

            // Cleanup
            \Illuminate\Support\Facades\Redis::del("user:{$user->id}:status");
        } catch (\RedisException $e) {
            $this->markTestSkipped('Redis is not available for integration testing');
        }
    }

    public function test_listener_implements_should_queue(): void
    {
        $listener = app(CacheUserStatusListener::class);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $listener);
    }
}
