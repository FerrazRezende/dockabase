<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Database;
use App\Models\Notification;
use App\Models\User;

class DatabaseCreatedNotification
{
    public function __construct(
        public Database $database,
    ) {}

    public function toDatabase(User $user): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => 'database_created',
            'title' => __('Database created successfully'),
            'message' => __('The database :name was created and is ready for use.', ['name' => $this->database->name]),
            'data' => [
                'database_id' => $this->database->id,
                'database_name' => $this->database->name,
            ],
        ]);
    }
}
