<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Database;
use App\Services\SchemaIntrospectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateSchemaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public Database $database,
        public string $schemaName,
    ) {}

    public function handle(SchemaIntrospectionService $introspectionService): void
    {
        try {
            $introspectionService->createSchema($this->database, $this->schemaName);

            Log::info("Schema {$this->schemaName} created successfully", [
                'database_id' => $this->database->id,
            ]);

        } catch (Throwable $e) {
            Log::error("Failed to create schema {$this->schemaName}", [
                'database_id' => $this->database->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
