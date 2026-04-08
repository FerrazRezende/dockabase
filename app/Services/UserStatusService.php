<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserStatusEnum;
use App\Models\User;
use Illuminate\Support\Facades\Redis;

final readonly class UserStatusService
{
    private const int STATUS_TTL = 300; // 5 minutes

    private const int HEARTBEAT_TTL = 120; // 2 minutes

    public function setOnline(User $user): void
    {
        $this->setStatus($user, UserStatusEnum::ONLINE);
    }

    public function setOffline(User $user): void
    {
        $userId = $user->id;

        // Delete status key
        $statusKey = "user:{$userId}:status";
        if (Redis::exists($statusKey)) {
            Redis::del($statusKey);
        }

        // Delete heartbeat key
        $heartbeatKey = "user:{$userId}:heartbeat";
        if (Redis::exists($heartbeatKey)) {
            Redis::del($heartbeatKey);
        }
    }

    public function setStatus(User $user, UserStatusEnum $status): array
    {
        $userId = $user->id;
        $statusKey = "user:{$userId}:status";

        // Get previous status
        $previousStatus = $this->getStatus($user);

        // Set new status
        $data = [
            'status' => $status->value,
            'updated_at' => now()->toIso8601String(),
        ];

        Redis::setex($statusKey, self::STATUS_TTL, json_encode($data));

        // Set heartbeat
        $this->refreshHeartbeat($user);

        return [
            'from' => $previousStatus,
            'to' => $status,
        ];
    }

    public function getStatus(User $user): UserStatusEnum
    {
        $userId = $user->id;
        $statusKey = "user:{$userId}:status";
        $heartbeatKey = "user:{$userId}:heartbeat";

        // If status key doesn't exist or heartbeat expired, user is offline
        if (! Redis::exists($statusKey) || ! Redis::exists($heartbeatKey)) {
            return UserStatusEnum::OFFLINE;
        }

        $data = Redis::get($statusKey);

        if ($data === false) {
            return UserStatusEnum::OFFLINE;
        }

        $decoded = json_decode($data, true);

        return UserStatusEnum::from($decoded['status']);
    }

    public function refreshHeartbeat(User $user): void
    {
        $userId = $user->id;
        $heartbeatKey = "user:{$userId}:heartbeat";

        Redis::setex($heartbeatKey, self::HEARTBEAT_TTL, (string) now()->timestamp);
    }

    public function updateHeartbeat(User $user): void
    {
        $userId = $user->id;
        $heartbeatKey = "user:{$userId}:heartbeat";

        Redis::setex($heartbeatKey, self::HEARTBEAT_TTL, now()->toIso8601String());
    }

    public function isOnline(User $user): bool
    {
        $status = $this->getStatus($user);

        return $status !== UserStatusEnum::OFFLINE;
    }

    /**
     * @param  array<int>  $userIds
     * @return array<int, string>
     */
    public function getMultipleStatuses(array $userIds): array
    {
        $statuses = [];

        foreach ($userIds as $userId) {
            $statusKey = "user:{$userId}:status";
            $heartbeatKey = "user:{$userId}:heartbeat";

            // If status or heartbeat doesn't exist, user is offline
            if (! Redis::exists($statusKey) || ! Redis::exists($heartbeatKey)) {
                $statuses[$userId] = UserStatusEnum::OFFLINE->value;
                continue;
            }

            $data = Redis::get($statusKey);

            if ($data === false) {
                $statuses[$userId] = UserStatusEnum::OFFLINE->value;
                continue;
            }

            $decoded = json_decode($data, true);
            $statuses[$userId] = $decoded['status'];
        }

        return $statuses;
    }
}
