<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly database dump into storage/backups (retains the last 14 files).
Schedule::command('app:backup --keep=14')
    ->dailyAt('01:30')
    ->withoutOverlapping()
    ->runInBackground();
