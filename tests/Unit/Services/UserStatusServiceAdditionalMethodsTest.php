<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Services\UserStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

final class UserStatusServiceAdditionalMethodsTest extends TestCase
{
    use RefreshDatabase;

    private UserStatusService $service;

    private User $user1;

    private User $user2;

    private User $user3;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear all Redis keys before each test
        try {
            Redis::flushall();
        } catch (\Exception $e) {
            $this->markTestSkipped('Redis not available: '.$e->getMessage());
        }

        $this->service = new UserStatusService;
        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();
        $this->user3 = User::factory()->create();
    }

    public function test_get_user_ids_with_status_returns_correct_users_for_online(): void
    {
        // Set users to different statuses
        $this->service->setOnline($this->user1);
        $this->service->setStatus($this->user2, UserStatusEnum::ONLINE);
        $this->service->setStatus($this->user3, UserStatusEnum::AWAY);

        $onlineUserIds = $this->service->getUserIdsWithStatus('online');

        $this->assertIsArray($onlineUserIds);
        $this->assertCount(2, $onlineUserIds);
        $this->assertContains($this->user1->id, $onlineUserIds);
        $this->assertContains($this->user2->id, $onlineUserIds);
        $this->assertNotContains($this->user3->id, $onlineUserIds);
    }

    public function test_get_user_ids_with_status_returns_correct_users_for_away(): void
    {
        // Set users to different statuses
        $this->service->setOnline($this->user1);
        $this->service->setStatus($this->user2, UserStatusEnum::AWAY);
        $this->service->setStatus($this->user3, UserStatusEnum::AWAY);

        $awayUserIds = $this->service->getUserIdsWithStatus('away');

        $this->assertIsArray($awayUserIds);
        $this->assertCount(2, $awayUserIds);
        $this->assertContains($this->user2->id, $awayUserIds);
        $this->assertContains($this->user3->id, $awayUserIds);
        $this->assertNotContains($this->user1->id, $awayUserIds);
    }

    public function test_get_user_ids_with_status_returns_correct_users_for_busy(): void
    {
        // Set users to different statuses
        $this->service->setOnline($this->user1);
        $this->service->setStatus($this->user2, UserStatusEnum::BUSY);
        $this->service->setStatus($this->user3, UserStatusEnum::BUSY);

        $busyUserIds = $this->service->getUserIdsWithStatus('busy');

        $this->assertIsArray($busyUserIds);
        $this->assertCount(2, $busyUserIds);
        $this->assertContains($this->user2->id, $busyUserIds);
        $this->assertContains($this->user3->id, $busyUserIds);
        $this->assertNotContains($this->user1->id, $busyUserIds);
    }

    public function test_get_user_ids_with_status_returns_empty_array_for_status_with_no_users(): void
    {
        // Only set one user to online
        $this->service->setOnline($this->user1);

        $offlineUserIds = $this->service->getUserIdsWithStatus('offline');

        $this->assertIsArray($offlineUserIds);
        $this->assertCount(0, $offlineUserIds);
    }

    public function test_get_all_statuses_returns_array_of_all_cached_statuses(): void
    {
        // Set users to different statuses
        $this->service->setOnline($this->user1);
        $this->service->setStatus($this->user2, UserStatusEnum::AWAY);
        // user3 is not set, should not appear in array

        $allStatuses = $this->service->getAllStatuses();

        $this->assertIsArray($allStatuses);
        $this->assertCount(2, $allStatuses);
        $this->assertArrayHasKey($this->user1->id, $allStatuses);
        $this->assertArrayHasKey($this->user2->id, $allStatuses);
        $this->assertEquals('online', $allStatuses[$this->user1->id]);
        $this->assertEquals('away', $allStatuses[$this->user2->id]);
    }

    public function test_get_all_statuses_returns_empty_array_when_no_users_cached(): void
    {
        $allStatuses = $this->service->getAllStatuses();

        $this->assertIsArray($allStatuses);
        $this->assertCount(0, $allStatuses);
    }

    public function test_get_status_with_metadata_returns_full_metadata(): void
    {
        $this->service->setStatus($this->user1, UserStatusEnum::BUSY);

        $statusWithMetadata = $this->service->getStatusWithMetadata($this->user1->id);

        $this->assertIsArray($statusWithMetadata);
        $this->assertNotNull($statusWithMetadata);
        $this->assertArrayHasKey('status', $statusWithMetadata);
        $this->assertArrayHasKey('updated_at', $statusWithMetadata);
        $this->assertEquals('busy', $statusWithMetadata['status']);
        $this->assertArrayHasKey('heartbeat', $statusWithMetadata);
    }

    public function test_get_status_with_metadata_returns_null_for_non_existent_user(): void
    {
        $statusWithMetadata = $this->service->getStatusWithMetadata('99999');

        $this->assertNull($statusWithMetadata);
    }

    public function test_get_status_with_metadata_returns_null_for_user_without_status(): void
    {
        // Create a user but don't set any status
        $userWithoutStatus = User::factory()->create();

        $statusWithMetadata = $this->service->getStatusWithMetadata($userWithoutStatus->id);

        $this->assertNull($statusWithMetadata);
    }

    public function test_get_status_with_metadata_includes_heartbeat_timestamp(): void
    {
        $this->service->setOnline($this->user1);

        $statusWithMetadata = $this->service->getStatusWithMetadata($this->user1->id);

        $this->assertIsArray($statusWithMetadata);
        $this->assertArrayHasKey('heartbeat', $statusWithMetadata);
        $this->assertIsString($statusWithMetadata['heartbeat']);
        // Verify it's a valid ISO 8601 date
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $statusWithMetadata['heartbeat']);
    }

    public function test_get_status_returns_away_when_heartbeat_expired(): void
    {
        // Set user to online
        $this->service->setOnline($this->user1);

        // Verify user is online
        $this->assertEquals(UserStatusEnum::ONLINE, $this->service->getStatus($this->user1));

        // Manually delete the heartbeat to simulate expiration
        $heartbeatKey = "user:{$this->user1->id}:heartbeat";
        Redis::del($heartbeatKey);

        // Status should return AWAY (not OFFLINE) when heartbeat expires
        $status = $this->service->getStatus($this->user1);
        $this->assertEquals(UserStatusEnum::AWAY, $status);
    }

    public function test_get_status_returns_offline_when_status_key_not_exists(): void
    {
        // Don't set any status for user

        $status = $this->service->getStatus($this->user1);

        $this->assertEquals(UserStatusEnum::OFFLINE, $status);
    }

    public function test_get_status_returns_away_when_heartbeat_exists_but_status_missing(): void
    {
        // Set user online (creates both keys)
        $this->service->setOnline($this->user1);

        // Delete only the status key, keep heartbeat
        $statusKey = "user:{$this->user1->id}:status";
        Redis::del($statusKey);

        // Verify heartbeat still exists
        $heartbeatKey = "user:{$this->user1->id}:heartbeat";
        $this->assertEquals(1, Redis::exists($heartbeatKey));

        // Status should return AWAY (not OFFLINE)
        $status = $this->service->getStatus($this->user1);
        $this->assertEquals(UserStatusEnum::AWAY, $status);
    }
}
