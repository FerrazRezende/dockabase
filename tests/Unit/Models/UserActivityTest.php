<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\UserActivityTypeEnum;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_user_has_activities_relationship(): void
    {
        $activity = UserActivity::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertCount(1, $this->user->activities);
        $this->assertEquals($activity->id, $this->user->activities->first()->id);
    }

    public function test_status_changed_activity_type(): void
    {
        $activity = UserActivity::factory()->statusChanged()->create([
            'user_id' => $this->user->id,
            'from_status' => 'online',
            'to_status' => 'away',
        ]);

        $this->assertEquals(UserActivityTypeEnum::STATUS_CHANGED, $activity->activity_type);
        $this->assertEquals('online', $activity->from_status);
        $this->assertEquals('away', $activity->to_status);
    }

    public function test_database_created_activity_type(): void
    {
        $activity = UserActivity::factory()->databaseCreated()->create([
            'user_id' => $this->user->id,
            'metadata' => ['database_name' => 'production'],
        ]);

        $this->assertEquals(UserActivityTypeEnum::DATABASE_CREATED, $activity->activity_type);
        $this->assertEquals(['database_name' => 'production'], $activity->metadata);
    }

    public function test_credential_created_activity_type(): void
    {
        $activity = UserActivity::factory()->credentialCreated()->create([
            'user_id' => $this->user->id,
            'metadata' => ['credential_name' => 'Dev Team'],
        ]);

        $this->assertEquals(UserActivityTypeEnum::CREDENTIAL_CREATED, $activity->activity_type);
        $this->assertEquals(['credential_name' => 'Dev Team'], $activity->metadata);
    }
}
