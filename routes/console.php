<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// GDACS Events Synchronization (nur wenn aktiviert)
if (config('app.gdacs_enabled')) {
    Schedule::command('gdacs:update-events')
        ->hourly()
        ->withoutOverlapping(10) // 10 Minuten Timeout für Überlappungen
        ->runInBackground()
        ->emailOutputOnFailure(config('mail.admin_email', 'admin@passolution.eu'))
        ->appendOutputTo(storage_path('logs/gdacs-schedule.log'));
}
