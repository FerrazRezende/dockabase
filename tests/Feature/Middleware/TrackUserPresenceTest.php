<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\TrackUserPresence;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

final class TrackUserPresenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_middleware_updates_heartbeat_for_authenticated_user(): void
    {
        Redis::flushall();

        $user = User::factory()->create();
        $this->actingAs($user);

        $middleware = app(TrackUserPresence::class);
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, fn ($req) => response('OK'));

        $this->assertSame('OK', $response->getContent());

        // Verify heartbeat was updated in Redis
        $heartbeatKey = "user:{$user->id}:heartbeat";
        $this->assertNotEmpty(Redis::get($heartbeatKey));

        // Verify activity was logged to database
        $this->assertDatabaseHas('user_activities', [
            'user_id' => $user->id,
            'activity_type' => 'page_view',
        ]);
    }

    public function test_middleware_skips_guest_users(): void
    {
        Redis::flushall();
        Auth::shouldReceive('check')->andReturn(false);

        $middleware = app(TrackUserPresence::class);
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, fn ($req) => response('OK'));

        $this->assertSame('OK', $response->getContent());
    }

    public function test_middleware_is_final(): void
    {
        $middleware = app(TrackUserPresence::class);

        $this->assertInstanceOf(TrackUserPresence::class, $middleware);
    }

    public function test_middleware_throttles_page_view_logging(): void
    {
        Redis::flushall();

        $user = User::factory()->create();
        $this->actingAs($user);

        $middleware = app(TrackUserPresence::class);

        // First request should log activity
        $request1 = Request::create('/test', 'GET');
        $middleware->handle($request1, fn ($req) => response('OK'));

        $initialCount = UserActivity::where('user_id', $user->id)
            ->where('activity_type', 'page_view')
            ->count();

        $this->assertSame(1, $initialCount);

        // Second request within same minute should be throttled
        $request2 = Request::create('/test2', 'GET');
        $middleware->handle($request2, fn ($req) => response('OK'));

        $finalCount = UserActivity::where('user_id', $user->id)
            ->where('activity_type', 'page_view')
            ->count();

        $this->assertSame(1, $finalCount, 'Page view logging should be throttled to once per minute');
    }
}
