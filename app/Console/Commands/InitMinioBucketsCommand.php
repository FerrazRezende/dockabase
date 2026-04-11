<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class InitMinioBucketsCommand extends Command
{
    protected $signature = 'minio:init-buckets';
    protected $description = 'Create required MinIO buckets for profile pictures';

    public function handle(): int
    {
        $disk = 'minio';
        $buckets = ['profilepic'];

        $this->info('Initializing MinIO buckets...');

        foreach ($buckets as $bucket) {
            $this->info("Checking bucket: {$bucket}");

            // Check if bucket exists by trying to list files
            try {
                Storage::disk($disk)->listContents('/');
                $this->info("  - Bucket '{$bucket}' already accessible");
            } catch (\Exception $e) {
                $this->warn("  - Bucket '{$bucket}' not found. Please create it manually in MinIO console.");
                $this->warn("    URL: http://localhost:9001");
                $this->warn("    Access Key: " . env('MINIO_ROOT_USER'));
            }
        }

        $this->info('MinIO buckets check complete!');
        return Command::SUCCESS;
    }
}
