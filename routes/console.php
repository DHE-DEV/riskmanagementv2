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

// Scheduled Branch Deletion - runs daily at 00:00
Schedule::command('branches:delete-scheduled')
    ->daily()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/branch-deletion-schedule.log'));

// Cleanup Expired Exports - runs every hour
Schedule::command('exports:cleanup')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/exports-cleanup-schedule.log'));

// Generate Sitemap - runs daily at 02:00 for SEO
Schedule::command('sitemap:generate')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/sitemap-generation-schedule.log'));
