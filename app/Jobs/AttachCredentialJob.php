<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Credential;
use App\Models\Database;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AttachCredentialJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public Database $database,
        public Credential $credential,
        public User $triggeredBy,
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $notificationService->notifyCredentialAttachedToDatabase(
            $this->credential,
            $this->database,
            $this->triggeredBy,
        );

        // Future: Create PostgreSQL role + GRANT permissions here
        // when external-access feature is implemented
    }
}
