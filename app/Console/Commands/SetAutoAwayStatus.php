<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UserStatusService;
use App\Services\UserActivityService;
use App\Enums\UserStatusEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

final class SetAutoAwayStatus extends Command
{
    protected $signature = 'presence:set-auto-away';
    protected $description = 'Set users to away if heartbeat expired';

    public function __construct(
        private readonly UserStatusService $statusService,
        private readonly UserActivityService $activityService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Setting auto-away for inactive users...');

        // Find all users with expired heartbeat
        $affected = 0;
        User::chunk(100, function ($users) use (&$affected) {
            foreach ($users as $user) {
                $statusKey = "user:{$user->id}:status";
                $heartbeatKey = "user:{$user->id}:heartbeat";

                // Check if user has a status key but expired heartbeat
                if (Redis::exists($statusKey) && ! Redis::exists($heartbeatKey)) {
                    // Get current status from Redis
                    $data = Redis::get($statusKey);
                    if ($data !== false) {
                        $decoded = json_decode($data, true);
                        $currentStatus = UserStatusEnum::from($decoded['status']);

                        // Only set to AWAY if not already AWAY or OFFLINE
                        if ($currentStatus !== UserStatusEnum::AWAY && $currentStatus !== UserStatusEnum::OFFLINE) {
                            $this->statusService->setStatus($user, UserStatusEnum::AWAY);
                            $this->activityService->logStatusChange(
                                user: $user,
                                from: $currentStatus,
                                to: UserStatusEnum::AWAY,
                            );
                            $affected++;
                        }
                    }
                }
            }
        });

        $this->info("Set {$affected} users to away.");

        return Command::SUCCESS;
    }
}
