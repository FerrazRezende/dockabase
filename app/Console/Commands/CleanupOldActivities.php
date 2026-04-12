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

        // Validate days parameter
        if ($days < 1) {
            $this->error('Days must be at least 1.');

            return Command::FAILURE;
        }

        if ($days > 3650) {
            $this->error('Days cannot exceed 3650 (10 years).');

            return Command::FAILURE;
        }

        $cutoff = Carbon::now()->subDays($days);

        $this->info("Cleaning up activities older than {$days} days...");

        // Use chunked processing for large datasets
        $deleted = 0;
        UserActivity::where('created_at', '<', $cutoff)
            ->chunkById(1000, function ($activities) use (&$deleted) {
                $count = $activities->count();
                $activities->each->delete();
                $deleted += $count;
            });

        $this->info("Deleted {$deleted} old activity records.");

        return Command::SUCCESS;
    }
}
