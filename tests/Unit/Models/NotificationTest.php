<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_notification(): void
    {
        $user = User::factory()->create();
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'database_created',
            'title' => 'Database criado',
            'message' => 'O database dev foi criado com sucesso',
            'data' => ['database_id' => '123'],
        ]);

        $this->assertEquals('database_created', $notification->type);
        $this->assertFalse($notification->read);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($notification->user->is($user));
    }

    public function test_scope_unread_filters_read(): void
    {
        $user = User::factory()->create();
        Notification::factory()->create(['user_id' => $user->id, 'read' => false]);
        Notification::factory()->create(['user_id' => $user->id, 'read' => true]);

        $unread = Notification::unread()->get();

        $this->assertCount(1, $unread);
    }

    public function test_mark_as_read(): void
    {
        $notification = Notification::factory()->create(['read' => false]);

        $notification->markAsRead();

        $this->assertTrue($notification->fresh()->read);
    }
}
