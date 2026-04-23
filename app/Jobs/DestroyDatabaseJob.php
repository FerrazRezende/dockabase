<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Database;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DestroyDatabaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public string $databaseId,
    ) {}

    public function handle(): void
    {
        $database = Database::find($this->databaseId);

        if (! $database) {
            return;
        }

        try {
            // Soft delete the record
            $database->delete();

            // Future: DROP DATABASE + REVOKE ALL permissions here
            // when external-access and actual provisioning are implemented

            Log::info("Database {$database->name} deleted successfully", [
                'database_id' => $database->id,
            ]);

        } catch (Throwable $e) {
            Log::error("Failed to delete database", [
                'database_id' => $this->databaseId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
