<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Database;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class MigrateFreshCommand extends Command
{
    protected $signature = 'dockabase:migrate-fresh {--seed : Seed the database}';
    protected $description = 'Drop all managed databases, then run migrate:fresh';

    public function handle(): int
    {
        $this->dropManagedDatabases();

        $args = [];
        if ($this->option('seed')) {
            $args['--seed'] = true;
        }

        $this->call('migrate:fresh', $args);

        return self::SUCCESS;
    }

    private function dropManagedDatabases(): void
    {
        $databases = Database::all();

        if ($databases->isEmpty()) {
            $this->info('No managed databases to drop.');

            return;
        }

        foreach ($databases as $database) {
            $name = $database->database_name;

            try {
                DB::connection('pgsql')->statement("DROP DATABASE IF EXISTS \"{$name}\"");
                $this->info("Dropped database: {$name}");
            } catch (\Exception $e) {
                $this->warn("Could not drop database {$name}: {$e->getMessage()}");
            }
        }
    }
}
