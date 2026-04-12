<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule commands
Schedule::command('presence:set-auto-away')->everyMinute()->runInBackground();
Schedule::command('presence:cleanup-activities 30')->dailyAt('00:00')->runInBackground();
