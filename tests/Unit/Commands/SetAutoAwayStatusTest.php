<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Services\UserActivityService;
use App\Services\UserStatusService;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

final class SetAutoAwayStatusTest extends TestCase
{
    use RefreshDatabase;

    private UserStatusService $statusService;

    private UserActivityService $activityService;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear all Redis keys before each test
        Redis::flushall();

        $this->statusService = new UserStatusService;
        $this->activityService = new UserActivityService;
    }

    public function test_command_sets_users_to_away_when_heartbeat_expired(): void
    {
        // Create users with different statuses
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // Set user1 to online
        $this->statusService->setOnline($user1);

        // Set user2 to busy
        $this->statusService->setStatus($user2, UserStatusEnum::BUSY);

        // Set user3 to online
        $this->statusService->setOnline($user3);

        // Delete heartbeat for user1 and user2 to simulate expiration
        $heartbeatKey1 = "user:{$user1->id}:heartbeat";
        $heartbeatKey2 = "user:{$user2->id}:heartbeat";
        Redis::del($heartbeatKey1);
        Redis::del($heartbeatKey2);

        // Verify initial state (heartbeat expired = AWAY status)
        $this->assertEquals(UserStatusEnum::AWAY, $this->statusService->getStatus($user1));
        $this->assertEquals(UserStatusEnum::AWAY, $this->statusService->getStatus($user2));
        $this->assertEquals(UserStatusEnum::ONLINE, $this->statusService->getStatus($user3));

        // Run the command
        $this->artisan('presence:set-auto-away')
            ->expectsOutput('Setting auto-away for inactive users...')
            ->assertExitCode(Command::SUCCESS);

        // user3 should still be online
        $this->assertEquals(UserStatusEnum::ONLINE, $this->statusService->getStatus($user3));
    }

    public function test_command_logs_activity_when_changing_status(): void
    {
        $user = User::factory()->create();

        // Set user to online
        $this->statusService->setOnline($user);

        // Delete heartbeat to simulate expiration
        $heartbeatKey = "user:{$user->id}:heartbeat";
        Redis::del($heartbeatKey);

        // Run the command
        $this->artisan('presence:set-auto-away')
            ->assertExitCode(Command::SUCCESS);

        // Check that activity was logged
        $activities = $user->activities()->statusChanged()->get();
        $this->assertCount(1, $activities);
        $this->assertEquals('away', $activities->first()->to_status);
    }

    public function test_command_does_not_change_offline_users(): void
    {
        $user = User::factory()->create();

        // User is offline by default (no Redis keys)

        // Run the command
        $this->artisan('presence:set-auto-away')
            ->assertExitCode(Command::SUCCESS);

        // User should still be offline
        $this->assertEquals(UserStatusEnum::OFFLINE, $this->statusService->getStatus($user));

        // No activity should be logged
        $activities = $user->activities()->statusChanged()->get();
        $this->assertCount(0, $activities);
    }

    public function test_command_reports_correct_number_of_affected_users(): void
    {
        // Create multiple users
        $users = User::factory()->count(5)->create();

        // Set all users to online
        foreach ($users as $user) {
            $this->statusService->setOnline($user);
        }

        // Delete heartbeats for first 3 users
        foreach ($users->take(3) as $user) {
            $heartbeatKey = "user:{$user->id}:heartbeat";
            Redis::del($heartbeatKey);
        }

        // Run the command and check output
        $this->artisan('presence:set-auto-away')
            ->expectsOutput('Setting auto-away for inactive users...')
            ->expectsOutput('Set 3 users to away.')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_command_handles_no_inactive_users(): void
    {
        User::factory()->count(3)->create();

        // Run the command
        $this->artisan('presence:set-auto-away')
            ->expectsOutput('Setting auto-away for inactive users...')
            ->expectsOutput('Set 0 users to away.')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_command_processes_users_in_chunks(): void
    {
        // Create more users than chunk size (100)
        User::factory()->count(150)->create();

        // This should not timeout or fail
        $this->artisan('presence:set-auto-away')
            ->assertExitCode(Command::SUCCESS);
    }
}
