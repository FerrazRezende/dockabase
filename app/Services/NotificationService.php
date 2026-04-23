<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Credential;
use App\Models\Database;
use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Database created successfully — notify credential members + admins.
     */
    public function notifyDatabaseCreated(Database $database): void
    {
        $database->load('credentials.users');

        // Notify all users in attached credentials
        foreach ($database->credentials as $credential) {
            foreach ($credential->users as $user) {
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'database_ready',
                    'title' => __('Database created successfully'),
                    'message' => __('The database :name was created and is ready for use.', ['name' => $database->display_name ?: $database->name]),
                    'data' => [
                        'database_id' => $database->id,
                        'database_name' => $database->name,
                    ],
                ]);
            }
        }
    }

    /**
     * User added to a credential — notify the added user.
     */
    public function notifyUserAddedToCredential(Credential $credential, User $addedUser): void
    {
        Notification::create([
            'user_id' => $addedUser->id,
            'type' => 'user_added_to_credential',
            'title' => __('Added to credential'),
            'message' => __('You have been added to the credential :name.', ['name' => $credential->name]),
            'data' => [
                'credential_id' => $credential->id,
                'credential_name' => $credential->name,
                'permission' => $credential->permission->value,
            ],
        ]);
    }

    /**
     * Credential created — notify admins.
     */
    public function notifyCredentialCreated(Credential $credential, User $creator): void
    {
        $this->notifyAdmins(
            'credential_created',
            __('New credential created'),
            __('User :creator created the credential :name.', ['creator' => $creator->name, 'name' => $credential->name]),
            [
                'credential_id' => $credential->id,
                'credential_name' => $credential->name,
                'creator_id' => $creator->id,
                'creator_name' => $creator->name,
            ],
        );
    }

    /**
     * Database creation started — notify admins.
     */
    public function notifyDatabaseCreationStarted(Database $database, User $creator): void
    {
        $this->notifyAdmins(
            'database_creation_started',
            __('Database creation started'),
            __('User :creator started creating the database :name.', ['creator' => $creator->name, 'name' => $database->display_name ?: $database->name]),
            [
                'database_id' => $database->id,
                'database_name' => $database->name,
                'creator_id' => $creator->id,
                'creator_name' => $creator->name,
            ],
        );
    }

    /**
     * User attached to credential by someone — notify admins.
     */
    public function notifyUserAttachedToCredential(Credential $credential, User $addedUser, User $addedBy): void
    {
        $this->notifyAdmins(
            'user_attached_to_credential',
            __('User added to credential'),
            __('User :added_by added :user to the credential :credential.', [
                'added_by' => $addedBy->name,
                'user' => $addedUser->name,
                'credential' => $credential->name,
            ]),
            [
                'credential_id' => $credential->id,
                'credential_name' => $credential->name,
                'added_user_id' => $addedUser->id,
                'added_user_name' => $addedUser->name,
                'added_by_id' => $addedBy->id,
                'added_by_name' => $addedBy->name,
            ],
        );
    }

    /**
     * Credential attached to database — notify admins.
     */
    public function notifyCredentialAttachedToDatabase(Credential $credential, Database $database, User $attachedBy): void
    {
        $this->notifyAdmins(
            'credential_attached_to_database',
            __('Credential linked to database'),
            __('User :user linked the credential :credential to the database :database.', [
                'user' => $attachedBy->name,
                'credential' => $credential->name,
                'database' => $database->display_name ?: $database->name,
            ]),
            [
                'credential_id' => $credential->id,
                'credential_name' => $credential->name,
                'database_id' => $database->id,
                'database_name' => $database->name,
                'attached_by_id' => $attachedBy->id,
                'attached_by_name' => $attachedBy->name,
            ],
        );
    }

    /**
     * User removed from a credential — notify the removed user.
     */
    public function notifyUserRemovedFromCredential(Credential $credential, User $removedUser): void
    {
        Notification::create([
            'user_id' => $removedUser->id,
            'type' => 'user_removed_from_credential',
            'title' => __('Removed from credential'),
            'message' => __('You have been removed from the credential :name.', ['name' => $credential->name]),
            'data' => [
                'credential_id' => $credential->id,
                'credential_name' => $credential->name,
            ],
        ]);
    }

    /**
     * Send a notification to all admin users.
     */
    private function notifyAdmins(string $type, string $title, string $message, array $data): void
    {
        $admins = User::where('is_admin', true)->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
            ]);
        }
    }
}
