<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use App\Enums\UserStatusEnum;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

final class CheckUserHeartbeatsTest extends TestCase
{
    use RefreshDatabase;

    private function cleanupRedisKeys(): void
    {
        $redis = Redis::connection();
        $prefix = config('database.redis.options.prefix', '');

        foreach (['user:*:status', 'user:*:heartbeat'] as $pattern) {
            $keys = $redis->keys($pattern);
            foreach ($keys as $key) {
                $cleanKey = $prefix ? str_replace($prefix, '', $key) : $key;
                Redis::del($cleanKey);
            }
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanupRedisKeys();
    }

    protected function tearDown(): void
    {
        $this->cleanupRedisKeys();
        parent::tearDown();
    }

    public function test_command_marks_expired_heartbeat_users_as_offline(): void
    {
        $user = User::factory()->create();

        // Set status key in Redis (simulating an online user) without heartbeat
        $statusKey = "user:{$user->id}:status";
        Redis::setex($statusKey, 300, json_encode([
            'status' => UserStatusEnum::ONLINE->value,
            'updated_at' => now()->toIso8601String(),
        ]));

        $this->artisan('presence:check-heartbeats')
            ->assertExitCode(Command::SUCCESS);

        // Activity should be logged with correct status transition
        $this->assertDatabaseHas('user_activities', [
            'user_id' => $user->id,
            'activity_type' => 'status_changed',
            'from_status' => UserStatusEnum::ONLINE->value,
            'to_status' => UserStatusEnum::OFFLINE->value,
        ]);
    }

    public function test_command_does_not_touch_users_with_active_heartbeat(): void
    {
        $user = User::factory()->create();

        // Set both status and heartbeat keys (simulating an active user)
        $statusKey = "user:{$user->id}:status";
        $heartbeatKey = "user:{$user->id}:heartbeat";

        Redis::setex($statusKey, 300, json_encode([
            'status' => UserStatusEnum::ONLINE->value,
            'updated_at' => now()->toIso8601String(),
        ]));
        Redis::setex($heartbeatKey, 120, now()->toIso8601String());

        $this->artisan('presence:check-heartbeats')
            ->assertExitCode(Command::SUCCESS);

        // No activity should be logged for active users
        $this->assertDatabaseMissing('user_activities', [
            'user_id' => $user->id,
            'activity_type' => 'status_changed',
        ]);
    }

    public function test_command_does_not_log_activity_for_already_offline_users(): void
    {
        $user = User::factory()->create();

        // Set status key with OFFLINE value but no heartbeat
        $statusKey = "user:{$user->id}:status";
        Redis::setex($statusKey, 300, json_encode([
            'status' => UserStatusEnum::OFFLINE->value,
            'updated_at' => now()->toIso8601String(),
        ]));

        $this->artisan('presence:check-heartbeats')
            ->assertExitCode(Command::SUCCESS);

        // No activity should be logged (was already offline)
        $this->assertDatabaseMissing('user_activities', [
            'user_id' => $user->id,
            'activity_type' => 'status_changed',
            'to_status' => UserStatusEnum::OFFLINE->value,
        ]);
    }

    public function test_command_handles_no_active_sessions(): void
    {
        $this->artisan('presence:check-heartbeats')
            ->expectsOutput('No active user sessions found.')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_command_marks_away_users_as_offline_when_heartbeat_expires(): void
    {
        $user = User::factory()->create();

        // Set status to AWAY but with expired heartbeat
        $statusKey = "user:{$user->id}:status";
        Redis::setex($statusKey, 300, json_encode([
            'status' => UserStatusEnum::AWAY->value,
            'updated_at' => now()->toIso8601String(),
        ]));

        $this->artisan('presence:check-heartbeats')
            ->assertExitCode(Command::SUCCESS);

        // Activity should show away -> offline
        $this->assertDatabaseHas('user_activities', [
            'user_id' => $user->id,
            'from_status' => UserStatusEnum::AWAY->value,
            'to_status' => UserStatusEnum::OFFLINE->value,
        ]);
    }

    public function test_command_processes_multiple_users(): void
    {
        $onlineUser = User::factory()->create();
        $expiredUser1 = User::factory()->create();
        $expiredUser2 = User::factory()->create();

        // Online user (has heartbeat)
        $statusKey1 = "user:{$onlineUser->id}:status";
        $heartbeatKey1 = "user:{$onlineUser->id}:heartbeat";
        Redis::setex($statusKey1, 300, json_encode([
            'status' => UserStatusEnum::ONLINE->value,
            'updated_at' => now()->toIso8601String(),
        ]));
        Redis::setex($heartbeatKey1, 120, now()->toIso8601String());

        // Expired user 1
        $statusKey2 = "user:{$expiredUser1->id}:status";
        Redis::setex($statusKey2, 300, json_encode([
            'status' => UserStatusEnum::ONLINE->value,
            'updated_at' => now()->toIso8601String(),
        ]));

        // Expired user 2
        $statusKey3 = "user:{$expiredUser2->id}:status";
        Redis::setex($statusKey3, 300, json_encode([
            'status' => UserStatusEnum::BUSY->value,
            'updated_at' => now()->toIso8601String(),
        ]));

        $this->artisan('presence:check-heartbeats')
            ->assertExitCode(Command::SUCCESS);

        // Two activities logged for expired users (online user untouched)
        $this->assertDatabaseHas('user_activities', [
            'user_id' => $expiredUser1->id,
            'from_status' => UserStatusEnum::ONLINE->value,
            'to_status' => UserStatusEnum::OFFLINE->value,
        ]);
        $this->assertDatabaseHas('user_activities', [
            'user_id' => $expiredUser2->id,
            'from_status' => UserStatusEnum::BUSY->value,
            'to_status' => UserStatusEnum::OFFLINE->value,
        ]);

        // Online user should NOT have an activity logged
        $this->assertDatabaseMissing('user_activities', [
            'user_id' => $onlineUser->id,
            'activity_type' => 'status_changed',
        ]);
    }
}
