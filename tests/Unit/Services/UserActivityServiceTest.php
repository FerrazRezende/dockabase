<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Models\UserActivity;
use App\Services\UserActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserActivityService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new UserActivityService();
        $this->user = User::factory()->create();
    }

    public function test_log_status_change_creates_activity(): void
    {
        $this->service->logStatusChange($this->user, UserStatusEnum::ONLINE, UserStatusEnum::AWAY);

        $this->assertDatabaseHas('user_activities', [
            'user_id' => $this->user->id,
            'activity_type' => 'status_changed',
            'from_status' => 'online',
            'to_status' => 'away',
        ]);
    }

    public function test_log_status_change_returns_activity(): void
    {
        $activity = $this->service->logStatusChange($this->user, UserStatusEnum::ONLINE, UserStatusEnum::BUSY);

        $this->assertInstanceOf(UserActivity::class, $activity);
        $this->assertEquals(\App\Enums\UserActivityTypeEnum::STATUS_CHANGED, $activity->activity_type);
        $this->assertEquals('online', $activity->from_status);
        $this->assertEquals('busy', $activity->to_status);
    }

    public function test_log_database_created_creates_activity(): void
    {
        $database = \App\Models\Database::factory()->create();

        $this->service->logDatabaseCreated($this->user, $database);

        $this->assertDatabaseHas('user_activities', [
            'user_id' => $this->user->id,
            'activity_type' => 'database_created',
        ]);
    }

    public function test_log_database_created_stores_metadata(): void
    {
        $database = \App\Models\Database::factory()->create([
            'name' => 'production',
        ]);

        $activity = $this->service->logDatabaseCreated($this->user, $database);

        $this->assertEquals('production', $activity->metadata['database_name']);
        $this->assertArrayHasKey('permission', $activity->metadata);
    }

    public function test_log_credential_created_creates_activity(): void
    {
        $credential = \App\Models\Credential::factory()->create();

        $this->service->logCredentialCreated($this->user, $credential);

        $this->assertDatabaseHas('user_activities', [
            'user_id' => $this->user->id,
            'activity_type' => 'credential_created',
        ]);
    }

    public function test_log_credential_created_stores_metadata(): void
    {
        $credential = \App\Models\Credential::factory()->create([
            'name' => 'Dev Team',
        ]);

        $activity = $this->service->logCredentialCreated($this->user, $credential);

        $this->assertEquals('Dev Team', $activity->metadata['credential_name']);
    }

    public function test_get_user_activities_returns_paginated(): void
    {
        UserActivity::factory()->count(25)->create([
            'user_id' => $this->user->id,
        ]);

        $activities = $this->service->getUserActivities($this->user);

        $this->assertCount(20, $activities->items());
    }

    public function test_get_user_activities_orders_by_recent(): void
    {
        $old = UserActivity::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(2),
        ]);

        $new = UserActivity::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDay(),
        ]);

        $activities = $this->service->getUserActivities($this->user);

        $this->assertEquals($new->id, $activities->first()->id);
        $this->assertEquals($old->id, $activities->last()->id);
    }
}
