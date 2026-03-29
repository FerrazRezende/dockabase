<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DatabaseCreationStepEnum;
use App\Models\Database;
use Illuminate\Support\Facades\DB;

class DatabaseProvisioningService
{
    /**
     * @return DatabaseCreationStepEnum[]
     */
    public function getSteps(): array
    {
        return DatabaseCreationStepEnum::cases();
    }

    public function validateStep(Database $database): bool
    {
        $name = $database->database_name;

        // Database name must be valid PostgreSQL identifier
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException("Invalid database name: {$name}");
        }

        if (strlen($name) > 63) {
            throw new \InvalidArgumentException("Database name too long: {$name}");
        }

        return true;
    }

    public function createDatabase(Database $database): bool
    {
        $name = $database->database_name;

        try {
            // Create the PostgreSQL database
            DB::connection('pgsql')->statement("CREATE DATABASE \"{$name}\"");

            return true;
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to create database: {$e->getMessage()}");
        }
    }

    public function configureExtensions(Database $database): bool
    {
        // Configure extensions on the new database
        // This would connect to the new database and enable extensions
        // For now, we'll just return true as a placeholder
        return true;
    }

    public function runMigrations(Database $database): bool
    {
        // Run base migrations on the new database
        return true;
    }

    public function configurePermissions(Database $database): bool
    {
        // Configure database permissions
        return true;
    }

    public function testConnection(Database $database): bool
    {
        // Test the connection to the new database
        return true;
    }
}
