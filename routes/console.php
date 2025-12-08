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

// Travel Detail Module - Scheduled Cleanup (nur wenn aktiviert)
if (config('travel_detail.enabled') && config('travel_detail.retention.scheduled_cleanup_enabled')) {
    $cleanupTime = config('travel_detail.retention.cleanup_time', '03:00');

    // Archive completed trips (mark as archived after X days)
    Schedule::command('td:archive-trips --force')
        ->dailyAt($cleanupTime)
        ->withoutOverlapping()
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/travel-detail-archive.log'));

    // Prune old import logs (delete after X days)
    Schedule::command('td:prune-logs')
        ->dailyAt($cleanupTime)
        ->withoutOverlapping()
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/travel-detail-prune-logs.log'));

    // Purge archived trips (permanently delete after X years)
    Schedule::command('td:purge-archived --force')
        ->weekly()
        ->sundays()
        ->at($cleanupTime)
        ->withoutOverlapping()
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/travel-detail-purge.log'));
}
