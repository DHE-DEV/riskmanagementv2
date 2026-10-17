<?php

namespace App\Services\Push;

use App\Models\Push\AppDeviceTravelLink;
use App\Models\Push\AppPushMessage;
use App\Models\TravelDetail\PdsGlobalSyncState;
use App\Services\PassolutionApiService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Erkennt wichtige Aenderungen an Reise-Links und benachrichtigt die Geraete, die
 * diese Links in der App haben.
 *
 * Quelle ist der interne Delta-Feed der pds-api (/__internal/travel-details/changes):
 * er liefert account-uebergreifend nur die Links, die sich seit dem letzten Lauf
 * geaendert haben, mit last_important_change_at – unabhaengig davon, wie viele
 * Geraete angemeldet sind. Der Cursor liegt in pds_global_sync_states.
 *
 * Ein Geraet bekommt einen Push, wenn die Aenderung neuer ist als seine Anmeldung
 * des Links und als die letzte Benachrichtigung (notified_change_at).
 */
class TravelChangeScanner
{
    public const STATE_KEY = 'travel_details_push';

    public function __construct(
        private readonly PassolutionApiService $api,
        private readonly PushDispatcher $dispatcher,
    ) {}

    /**
     * @return array{pages: int, links: int, notified: int, initialized: bool}
     */
    public function run(?int $maxPages = null, ?int $perPage = null): array
    {
        $maxPages ??= (int) config('push.travel_changes.max_pages', 50);
        $perPage ??= (int) config('push.travel_changes.per_page', 1000);
        $state = PdsGlobalSyncState::forKey(self::STATE_KEY);
        $stats = ['pages' => 0, 'links' => 0, 'notified' => 0, 'initialized' => false];

        // Erster Lauf: ab jetzt beobachten, nicht den ganzen Bestand durchgehen. Der Puffer von drei
        // Stunden in UTC liegt sicher vor "jetzt", egal ob die pds-Datenbank UTC oder Ortszeit speichert.
        if (! $state->cursor_last_change_at) {
            $state->update(['cursor_last_change_at' => now('UTC')->subHours(3), 'cursor_id' => 0, 'last_run_at' => now()]);
            $stats['initialized'] = true;

            return $stats;
        }

        $cursor = ['last_change_at' => $state->cursor_last_change_at->format('Y-m-d H:i:s'), 'id' => (int) $state->cursor_id];

        do {
            $response = $this->api->fetchTravelDetailChanges(null, $cursor, $perPage);
            if ($response === null) {
                Log::warning('Push: Delta-Feed nicht erreichbar, naechster Versuch im naechsten Lauf', ['page' => $stats['pages']]);
                break;
            }

            $links = $response['data'] ?? [];
            $stats['links'] += count($links);
            $stats['notified'] += $this->handle($links);
            $stats['pages']++;

            $next = $response['meta']['next_cursor'] ?? null;
            if ($next && ! empty($next['last_change_at']) && isset($next['id'])) {
                $cursor = ['last_change_at' => $next['last_change_at'], 'id' => (int) $next['id']];
                $state->update(['cursor_last_change_at' => $next['last_change_at'], 'cursor_id' => $next['id'], 'last_run_at' => now()]);
            } else {
                $state->update(['last_run_at' => now()]);
            }
        } while (($response['meta']['has_more'] ?? false) && $stats['pages'] < $maxPages);

        return $stats;
    }

    /**
     * Eine Seite des Feeds: betroffene Geraete finden, vormerken und Versand-Jobs anstossen.
     *
     * @param  array<int, array<string, mixed>>  $links
     * @return int Zahl der benachrichtigten Geraete
     */
    public function handle(array $links): int
    {
        $changes = [];
        $tripNames = [];
        foreach ($links as $link) {
            $tid = AppDeviceTravelLink::normalizeTid($link['tid'] ?? null);
            if ($tid === null || empty($link['last_important_change_at'])) {
                continue;
            }
            $changes[$tid] = Carbon::parse($link['last_important_change_at'])->setTimezone(config('app.timezone'));
            $tripNames[$tid] = filled($link['trip_name'] ?? null) ? mb_substr((string) $link['trip_name'], 0, 80) : null;
        }

        if ($changes === []) {
            return 0;
        }

        $message = null;
        $notified = 0;

        // Je Reise ein Datensatz je Geraet; chunkById haelt den Speicher auch bei sehr vielen Geraeten klein.
        AppDeviceTravelLink::query()
            ->select('app_device_travel_links.id', 'app_device_travel_links.app_device_id', 'app_device_travel_links.tid', 'app_device_travel_links.notified_change_at', 'app_device_travel_links.created_at')
            ->join('app_devices', 'app_devices.id', '=', 'app_device_travel_links.app_device_id')
            ->whereIn('app_device_travel_links.tid', array_keys($changes))
            ->where('app_devices.notifications_enabled', true)
            ->whereNotNull('app_devices.push_token')
            ->whereNull('app_devices.token_invalid_at')
            ->chunkById(5000, function ($rows) use ($changes, $tripNames, &$message, &$notified) {
                $due = [];
                foreach ($rows as $row) {
                    $change = $changes[$row->tid];
                    $since = max($row->notified_change_at?->getTimestamp() ?? 0, $row->created_at?->getTimestamp() ?? 0);
                    if ($change->getTimestamp() > $since) {
                        $due[$row->tid][$row->id] = $row->app_device_id;
                    }
                }

                if ($due === []) {
                    return;
                }

                $message ??= $this->dispatcher->begin([
                    'type' => AppPushMessage::TYPE_TRAVEL_IMPORTANT_CHANGE,
                    'source' => AppPushMessage::SOURCE_SCANNER,
                    'title' => PushTexts::TRAVEL_IMPORTANT_CHANGE_TITLE,
                    'body' => PushTexts::TRAVEL_IMPORTANT_CHANGE_BODY,
                    'target' => ['tids' => count($changes)],
                ]);

                $tidByDevice = [];
                foreach ($due as $tid => $rowsOfTid) {
                    // Vor dem Versand vormerken: ein zweiter Lauf schickt dieselbe Aenderung nicht noch einmal.
                    DB::table('app_device_travel_links')->whereIn('id', array_keys($rowsOfTid))
                        ->update(['notified_change_at' => $changes[$tid]]);

                    foreach ($rowsOfTid as $deviceId) {
                        // Mehrere betroffene Reisen eines Geraets: eine Benachrichtigung je Reise waere zu viel – die erste genuegt.
                        $tidByDevice[$deviceId] ??= $tid;
                    }
                }

                $this->dispatcher->dispatch($message, array_keys($tidByDevice), $tidByDevice, $tripNames);
                $notified += count($tidByDevice);
            }, 'app_device_travel_links.id', 'id');

        if ($message) {
            $this->dispatcher->release($message);
        }

        return $notified;
    }
}
