<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Models\Database;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\DatabaseCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseCreatedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_notification_in_database(): void
    {
        $user = User::factory()->create();
        $database = Database::factory()->create(['name' => 'dev']);

        $notification = new DatabaseCreatedNotification($database);
        $notification->toDatabase($user);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'database_created',
            'title' => 'Database created successfully',
        ]);
    }

    public function test_notification_contains_database_data(): void
    {
        $user = User::factory()->create();
        $database = Database::factory()->create(['name' => 'staging']);

        $notification = new DatabaseCreatedNotification($database);
        $notification->toDatabase($user);

        $dbNotification = Notification::where('user_id', $user->id)->first();

        $this->assertEquals($database->id, $dbNotification->data['database_id']);
        $this->assertEquals('staging', $dbNotification->data['database_name']);
    }
}
