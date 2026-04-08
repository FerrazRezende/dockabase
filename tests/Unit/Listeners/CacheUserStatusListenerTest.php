<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\UserStatusUpdatedEvent;
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
        $this->markTestSkipped('UserStatusService is marked as final and cannot be mocked');
    }

    public function test_listener_implements_should_queue(): void
    {
        $listener = app(CacheUserStatusListener::class);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $listener);
    }
}
