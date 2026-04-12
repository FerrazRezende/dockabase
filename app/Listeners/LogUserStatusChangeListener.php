<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserStatusUpdatedEvent;
use App\Services\UserActivityService;
use App\Enums\UserStatusEnum;
use Illuminate\Contracts\Queue\ShouldQueue;

final class LogUserStatusChangeListener implements ShouldQueue
{
    public function __construct(
        private readonly UserActivityService $activityService,
    ) {
    }

    public function handle(UserStatusUpdatedEvent $event): void
    {
        $this->activityService->logStatusChange(
            user: $event->user,
            from: UserStatusEnum::OFFLINE, // Default previous status
            to: $event->status,
        );
    }
}
