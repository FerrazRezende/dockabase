<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Database;
use App\Models\DatabaseTableMetadata;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateTableJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public Database $database,
        public string $schemaName,
        public string $tableName,
        public array $columns,
        public array $validations,
        public ?array $messages,
        public array $migrationDef,
    ) {}

    public function handle(): void
    {
        try {
            DB::transaction(function () {
                $database = $this->database;

                // Check if the Database model has migrations() relationship
                if (method_exists($database, 'migrations')) {
                    $database->migrations()->create([
                        'batch' => 1,
                        'name' => 'Create table ' . $this->tableName,
                        'operation' => $this->migrationDef['operation'],
                        'table_name' => $this->tableName,
                        'schema_name' => $this->schemaName,
                        'sql_up' => $this->migrationDef['sql_up'],
                        'sql_down' => $this->migrationDef['sql_down'],
                        'status' => 'executed',
                        'executed_at' => now(),
                    ]);
                }

                // Execute the DDL on the tenant database
                $connection = $this->getConnection($database);
                $connection->statement($this->migrationDef['sql_up']);

                $database->tableMetadata()->create([
                    'schema_name' => $this->schemaName,
                    'table_name' => $this->tableName,
                    'columns' => $this->columns,
                    'validations' => $this->validations,
                    'messages' => $this->messages,
                ]);
            });

            Log::info("Table {$this->schemaName}.{$this->tableName} created successfully", [
                'database_id' => $this->database->id,
            ]);

        } catch (Throwable $e) {
            Log::error("Failed to create table {$this->schemaName}.{$this->tableName}", [
                'database_id' => $this->database->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function getConnection(Database $database): ConnectionInterface
    {
        $connectionName = "tenant_{$database->id}";
        $default = config('database.connections.pgsql');

        config(["database.connections.{$connectionName}" => [
            'driver' => 'pgsql',
            'host' => $default['host'],
            'port' => $default['port'],
            'database' => $database->database_name,
            'username' => $default['username'],
            'password' => $default['password'],
        ]]);

        return DB::connection($connectionName);
    }
}
