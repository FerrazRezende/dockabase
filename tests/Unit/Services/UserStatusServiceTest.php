<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Services\UserStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

final class UserStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserStatusService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear all Redis keys before each test
        Redis::flushall();

        $this->service = new UserStatusService;
        $this->user = User::factory()->create();
    }

    public function test_set_online_sets_user_status_to_online(): void
    {
        $this->service->setOnline($this->user);

        $status = $this->service->getStatus($this->user);

        $this->assertEquals(UserStatusEnum::ONLINE, $status);
        $this->assertTrue($this->service->isOnline($this->user));
    }

    public function test_set_offline_removes_user_status_from_redis(): void
    {
        // First set user online
        $this->service->setOnline($this->user);

        // Verify user is online
        $this->assertTrue($this->service->isOnline($this->user));

        // Set user offline
        $this->service->setOffline($this->user);

        // Verify user is offline
        $this->assertEquals(UserStatusEnum::OFFLINE, $this->service->getStatus($this->user));
        $this->assertFalse($this->service->isOnline($this->user));

        // Verify Redis keys are deleted
        $statusKey = "user:{$this->user->id}:status";
        $heartbeatKey = "user:{$this->user->id}:heartbeat";

        $this->assertEquals(0, Redis::exists($statusKey));
        $this->assertEquals(0, Redis::exists($heartbeatKey));
    }

    public function test_set_status_returns_previous_and_new_status(): void
    {
        // First set to online
        $result = $this->service->setStatus($this->user, UserStatusEnum::ONLINE);

        $this->assertEquals(UserStatusEnum::OFFLINE, $result['from']);
        $this->assertEquals(UserStatusEnum::ONLINE, $result['to']);

        // Change to away
        $result = $this->service->setStatus($this->user, UserStatusEnum::AWAY);

        $this->assertEquals(UserStatusEnum::ONLINE, $result['from']);
        $this->assertEquals(UserStatusEnum::AWAY, $result['to']);
    }

    public function test_get_status_returns_offline_if_not_in_redis(): void
    {
        $status = $this->service->getStatus($this->user);

        $this->assertEquals(UserStatusEnum::OFFLINE, $status);
    }

    public function test_get_status_returns_current_status_from_redis(): void
    {
        $this->service->setStatus($this->user, UserStatusEnum::BUSY);

        $status = $this->service->getStatus($this->user);

        $this->assertEquals(UserStatusEnum::BUSY, $status);
    }

    public function test_refresh_heartbeat_updates_timestamp(): void
    {
        $this->service->setOnline($this->user);

        $heartbeatKey = "user:{$this->user->id}:heartbeat";

        // Get initial heartbeat
        $initialHeartbeat = Redis::get($heartbeatKey);

        // Wait a tiny bit to ensure timestamp difference
        sleep(1); // 1 second

        // Refresh heartbeat
        $this->service->refreshHeartbeat($this->user);

        // Get new heartbeat
        $newHeartbeat = Redis::get($heartbeatKey);

        $this->assertNotEquals($initialHeartbeat, $newHeartbeat);
        $this->assertTrue($this->service->isOnline($this->user));
    }

    public function test_refresh_heartbeat_brings_user_back_online_if_expired(): void
    {
        // Set user online
        $this->service->setOnline($this->user);

        // Simulate expired heartbeat by deleting it
        $heartbeatKey = "user:{$this->user->id}:heartbeat";
        Redis::del($heartbeatKey);

        // User should be offline without heartbeat
        $this->assertFalse($this->service->isOnline($this->user));

        // Refresh heartbeat should bring user back online
        $this->service->refreshHeartbeat($this->user);

        // User should be online again
        $this->assertTrue($this->service->isOnline($this->user));
    }

    public function test_is_online_returns_true_when_user_is_online(): void
    {
        $this->service->setOnline($this->user);

        $this->assertTrue($this->service->isOnline($this->user));
    }

    public function test_is_online_returns_true_when_user_is_away(): void
    {
        $this->service->setStatus($this->user, UserStatusEnum::AWAY);

        $this->assertTrue($this->service->isOnline($this->user));
    }

    public function test_is_online_returns_true_when_user_is_busy(): void
    {
        $this->service->setStatus($this->user, UserStatusEnum::BUSY);

        $this->assertTrue($this->service->isOnline($this->user));
    }

    public function test_is_online_returns_false_when_user_is_offline(): void
    {
        // User not in Redis is considered offline
        $this->assertFalse($this->service->isOnline($this->user));

        // Explicitly set to offline
        $this->service->setOnline($this->user);
        $this->service->setOffline($this->user);

        $this->assertFalse($this->service->isOnline($this->user));
    }

    public function test_get_multiple_statuses_returns_array_indexed_by_user_id(): void
    {
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // Set different statuses
        $this->service->setOnline($this->user);
        $this->service->setStatus($user2, UserStatusEnum::AWAY);
        // user3 stays offline

        $statuses = $this->service->getMultipleStatuses([
            $this->user->id,
            $user2->id,
            $user3->id,
        ]);

        $this->assertIsArray($statuses);
        $this->assertCount(3, $statuses);
        $this->assertEquals('online', $statuses[$this->user->id]);
        $this->assertEquals('away', $statuses[$user2->id]);
        $this->assertEquals('offline', $statuses[$user3->id]);
    }
}
