<?php

namespace App\Livewire\AdminV2\Events;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\CustomEvent;
use App\Models\EventClick;
use App\Models\NotificationQueueLog;
use App\Support\AdminV2\EventState;
use Cron\CronExpression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Stand der Ereignisse auf einen Blick: Kennzahlen je Zustand, Handlungsbedarf
 * und die Hintergrundlaeufe der Benachrichtigungen.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Ereignisse – Übersicht')]
class Overview extends Component
{
    use AuthorizesAdminV2;

    /**
     * Kennzahlen je Zustand – jede Kachel fuehrt in die passend gefilterte Liste.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        return collect([EventState::Live, EventState::Scheduled, EventState::Draft, EventState::PendingReview])
            ->mapWithKeys(fn (EventState $state) => [
                $state->value => $state->apply(CustomEvent::query())->count(),
            ])
            ->all();
    }

    /**
     * Von aussen eingereichte Ereignisse, die auf Freigabe warten.
     */
    #[Computed]
    public function pendingReview(): Collection
    {
        return EventState::PendingReview->apply(CustomEvent::query())
            ->with('countries')
            ->latest()
            ->limit(6)
            ->get();
    }

    /**
     * Ausgelieferte Ereignisse ohne Standort: sie erscheinen nicht auf der
     * Karte und koennen keiner Reise zugeordnet werden.
     */
    #[Computed]
    public function liveWithoutLocation(): Collection
    {
        return $this->liveQuery()
            ->whereDoesntHave('countries')
            ->whereNull('country_id')
            ->orderByDesc('start_date')
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function liveWithoutLocationCount(): int
    {
        return $this->liveQuery()
            ->whereDoesntHave('countries')
            ->whereNull('country_id')
            ->count();
    }

    /**
     * Ereignisse, deren Zeitraum in den naechsten sieben Tagen endet.
     */
    #[Computed]
    public function endingSoon(): Collection
    {
        return $this->liveQuery()
            ->whereBetween('end_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
            ->with('countries')
            ->orderBy('end_date')
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function recentlyChanged(): Collection
    {
        return CustomEvent::query()
            ->whereNull('superseded_by_id')
            ->with('countries')
            ->latest('updated_at')
            ->limit(8)
            ->get();
    }

    /**
     * @return array{today: int, week: int}
     */
    #[Computed]
    public function clicks(): array
    {
        return [
            'today' => EventClick::whereDate('clicked_at', today())->count(),
            'week' => EventClick::whereBetween('clicked_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];
    }

    /**
     * Die drei protokollierten Hintergrundlaeufe mit letztem und naechstem Lauf.
     *
     * @return array<int, array{label: string, interval: int, last: ?NotificationQueueLog, next: Carbon}>
     */
    #[Computed]
    public function automations(): array
    {
        $queues = [
            ['GTM-Benachrichtigungen', 'gtm-notifications', (int) config('notifications.gtm_interval', 5)],
            ['Travel-Alert-Benachrichtigungen', 'travel-alert-notifications', (int) config('notifications.travel_alert_interval', 5)],
            ['Travel-Links-Sync', 'travel-link-sync', (int) config('notifications.travel_links_sync_interval', 30)],
        ];

        return array_map(fn (array $queue) => [
            'label' => $queue[0],
            'interval' => $queue[2],
            'last' => NotificationQueueLog::forQueue($queue[1])->latest('started_at')->first(),
            // Derselbe Cron-Ausdruck wie in routes/console.php.
            'next' => Carbon::instance((new CronExpression("*/{$queue[2]} * * * *"))->getNextRunDate(now())),
        ], $queues);
    }

    protected function liveQuery(): Builder
    {
        return EventState::Live->apply(CustomEvent::query());
    }

    public function render()
    {
        return view('livewire.admin-v2.events.overview');
    }
}
