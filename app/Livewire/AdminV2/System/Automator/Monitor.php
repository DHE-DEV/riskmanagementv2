<?php

namespace App\Livewire\AdminV2\System\Automator;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\NotificationQueueLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * System > Automator > Monitor: die Durchlaeufe der Benachrichtigungs-
 * Warteschlangen (GTM, Travel Alert) und des Travel-Link-Syncs – mit den
 * Zahlen des Tages und der Moeglichkeit, einen Durchlauf sofort zu starten.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Automator Monitor')]
class Monitor extends Component
{
    use AuthorizesAdminV2, WithPagination;

    public const PER_PAGE = 25;

    /** Warteschlange => [Bezeichnung, Farbe, Konfigurationsschluessel des Intervalls] */
    public const QUEUES = [
        'gtm-notifications' => ['GTM Benachrichtigungen', 'sky', 'gtm_interval'],
        'travel-alert-notifications' => ['Travel Alert Benachrichtigungen', 'amber', 'travel_alert_interval'],
        'travel-link-sync' => ['Travel Link Sync', 'indigo', 'travel_links_sync_interval'],
    ];

    /** all oder ein Schluessel aus QUEUES */
    #[Url(except: 'all')]
    public string $tab = 'all';

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<string, string>
     */
    public function tabs(): array
    {
        return ['all' => 'Alle'] + array_map(fn (array $queue) => $queue[0], self::QUEUES);
    }

    /**
     * Die Zahlen des Tages je Warteschlange.
     *
     * @return array<string, array{last_run: ?NotificationQueueLog, runs: int, sent: int, errors: int, processed: int, interval: int}>
     */
    #[Computed]
    public function stats(): array
    {
        $today = now()->startOfDay();
        $stats = [];

        foreach (self::QUEUES as $queue => [, , $intervalKey]) {
            $todayQuery = fn () => NotificationQueueLog::forQueue($queue)->where('started_at', '>=', $today);

            $stats[$queue] = [
                'last_run' => NotificationQueueLog::forQueue($queue)->latest('started_at')->latest('id')->first(),
                'runs' => $todayQuery()->count(),
                'sent' => (int) $todayQuery()->sum('notifications_sent'),
                'errors' => (int) $todayQuery()->sum('errors'),
                'processed' => (int) $todayQuery()->sum('events_processed'),
                'interval' => (int) config('notifications.'.$intervalKey, 5),
            ];
        }

        return $stats;
    }

    #[Computed]
    public function logs(): LengthAwarePaginator
    {
        $query = NotificationQueueLog::query()->orderByDesc('started_at')->orderByDesc('id');

        if (isset(self::QUEUES[$this->tab])) {
            $query->forQueue($this->tab);
        }

        return $query->paginate(self::PER_PAGE);
    }

    public function runGtm(): void
    {
        $this->modal('monitor-run-gtm')->close();
        $this->runCommand('notifications:process-gtm', 'GTM-Durchlauf ausgeführt.');
    }

    public function runTravelAlert(): void
    {
        $this->modal('monitor-run-travel-alert')->close();
        $this->runCommand('notifications:process-travel-alert', 'Travel-Alert-Durchlauf ausgeführt.');
    }

    public function syncTravelLinks(): void
    {
        $this->modal('monitor-sync-travel-links')->close();
        $this->runCommand('travel-links:sync', 'Travel Links Sync gestartet.');
    }

    protected function runCommand(string $command, string $message): void
    {
        try {
            Artisan::call($command);
        } catch (\Throwable $e) {
            $this->dispatch('adminv2-toast', message: 'Fehler: '.$e->getMessage(), variant: 'danger');

            return;
        }

        $output = trim(Artisan::output());

        $this->refresh();
        $this->dispatch('adminv2-toast', message: $output !== '' ? $message.' '.\Illuminate\Support\Str::limit($output, 300) : $message);
    }

    public function refresh(): void
    {
        unset($this->stats, $this->logs);
    }

    public function render()
    {
        return view('livewire.admin-v2.system.automator.monitor');
    }
}
