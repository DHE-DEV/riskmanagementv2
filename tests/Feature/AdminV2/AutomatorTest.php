<?php

use App\Livewire\AdminV2\System\Automator\Index;
use App\Livewire\AdminV2\System\Automator\Monitor;
use App\Models\NotificationQueueLog;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * System > Automator: Warteschlange der Hintergrund-Jobs und der Monitor der
 * Benachrichtigungs-Durchlaeufe.
 */
function automatorAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true]);
}

function pendingJob(string $class, string $queue = 'default'): int
{
    return DB::table('jobs')->insertGetId([
        'queue' => $queue,
        'payload' => json_encode(['displayName' => $class, 'job' => 'Illuminate\\Queue\\CallQueuedHandler@call']),
        'attempts' => 0,
        'reserved_at' => null,
        'available_at' => now()->timestamp,
        'created_at' => now()->timestamp,
    ]);
}

function failedJob(string $class, string $exception = 'RuntimeException: Verbindung verweigert'): string
{
    $uuid = (string) Str::uuid();

    DB::table('failed_jobs')->insert([
        'uuid' => $uuid,
        'connection' => 'database',
        'queue' => 'notifications',
        'payload' => json_encode(['displayName' => $class]),
        'exception' => $exception."\n#0 /app/Jobs.php(12)",
        'failed_at' => now(),
    ]);

    return $uuid;
}

it('zeigt wartende und fehlgeschlagene Jobs mit dem Weg zum Monitor', function () {
    pendingJob('App\\Jobs\\SendGtmNotifications', 'notifications');
    pendingJob('App\\Jobs\\SyncInfosystemEntriesJob');
    failedJob('App\\Jobs\\SendTravelAlertNotifications', 'RuntimeException: SMTP nicht erreichbar');

    $this->actingAs(automatorAdmin())
        ->get(route('adminv2.system.automator.index'))
        ->assertOk()
        ->assertSee('Automator')
        ->assertSee('SendGtmNotifications')
        ->assertSee('SyncInfosystemEntriesJob')
        ->assertSee(route('adminv2.system.automator.monitor'));

    Livewire::test(Index::class)
        ->assertSet('tab', 'pending')
        ->set('tab', 'failed')
        ->assertSee('SendTravelAlertNotifications')
        ->assertSee('SMTP nicht erreichbar');
});

it('entfernt wartende Jobs einzeln und in Auswahl', function () {
    $first = pendingJob('App\\Jobs\\SendGtmNotifications');
    $second = pendingJob('App\\Jobs\\SyncInfosystemEntriesJob');
    $third = pendingJob('App\\Jobs\\SendTravelAlertNotifications');

    $this->actingAs(automatorAdmin());

    Livewire::test(Index::class)
        ->call('deletePending', $first)
        ->assertDispatched('adminv2-toast')
        ->set('selectedPending', [(string) $second, (string) $third])
        ->call('deleteSelectedPending')
        ->assertSet('selectedPending', []);

    expect(DB::table('jobs')->count())->toBe(0);
});

it('wiederholt und loescht fehlgeschlagene Jobs', function () {
    $retry = failedJob('App\\Jobs\\SendGtmNotifications');
    $forget = failedJob('App\\Jobs\\SendTravelAlertNotifications');
    $keep = failedJob('App\\Jobs\\SyncInfosystemEntriesJob');

    $this->actingAs(automatorAdmin());

    Livewire::test(Index::class, ['tab' => 'failed'])
        ->call('retry', $retry)
        ->call('deleteFailed', $forget)
        ->assertDispatched('adminv2-toast');

    // Wiederholt heisst: zurueck in die Warteschlange und aus den fehlgeschlagenen raus.
    expect(DB::table('failed_jobs')->pluck('uuid')->all())->toBe([$keep])
        ->and(DB::table('jobs')->count())->toBe(1);

    Livewire::test(Index::class, ['tab' => 'failed'])
        ->call('flushFailed');

    expect(DB::table('failed_jobs')->count())->toBe(0);
});

it('zeigt im Monitor die Durchlaeufe mit den Zahlen des Tages', function () {
    NotificationQueueLog::create([
        'queue_name' => 'gtm-notifications',
        'started_at' => now()->subMinutes(10),
        'completed_at' => now()->subMinutes(9),
        'events_processed' => 4,
        'notifications_sent' => 12,
        'errors' => 0,
        'status' => 'completed',
    ]);
    NotificationQueueLog::create([
        'queue_name' => 'travel-alert-notifications',
        'started_at' => now()->subMinutes(5),
        'completed_at' => now()->subMinutes(4),
        'events_processed' => 1,
        'notifications_sent' => 0,
        'errors' => 2,
        'status' => 'failed',
        'error_message' => 'Mailserver antwortet nicht',
    ]);

    $this->actingAs(automatorAdmin())
        ->get(route('adminv2.system.automator.monitor'))
        ->assertOk()
        ->assertSee('Automator Monitor')
        ->assertSee('Mailserver antwortet nicht')
        ->assertSee(route('adminv2.system.automator.index'));

    $monitor = Livewire::test(Monitor::class);

    expect($monitor->instance()->stats['gtm-notifications']['sent'])->toBe(12)
        ->and($monitor->instance()->stats['travel-alert-notifications']['errors'])->toBe(2);

    $monitor
        ->set('tab', 'gtm-notifications')
        ->assertSee('GTM Benachrichtigungen')
        ->assertDontSee('Mailserver antwortet nicht');
});

it('startet die Durchlaeufe aus dem Monitor heraus', function () {
    Artisan::shouldReceive('call')->once()->with('notifications:process-gtm')->andReturn(0);
    Artisan::shouldReceive('call')->once()->with('travel-links:sync')->andReturn(0);
    Artisan::shouldReceive('output')->andReturn('3 Ereignisse verarbeitet');

    $this->actingAs(automatorAdmin());

    Livewire::test(Monitor::class)
        ->call('runGtm')
        ->assertDispatched('adminv2-toast', fn (string $name, array $params) => str_contains($params['message'], 'GTM-Durchlauf ausgeführt') && str_contains($params['message'], '3 Ereignisse verarbeitet'))
        ->call('syncTravelLinks')
        ->assertDispatched('adminv2-toast', fn (string $name, array $params) => str_contains($params['message'], 'Travel Links Sync gestartet'));
});
