<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Database;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
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
            $suffix = '_deleted_' . bin2hex(random_bytes(4));
            $newDbName = $database->database_name . $suffix;

            // Rename the actual PostgreSQL database
            $this->renameTenantDatabase($database, $newDbName);

            // Mark record as deleted with new name
            $database->update([
                'database_name' => $newDbName,
                'status' => 'deleted',
                'is_active' => false,
            ]);

            // Detach all credentials
            $database->credentials()->detach();

            Log::info('Database renamed and marked as deleted', [
                'database_id' => $database->id,
                'original_name' => $database->getOriginal('database_name'),
                'new_name' => $newDbName,
            ]);

        } catch (Throwable $e) {
            Log::error('Failed to delete database', [
                'database_id' => $this->databaseId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function renameTenantDatabase(Database $database, string $newName): void
    {
        $config = [
            'driver' => 'pgsql',
            'host' => $database->host,
            'port' => $database->port ?? 5432,
            'database' => config('database.connections.pgsql.database'),
            'username' => config('database.connections.pgsql.username'),
            'password' => config('database.connections.pgsql.password'),
        ];

        DB::purge('temp_admin');
        config(['database.connections.temp_admin' => $config]);

        // Disconnect users and rename
        DB::connection('temp_admin')->statement(
            "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = ?",
            [$database->database_name]
        );
        DB::connection('temp_admin')->statement(
            "ALTER DATABASE \"{$database->database_name}\" RENAME TO \"{$newName}\""
        );

        DB::purge('temp_admin');
    }
}
