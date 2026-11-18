<?php

namespace App\Services\TravelDetail;

use App\Models\TravelAlertPdsAccount;
use App\Models\TravelAlertPdsTrip;
use App\Models\TravelDetail\PdsGlobalSyncState;
use App\Services\PassolutionApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Lokaler Spiegel der pds-Reisen für den Travel Alert.
 *
 * Konten, die Travel Alert nutzen, werden einmal vollständig geladen (alle Reisen,
 * die heute oder später enden), danach jede Minute über den Delta-Feed
 * __internal/travel-details/changes aktuell gehalten und nachts komplett abgeglichen
 * (der Feed meldet keine gelöschten Links). Der Travel Alert liest dann lokal statt
 * bei jedem Aufruf tausende Reisen bei pds abzufragen.
 */
class TravelAlertTripMirror
{
    /** Ohne Travel-Alert-Aufruf so lange wird ein Konto nicht mehr gespiegelt. */
    public const INACTIVE_AFTER_DAYS = 30;

    /** Cursor des Delta-Feeds (eigener Wasserstand, unabhängig von Push und td_trips). */
    public const SYNC_STATE_KEY = 'travel_alert_trips';

    /** Neu hinzugekommene Konten, die ein Minutenlauf höchstens vollständig lädt. */
    public const NEW_ACCOUNTS_PER_RUN = 3;

    public function __construct(private PassolutionApiService $api) {}

    /**
     * Merkt vor, dass ein Konto Travel Alert nutzt (höchstens einmal je Stunde geschrieben).
     */
    public function noteRequest(int $accountId): void
    {
        if (! Cache::add('travel_alert_pds_requested:'.$accountId, true, now()->addHour())) {
            return;
        }

        TravelAlertPdsAccount::query()->updateOrCreate(
            ['pds_account_id' => $accountId],
            ['last_requested_at' => now()],
        );
    }

    /**
     * Legt ein Konto für den Spiegel an, ohne dessen Nutzungszeitpunkt zurückzusetzen.
     */
    public function noteRequestNow(int $accountId): void
    {
        TravelAlertPdsAccount::query()->firstOrCreate(
            ['pds_account_id' => $accountId],
            ['last_requested_at' => now()],
        );
    }

    /**
     * Gespiegelte Reisen eines Kontos im Zeitraum – oder null, wenn das Konto (noch)
     * nicht gespiegelt ist oder der Zeitraum vor dem Spiegel beginnt.
     *
     * @return array<int, array<string, mixed>>|null
     */
    public function localTrips(int $accountId, string $startDate, string $endDate): ?array
    {
        $account = TravelAlertPdsAccount::find($accountId);

        if (! $account?->coversWindowFrom($startDate)) {
            return null;
        }

        // Wie die pds-Abfrage: Reise beginnt bis Zeitraumende und endet ab Zeitraumbeginn.
        return TravelAlertPdsTrip::query()
            ->where('pds_account_id', $accountId)
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->orderByDesc('start_date')
            ->toBase()
            ->pluck('payload')
            ->map(fn (string $payload) => json_decode($payload, true))
            ->all();
    }

    /**
     * Lädt alle Reisen eines Kontos neu und entfernt, was es bei pds nicht mehr gibt.
     */
    public function syncAccount(int $accountId): bool
    {
        $startedAt = now();
        $from = $startedAt->toDateString();

        $rows = $this->api->fetchAllTravelDetailsOfAccount($accountId, $from, fn (array $row) => self::slim($row));

        if ($rows === null) {
            Log::warning('Travel Alert: Spiegel des pds-Kontos nicht aktualisiert (Abruf unvollständig)', ['pds_account_id' => $accountId]);

            return false;
        }

        $this->initializeCursor();

        DB::transaction(function () use ($accountId, $rows, $startedAt, $from) {
            $this->upsertTrips($accountId, $rows, $startedAt);

            // Was der Vollabruf nicht mehr liefert, ist gelöscht oder beendet.
            $delivered = array_flip(array_filter(array_column($rows, 'tid')));
            $gone = TravelAlertPdsTrip::query()->where('pds_account_id', $accountId)->pluck('tid', 'id')
                ->reject(fn (string $tid) => isset($delivered[$tid]))->keys();

            foreach ($gone->chunk(1000) as $ids) {
                TravelAlertPdsTrip::query()->whereKey($ids->all())->delete();
            }

            TravelAlertPdsAccount::query()
                ->firstOrNew(['pds_account_id' => $accountId], ['last_requested_at' => $startedAt])
                ->fill(['full_synced_at' => $startedAt, 'synced_from' => $from, 'trips_count' => count($rows)])
                ->save();
        });

        return true;
    }

    /**
     * Übernimmt die Änderungen seit dem letzten Lauf aus dem Delta-Feed.
     *
     * @return array{pages: int, received: int, stored: int}
     */
    public function syncChanges(int $maxPages = 20): array
    {
        $stats = ['pages' => 0, 'received' => 0, 'stored' => 0];
        $accountIds = TravelAlertPdsAccount::query()->whereNotNull('full_synced_at')->pluck('pds_account_id')->all();

        if ($accountIds === []) {
            return $stats;
        }

        $state = PdsGlobalSyncState::forKey(self::SYNC_STATE_KEY);
        if (! $state->cursor_last_change_at) {
            $this->initializeCursor();
            $state->refresh();
        }

        $tracked = array_flip($accountIds);

        do {
            $cursor = ['last_change_at' => $state->cursor_last_change_at->format('Y-m-d H:i:s'), 'id' => (int) $state->cursor_id];
            $response = $this->api->fetchTravelDetailChanges(null, $cursor, 1000, $accountIds);

            if ($response === null) {
                break;
            }

            $byAccount = [];
            foreach ($response['data'] ?? [] as $row) {
                $accountId = (int) ($row['account_id'] ?? 0);
                $stats['received']++;

                if (isset($tracked[$accountId]) && ! empty($row['tid'])) {
                    $byAccount[$accountId][] = self::slim($row) + ['__last_change_at' => $row['last_change_at'] ?? null];
                }
            }

            foreach ($byAccount as $accountId => $rows) {
                $this->upsertTrips($accountId, $rows, now());
                $stats['stored'] += count($rows);
            }

            $next = $response['meta']['next_cursor'] ?? null;
            if (! empty($next['last_change_at']) && isset($next['id'])) {
                $state->update([
                    'cursor_last_change_at' => $next['last_change_at'],
                    'cursor_id' => $next['id'],
                    'last_run_at' => now(),
                ]);
            }

            $stats['pages']++;
        } while (($response['meta']['has_more'] ?? false) && $stats['pages'] < $maxPages);

        return $stats;
    }

    /**
     * Konten, die Travel Alert länger nicht genutzt haben, nicht mehr spiegeln.
     */
    public function pruneInactive(): int
    {
        $inactive = TravelAlertPdsAccount::query()
            ->where('last_requested_at', '<', now()->subDays(self::INACTIVE_AFTER_DAYS))
            ->pluck('pds_account_id');

        foreach ($inactive as $accountId) {
            TravelAlertPdsTrip::query()->where('pds_account_id', $accountId)->delete();
            TravelAlertPdsAccount::query()->whereKey($accountId)->delete();
        }

        return $inactive->count();
    }

    /**
     * Konten, die noch nie vollständig geladen wurden.
     *
     * @return array<int, int>
     */
    public function accountsAwaitingFirstSync(int $limit = self::NEW_ACCOUNTS_PER_RUN): array
    {
        return TravelAlertPdsAccount::query()->whereNull('full_synced_at')->orderBy('last_requested_at')
            ->limit($limit)->pluck('pds_account_id')->all();
    }

    /**
     * @return array<int, int>
     */
    public function activeAccounts(): array
    {
        return TravelAlertPdsAccount::query()->orderBy('pds_account_id')->pluck('pds_account_id')->all();
    }

    /**
     * Nur die Felder einer pds-Reise, die der Travel Alert liest – hält Spiegel und
     * Zwischenspeicher auch bei zehntausenden Reisen klein.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function slim(array $row): array
    {
        $codes = fn ($list) => array_values(array_map(
            fn ($item) => ['code' => is_array($item) ? ($item['code'] ?? null) : null],
            is_array($list) ? $list : [],
        ));

        return [
            'tid' => $row['tid'] ?? null,
            'id' => $row['id'] ?? null,
            'trip_name' => $row['trip_name'] ?? null,
            'start_date' => $row['start_date'] ?? null,
            'end_date' => $row['end_date'] ?? null,
            'travelers_count' => $row['travelers_count'] ?? null,
            'destinations' => $row['destinations'] ?? [],
            'destinations_list' => $codes($row['destinations_list'] ?? []),
            'countries' => $codes($row['countries'] ?? []),
            'nationalities' => $row['nationalities'] ?? [],
            'cruise_compass' => $row['cruise_compass'] ?? null,
            'cruise' => isset($row['cruise']['port_calls']) && is_array($row['cruise']['port_calls'])
                ? ['port_calls' => array_values(array_map(fn ($call) => [
                    'day' => $call['day'] ?? null,
                    'port' => ['country' => ['code' => $call['port']['country']['code'] ?? null]],
                ], $row['cruise']['port_calls']))]
                : null,
            'travel' => ['with_minors' => $row['travel']['with_minors'] ?? null],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows  verschlankte Reisen (optional mit __last_change_at)
     */
    private function upsertTrips(int $accountId, array $rows, \DateTimeInterface $stamp): void
    {
        $records = [];
        foreach ($rows as $row) {
            if (empty($row['tid'])) {
                continue;
            }
            $lastChangeAt = $row['__last_change_at'] ?? null;
            unset($row['__last_change_at']);

            $records[$row['tid']] = [
                'pds_account_id' => $accountId,
                'tid' => $row['tid'],
                'start_date' => $this->day($row['start_date'] ?? null),
                'end_date' => $this->day($row['end_date'] ?? null),
                'payload' => json_encode($row),
                'last_change_at' => $lastChangeAt ? date('Y-m-d H:i:s', strtotime($lastChangeAt)) : null,
                'created_at' => $stamp,
                'updated_at' => $stamp,
            ];
        }

        foreach (array_chunk(array_values($records), 500) as $chunk) {
            TravelAlertPdsTrip::query()->upsert(
                $chunk,
                ['tid'],
                ['pds_account_id', 'start_date', 'end_date', 'payload', 'last_change_at', 'updated_at'],
            );
        }
    }

    private function day(?string $value): ?string
    {
        return $value ? substr($value, 0, 10) : null;
    }

    /**
     * Startpunkt des Delta-Feeds: wie beim Push-Scanner in UTC (Zeitbasis der pds-Datenbank)
     * und großzügig zurück – doppelt übernommene Änderungen schaden nicht, fehlende schon.
     */
    private function initializeCursor(): void
    {
        $state = PdsGlobalSyncState::forKey(self::SYNC_STATE_KEY);

        if (! $state->cursor_last_change_at) {
            $state->update(['cursor_last_change_at' => now('UTC')->subHours(3), 'cursor_id' => 0, 'last_run_at' => now()]);
        }
    }
}
