<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\DatabaseCreationStepEnum;
use App\Events\DatabaseCreated;
use App\Events\DatabaseFailed;
use App\Events\DatabaseStepUpdated;
use App\Models\Database;
use App\Services\DatabaseProvisioningService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class CreateDatabaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Database $database,
    ) {}

    public function handle(DatabaseProvisioningService $service): void
    {
        $this->database->update(['status' => 'processing']);

        try {
            $steps = $service->getSteps();

            foreach ($steps as $step) {
                // Add delay so user can see the progress
                sleep(1); // 1 second per step

                $this->executeStep($service, $step);

                $this->database->update([
                    'current_step' => $step->value,
                    'progress' => $step->progress(),
                ]);

                DatabaseStepUpdated::dispatch(
                    $this->database,
                    $step->value,
                    $step->progress()
                );
            }

            $this->database->update([
                'status' => 'ready',
                'current_step' => DatabaseCreationStepEnum::READY->value,
                'progress' => 100,
                'is_active' => true,
            ]);

            DatabaseCreated::dispatch($this->database);

            app(NotificationService::class)->notifyDatabaseCreated($this->database);

        } catch (Throwable $e) {
            $this->database->update([
                'status' => 'failed',
                'is_active' => false,
                'error_message' => $e->getMessage(),
            ]);

            DatabaseFailed::dispatch($this->database, $e->getMessage());

            throw $e;
        }
    }

    private function executeStep(DatabaseProvisioningService $service, DatabaseCreationStepEnum $step): void
    {
        match ($step) {
            DatabaseCreationStepEnum::VALIDATING => $service->validateStep($this->database),
            DatabaseCreationStepEnum::CREATING => $service->createDatabase($this->database),
            DatabaseCreationStepEnum::CONFIGURING => $service->configureExtensions($this->database),
            DatabaseCreationStepEnum::MIGRATING => $service->runMigrations($this->database),
            DatabaseCreationStepEnum::PERMISSIONS => $service->configurePermissions($this->database),
            DatabaseCreationStepEnum::TESTING => $service->testConnection($this->database),
            DatabaseCreationStepEnum::READY => null, // Final step, no action needed
        };
    }
}
