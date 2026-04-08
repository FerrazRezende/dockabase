<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserStatusUpdatedEvent;
use App\Services\UserStatusService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class CacheUserStatusListener implements ShouldQueue
{
    public function __construct(
        private readonly UserStatusService $statusService,
    ) {
    }

    public function handle(UserStatusUpdatedEvent $event): void
    {
        $this->statusService->setStatus(
            user: $event->user,
            status: $event->status,
        );
    }
}
