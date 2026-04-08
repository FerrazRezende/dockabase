<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\UserActivity;
use Carbon\Carbon;
use Illuminate\Console\Command;

final class CleanupOldActivities extends Command
{
    protected $signature = 'presence:cleanup-activities {days=30}';
    protected $description = 'Clean up user activity records older than specified days';

    public function handle(): int
    {
        $days = (int) $this->argument('days');
        $cutoff = Carbon::now()->subDays($days);

        $this->info("Cleaning up activities older than {$days} days...");

        $deleted = UserActivity::where('created_at', '<', $cutoff)->delete();

        $this->info("Deleted {$deleted} old activity records.");

        return Command::SUCCESS;
    }
}
