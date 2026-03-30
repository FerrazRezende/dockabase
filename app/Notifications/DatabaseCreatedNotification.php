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
            'title' => 'Database criado com sucesso',
            'message' => "O database {$this->database->name} foi criado e está pronto para uso.",
            'data' => [
                'database_id' => $this->database->id,
                'database_name' => $this->database->name,
            ],
        ]);
    }
}
