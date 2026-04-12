<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use App\Models\User;
use App\Models\UserActivity;
use App\Services\UserActivityService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CleanupOldActivitiesTest extends TestCase
{
    use RefreshDatabase;

    private UserActivityService $activityService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activityService = new UserActivityService;
    }

    public function test_command_deletes_activities_older_than_specified_days(): void
    {
        $user = User::factory()->create();

        // Create old activity (35 days ago)
        $oldActivity = UserActivity::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subDays(35),
        ]);

        // Create recent activity (10 days ago)
        $recentActivity = UserActivity::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subDays(10),
        ]);

        // Run command with 30 days threshold
        $this->artisan('presence:cleanup-activities 30')
            ->expectsOutput('Cleaning up activities older than 30 days...')
            ->expectsOutput('Deleted 1 old activity records.')
            ->assertExitCode(Command::SUCCESS);

        // Check database
        $this->assertDatabaseMissing('user_activities', [
            'id' => $oldActivity->id,
        ]);

        $this->assertDatabaseHas('user_activities', [
            'id' => $recentActivity->id,
        ]);
    }

    public function test_command_accepts_custom_days_parameter(): void
    {
        $user = User::factory()->create();

        // Create activities with different ages
        UserActivity::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subDays(15),
        ]);

        UserActivity::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subDays(25),
        ]);

        // Run with 10 days threshold
        $this->artisan('presence:cleanup-activities 10')
            ->expectsOutput('Cleaning up activities older than 10 days...')
            ->expectsOutput('Deleted 2 old activity records.')
            ->assertExitCode(Command::SUCCESS);

        // Both should be deleted
        $this->assertDatabaseCount('user_activities', 0);
    }

    public function test_command_deletes_exactly_old_boundary(): void
    {
        $user = User::factory()->create();

        // Create activity exactly 30 days old
        $boundaryActivity = UserActivity::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subDays(30)->subSecond(),
        ]);

        // Create activity slightly newer than 30 days
        $recentActivity = UserActivity::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subDays(29)->addSecond(),
        ]);

        // Run command
        $this->artisan('presence:cleanup-activities 30')
            ->assertExitCode(Command::SUCCESS);

        // Boundary activity should be deleted
        $this->assertDatabaseMissing('user_activities', [
            'id' => $boundaryActivity->id,
        ]);

        // Recent activity should remain
        $this->assertDatabaseHas('user_activities', [
            'id' => $recentActivity->id,
        ]);
    }

    public function test_command_handles_no_old_activities(): void
    {
        $user = User::factory()->create();

        // Create only recent activities
        UserActivity::factory()->count(5)->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subDays(5),
        ]);

        // Run command
        $this->artisan('presence:cleanup-activities 30')
            ->expectsOutput('Cleaning up activities older than 30 days...')
            ->expectsOutput('Deleted 0 old activity records.')
            ->assertExitCode(Command::SUCCESS);

        // All activities should remain
        $this->assertDatabaseCount('user_activities', 5);
    }

    public function test_command_handles_empty_activities_table(): void
    {
        // Run command with no activities
        $this->artisan('presence:cleanup-activities 30')
            ->expectsOutput('Cleaning up activities older than 30 days...')
            ->expectsOutput('Deleted 0 old activity records.')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_command_handles_large_dataset_efficiently(): void
    {
        $user = User::factory()->create();

        // Create large number of old activities
        UserActivity::factory()->count(500)->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subDays(100),
        ]);

        // Create some recent activities
        UserActivity::factory()->count(50)->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subDays(5),
        ]);

        // Run command
        $this->artisan('presence:cleanup-activities 30')
            ->expectsOutput('Deleted 500 old activity records.')
            ->assertExitCode(Command::SUCCESS);

        // Check correct count remains
        $this->assertDatabaseCount('user_activities', 50);
    }

    public function test_command_default_days_parameter_is_30(): void
    {
        $user = User::factory()->create();

        // Create activity older than 30 days
        UserActivity::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subDays(35),
        ]);

        // Run without specifying days (should default to 30)
        $this->artisan('presence:cleanup-activities')
            ->expectsOutput('Cleaning up activities older than 30 days...')
            ->assertExitCode(Command::SUCCESS);

        // Activity should be deleted
        $this->assertDatabaseCount('user_activities', 0);
    }
}
