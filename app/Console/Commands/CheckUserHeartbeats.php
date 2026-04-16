<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserStatusEnum;
use App\Events\UserStatusUpdated;
use App\Models\User;
use App\Models\UserActivity;
use App\Services\UserStatusService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

final class CheckUserHeartbeats extends Command
{
    protected $signature = 'presence:check-heartbeats';

    protected $description = 'Check user heartbeats and mark inactive users as offline';

    public function handle(UserStatusService $statusService): int
    {
        $this->info('Checking user heartbeats...');

        $pattern = 'user:*:status';
        $redis = Redis::connection();
        $keys = $redis->keys($pattern);

        if (empty($keys)) {
            $this->info('No active user sessions found.');

            return Command::SUCCESS;
        }

        $prefix = config('database.redis.options.prefix', '');
        $expiredCount = 0;

        foreach ($keys as $key) {
            $cleanKey = $prefix ? str_replace($prefix, '', $key) : $key;
            $keyParts = explode(':', $cleanKey);

            if (count($keyParts) < 3) {
                continue;
            }

            $userId = $keyParts[1];

            // Check if heartbeat key still exists
            $heartbeatKey = "user:{$userId}:heartbeat";

            if (Redis::exists($heartbeatKey)) {
                continue;
            }

            // Heartbeat expired — read current status before cleaning up
            $statusData = Redis::get($cleanKey);
            $previousStatus = UserStatusEnum::OFFLINE;

            if ($statusData !== false && $statusData !== null) {
                $decoded = json_decode($statusData, true);
                if (isset($decoded['status'])) {
                    $previousStatus = UserStatusEnum::from($decoded['status']);
                }
            }

            // Clean up Redis keys (facade auto-prepends prefix)
            Redis::del($cleanKey);
            Redis::del("user:{$userId}:heartbeat");

            // Broadcast offline status if the user was not already offline
            if ($previousStatus !== UserStatusEnum::OFFLINE) {
                $user = User::find($userId);

                if ($user !== null) {
                    broadcast(new UserStatusUpdated(
                        $user,
                        UserStatusEnum::OFFLINE,
                        '',
                    ));

                    UserActivity::create([
                        'user_id' => $user->id,
                        'activity_type' => 'status_changed',
                        'from_status' => $previousStatus->value,
                        'to_status' => UserStatusEnum::OFFLINE->value,
                        'metadata' => ['reason' => 'heartbeat_expired'],
                    ]);

                    $expiredCount++;
                }
            }
        }

        $this->info("Processed {$expiredCount} expired heartbeat(s).");

        return Command::SUCCESS;
    }
}
