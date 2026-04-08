<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserStatusEnum;
use App\Models\Credential;
use App\Models\Database;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Pagination\LengthAwarePaginator;

class UserActivityService
{
    public function logStatusChange(User $user, UserStatusEnum $from, UserStatusEnum $to): UserActivity
    {
        return UserActivity::create([
            'user_id' => $user->id,
            'activity_type' => 'status_changed',
            'from_status' => $from->value,
            'to_status' => $to->value,
            'metadata' => null,
        ]);
    }

    public function logDatabaseCreated(User $user, Database $database): UserActivity
    {
        return UserActivity::create([
            'user_id' => $user->id,
            'activity_type' => 'database_created',
            'from_status' => null,
            'to_status' => null,
            'metadata' => [
                'database_name' => $database->name,
                'permission' => $database->permission?->value ?? 'unknown',
            ],
        ]);
    }

    public function logCredentialCreated(User $user, Credential $credential): UserActivity
    {
        return UserActivity::create([
            'user_id' => $user->id,
            'activity_type' => 'credential_created',
            'from_status' => null,
            'to_status' => null,
            'metadata' => [
                'credential_name' => $credential->name,
                'permission' => $credential->permission->value,
            ],
        ]);
    }

    public function getUserActivities(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $user->activities()
            ->recent()
            ->paginate($perPage);
    }
}
