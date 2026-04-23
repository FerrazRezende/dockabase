<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Database;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

class MigrationExecutorService
{
    public function execute(Database $database, string $sql): void
    {
        $connection = $this->getConnection($database);

        try {
            $connection->statement($sql);
        } finally {
            $connection->disconnect();
        }
    }

    public function testConnection(Database $database): bool
    {
        $connection = $this->getConnection($database);

        try {
            return $connection->getPdo() !== null;
        } catch (\Exception $e) {
            return false;
        } finally {
            $connection->disconnect();
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
