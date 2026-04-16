<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Credential;
use App\Models\Notification;
use App\Models\User;

class UserAddedToCredentialNotification
{
    public function __construct(
        public readonly Credential $credential,
        public readonly User $addedBy,
    ) {}

    public function toDatabase(User $user): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => 'user_added_to_credential',
            'title' => __('Added to credential'),
            'message' => __('You have been added to the credential :name by :added_by.', [
                'name' => $this->credential->name,
                'added_by' => $this->addedBy->name,
            ]),
            'data' => [
                'credential_id' => $this->credential->id,
                'credential_name' => $this->credential->name,
                'permission' => $this->credential->permission->value,
                'added_by_id' => $this->addedBy->id,
                'added_by_name' => $this->addedBy->name,
            ],
        ]);
    }
}
