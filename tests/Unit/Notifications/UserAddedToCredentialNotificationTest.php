<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\CredentialPermissionEnum;
use App\Models\Credential;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\UserAddedToCredentialNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserAddedToCredentialNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_database_creates_notification_with_correct_data(): void
    {
        $user = User::factory()->create();
        $addedBy = User::factory()->create(['name' => 'Admin User']);
        $credential = Credential::factory()->create([
            'name' => 'Engineering',
            'permission' => CredentialPermissionEnum::READ_WRITE,
        ]);

        $notifier = new UserAddedToCredentialNotification(
            credential: $credential,
            addedBy: $addedBy,
        );

        $notification = $notifier->toDatabase($user);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertSame($user->id, $notification->user_id);
        $this->assertSame('user_added_to_credential', $notification->type);
        $this->assertSame($credential->id, $notification->data['credential_id']);
        $this->assertSame('Engineering', $notification->data['credential_name']);
        $this->assertSame('read-write', $notification->data['permission']);
        $this->assertSame($addedBy->id, $notification->data['added_by_id']);
        $this->assertSame('Admin User', $notification->data['added_by_name']);
    }

    public function test_notification_title_and_message_use_translations(): void
    {
        $user = User::factory()->create();
        $addedBy = User::factory()->create(['name' => 'Jane']);
        $credential = Credential::factory()->create(['name' => 'QA Team']);

        $notifier = new UserAddedToCredentialNotification(
            credential: $credential,
            addedBy: $addedBy,
        );

        $notification = $notifier->toDatabase($user);

        $this->assertNotEmpty($notification->title);
        $this->assertNotEmpty($notification->message);
        $this->assertStringContainsString('QA Team', $notification->message);
    }
}
