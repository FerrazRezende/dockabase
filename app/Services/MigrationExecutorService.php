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
        return DB::connect([
            'driver' => 'pgsql',
            'host' => $database->host,
            'port' => $database->port,
            'database' => $database->database_name,
            'username' => config('database.connections.pgsql.username'),
            'password' => config('database.connections.pgsql.password'),
        ]);
    }
}
