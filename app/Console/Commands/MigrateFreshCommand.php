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
        $dropped = 0;

        // Drop all databases tracked in the databases table
        $databases = Database::all();

        foreach ($databases as $database) {
            $name = $database->database_name;

            try {
                DB::connection('pgsql')->statement("DROP DATABASE IF EXISTS \"{$name}\"");
                $this->info("Dropped tracked database: {$name}");
                $dropped++;
            } catch (\Exception $e) {
                $this->warn("Could not drop database {$name}: {$e->getMessage()}");
            }
        }

        // Drop any remaining dockabase_* databases (cleanup untracked)
        $dockabaseDbs = DB::connection('pgsql')->select(
            "SELECT datname FROM pg_database WHERE datname LIKE 'dockabase_%' AND datistemplate = false"
        );

        foreach ($dockabaseDbs as $db) {
            $name = $db->datname;

            try {
                DB::connection('pgsql')->statement("DROP DATABASE IF EXISTS \"{$name}\"");
                $this->info("Dropped untracked database: {$name}");
                $dropped++;
            } catch (\Exception $e) {
                $this->warn("Could not drop database {$name}: {$e->getMessage()}");
            }
        }

        if ($dropped === 0) {
            $this->info('No databases to drop.');
        } else {
            $this->info("Dropped {$dropped} database(s).");
        }
    }
}
