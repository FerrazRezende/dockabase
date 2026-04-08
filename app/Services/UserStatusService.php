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

        // If status key doesn't exist, user is offline
        if (! Redis::exists($statusKey)) {
            // But if heartbeat exists, return AWAY (user was online but status expired)
            if (Redis::exists($heartbeatKey)) {
                return UserStatusEnum::AWAY;
            }
            return UserStatusEnum::OFFLINE;
        }

        // If heartbeat expired, user is away (not offline)
        if (! Redis::exists($heartbeatKey)) {
            return UserStatusEnum::AWAY;
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

        Redis::setex($heartbeatKey, self::HEARTBEAT_TTL, now()->toIso8601String());
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

    /**
     * Get all user IDs with a specific status.
     *
     * @param  string  $status  The status value (online, away, busy, offline)
     * @return array<string> Array of user IDs (strings for KSUID)
     */
    public function getUserIdsWithStatus(string $status): array
    {
        $userIds = [];

        // Search for all status keys in Redis
        // We need to use the connection directly to get keys with prefix
        $pattern = 'user:*:status';
        $redis = Redis::connection();
        $keys = $redis->keys($pattern);

        foreach ($keys as $key) {
            // Remove the Redis prefix to get the clean key
            $prefix = config('database.redis.options.prefix', '');
            $cleanKey = $prefix ? str_replace($prefix, '', $key) : $key;

            // Extract user ID from key pattern "user:{id}:status"
            $keyParts = explode(':', $cleanKey);
            $userId = $keyParts[1]; // Keep as string (KSUID)

            // Get the status data using the facade (which handles prefix automatically)
            $data = Redis::get($cleanKey);

            if ($data === false || $data === null) {
                continue;
            }

            $decoded = json_decode($data, true);

            // Check if heartbeat still exists
            $heartbeatKey = "user:{$userId}:heartbeat";
            if (! Redis::exists($heartbeatKey)) {
                // User's heartbeat expired, they are AWAY
                if ($status === 'away') {
                    $userIds[] = $userId;
                }
                continue;
            }

            // Match the requested status
            if ($decoded['status'] === $status) {
                $userIds[] = $userId;
            }
        }

        return $userIds;
    }

    /**
     * Get all cached statuses as an array indexed by user ID.
     *
     * @return array<string, string> Array where key is user ID (string for KSUID) and value is status
     */
    public function getAllStatuses(): array
    {
        $statuses = [];

        // Search for all status keys in Redis
        // We need to use the connection directly to get keys with prefix
        $pattern = 'user:*:status';
        $redis = Redis::connection();
        $keys = $redis->keys($pattern);

        foreach ($keys as $key) {
            // Remove the Redis prefix to get the clean key
            $prefix = config('database.redis.options.prefix', '');
            $cleanKey = $prefix ? str_replace($prefix, '', $key) : $key;

            // Extract user ID from key pattern "user:{id}:status"
            $keyParts = explode(':', $cleanKey);
            $userId = $keyParts[1]; // Keep as string (KSUID)

            // Get the status data using the facade (which handles prefix automatically)
            $data = Redis::get($cleanKey);

            if ($data === false || $data === null) {
                continue;
            }

            $decoded = json_decode($data, true);
            $statuses[$userId] = $decoded['status'];
        }

        return $statuses;
    }

    /**
     * Get status with full metadata for a user.
     *
     * @param  string  $userId  The user ID (string for KSUID)
     * @return ?array{status: string, updated_at: string, heartbeat: string}|null
     */
    public function getStatusWithMetadata(string $userId): ?array
    {
        $statusKey = "user:{$userId}:status";
        $heartbeatKey = "user:{$userId}:heartbeat";

        // If status key doesn't exist, return null
        if (! Redis::exists($statusKey)) {
            return null;
        }

        // If heartbeat doesn't exist, return null
        if (! Redis::exists($heartbeatKey)) {
            return null;
        }

        // Get status data
        $statusData = Redis::get($statusKey);

        if ($statusData === false || $statusData === null) {
            return null;
        }

        $decoded = json_decode($statusData, true);

        // Get heartbeat data
        $heartbeat = Redis::get($heartbeatKey);

        if ($heartbeat === false || $heartbeat === null) {
            return null;
        }

        return [
            'status' => $decoded['status'],
            'updated_at' => $decoded['updated_at'],
            'heartbeat' => $heartbeat,
        ];
    }
}
