<?php

namespace App\Jobs;

use App\Mail\FillMissingRegionInfoMail;
use App\Models\Region;
use App\Models\RegionInfoRun;
use App\Models\Subregion;
use App\Services\RegionInfoGenerator;
use App\Services\SubregionGenerator;
use App\Support\AdminV2\RegionInfoFillRun;
use App\Support\AdminV2\RegionInfoTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Fuellt die Luecken der Regionsinfos von Regionen und Teilgebieten
 * (regions:fill-missing). Drei Schritte, nacheinander:
 *   r – Regionsinfos ergaenzen
 *   d – je Region neue Teilgebiete recherchieren und anlegen (SubregionGenerator);
 *       die neuen kommen als "s" hinten an
 *   s – Infos der Teilgebiete ergaenzen (Prompt baut auf der Region auf)
 * Je Durchgang einige Eintraege, danach stellt sich der Job wieder hinten an.
 *
 * Stand am Lauf (region_info_runs, kind "fill_missing"):
 *   pending: ["r12", "d12", "s4", …]
 *   failed:  {"r12": "Fehlermeldung"}
 *   result:  {countries, country_ids, web, notify, task_id, regions, discover, subregions,
 *             filled/discovered (bearbeitet je Schritt), changed, created (neue Teilgebiete), created_ids, tokens,
 *             active: {"r13": Zeitpunkt} (gerade in Arbeit), restarts}
 *
 * Wer den letzten Durchgang beendet, schliesst den Lauf ab und verschickt
 * die Mail und erledigt die Aufgabe dazu (RegionInfoTask) – auch wenn (z. B. nach einer Zeitueberschreitung) zwei Ketten
 * parallel arbeiten, wird so nichts doppelt bearbeitet oder gemeldet.
 */
class FillMissingRegionInfoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    /** Eintraege, die laenger in Arbeit sind, gelten als verloren. */
    public const STALE_MINUTES = 20;

    /** So oft setzt sich ein abgebrochener Lauf selbst wieder fort. */
    public const MAX_RESTARTS = 5;

    /**
     * Fehler, bei denen kein weiterer Eintrag gelingen kann (Guthaben leer,
     * Schluessel ungueltig): Der Lauf bricht sofort ab, statt alles Offene
     * als fehlgeschlagen zu markieren.
     */
    public const FATAL_ERRORS = [
        'no credits remaining',
        'insufficient_quota',
        'exceeded your current quota',
        'billing_hard_limit',
        'incorrect api key',
        'invalid_api_key',
        'account_deactivated',
    ];

    public function __construct(public int $runId) {}

    /**
     * @param  array<int, int>  $regionIds
     * @param  array<int, int>  $subregionIds
     * @param  array<int, int>  $discoverIds  Regionen, in denen nach neuen Teilgebieten gesucht wird
     * @param  array<int, string>  $countries  ID => Name der Laender (fuer Mail und Links)
     * @param  array<int, string>  $notify  E-Mail-Adressen fuer die Meldung am Ende
     * @param  int|null  $taskId  Aufgabe, die nach Lauf und Mail erledigt wird
     */
    public static function start(array $regionIds, array $subregionIds, array $countries, bool $web, array $notify, ?int $taskId = null, array $discoverIds = []): RegionInfoRun
    {
        return RegionInfoRun::create([
            'kind' => RegionInfoRun::KIND_FILL_MISSING,
            'status' => RegionInfoRun::STATUS_RUNNING,
            'pending' => [
                ...array_map(fn ($id) => 'r'.$id, $regionIds),
                ...array_map(fn ($id) => 'd'.$id, $discoverIds),
                ...array_map(fn ($id) => 's'.$id, $subregionIds),
            ],
            'total' => count($regionIds) + count($discoverIds) + count($subregionIds),
            'failed' => [],
            'result' => [
                'countries' => array_values($countries),
                'country_ids' => array_keys($countries),
                'web' => $web,
                'notify' => array_values($notify),
                'task_id' => $taskId,
                'regions' => count($regionIds),
                'discover' => count($discoverIds),
                'subregions' => count($subregionIds),
                'changed' => 0,
                'created' => 0,
                'created_ids' => [],
                'tokens' => 0,
            ],
        ]);
    }

    public function handle(RegionInfoGenerator $generator): void
    {
        if (self::step($this->runId, $generator)) {
            self::dispatch($this->runId);
        }
    }

    /**
     * Ein Durchgang. Gibt zurueck, ob danach noch etwas zu tun ist.
     */
    public static function step(int $runId, RegionInfoGenerator $generator): bool
    {
        $batch = [];

        $run = RegionInfoFillRun::update($runId, function (RegionInfoRun $run) use (&$batch) {
            if (! $run->isRunning()) {
                return;
            }

            $pending = (array) $run->pending;
            $type = substr((string) ($pending[0] ?? ''), 0, 1);
            $size = match (true) {
                (bool) ($run->result['web'] ?? false) => 1,
                $type === 'd' => SubregionGenerator::PARALLEL,
                default => RegionInfoGenerator::PARALLEL,
            };

            foreach ($pending as $key) {
                if (count($batch) >= $size || $key[0] !== $type) {
                    break;
                }
                $batch[] = $key;
            }

            $result = (array) $run->result;
            $result['active'] = (array) ($result['active'] ?? []) + array_fill_keys($batch, now()->toIso8601String());
            $run->pending = array_values(array_slice($pending, count($batch)));
            $run->result = $result;
        });

        if (! $run?->isRunning()) {
            return false;
        }

        if ($batch !== []) {
            self::work($run, $batch, $generator);
        }

        return self::finishIfDone($runId);
    }

    /**
     * @param  array<int, string>  $batch
     */
    protected static function work(RegionInfoRun $run, array $batch, RegionInfoGenerator $generator): void
    {
        $type = $batch[0][0];
        $ids = array_map(fn ($key) => (int) substr($key, 1), $batch);
        $targets = $type === 's'
            ? Subregion::query()->with(['region', 'country'])->whereIn('id', $ids)->get()
            : Region::query()->with('country')->whereIn('id', $ids)->get();
        $gone = array_diff($ids, $targets->modelKeys());
        $web = (bool) ($run->result['web'] ?? false);

        try {
            $result = $type === 'd'
                ? ['changed' => []] + app(SubregionGenerator::class)->forRun($run->id)->discover($targets, $web)
                : ['created' => []] + $generator->forRun($run->id)->fillMissing($targets, $web);
        } catch (Throwable $e) {
            $result = ['done' => [], 'changed' => [], 'created' => [], 'failed' => array_fill_keys($targets->modelKeys(), mb_substr($e->getMessage(), 0, 200)), 'usage' => null];
        }

        $fatal = self::fatalError($result['failed']);
        $aborted = false;

        $run = RegionInfoFillRun::update($run->id, function (RegionInfoRun $run) use ($batch, $type, $result, $gone, $fatal, &$aborted) {
            $failed = (array) $run->failed;
            foreach (($fatal === null ? $result['failed'] : []) + array_fill_keys($gone, 'Nicht mehr vorhanden.') as $id => $message) {
                $failed[$type.$id] = $message;
            }

            $state = (array) $run->result;
            $state[$type === 'd' ? 'discovered' : 'filled'] = (int) ($state[$type === 'd' ? 'discovered' : 'filled'] ?? 0) + count($result['done']);
            $state['changed'] = (int) ($state['changed'] ?? 0) + count($result['changed']);
            $state['created'] = (int) ($state['created'] ?? 0) + count($result['created']);
            $state['created_ids'] = [...(array) ($state['created_ids'] ?? []), ...$result['created']];
            $state['tokens'] = (int) ($state['tokens'] ?? 0) + (int) ($result['usage']['total_tokens'] ?? 0);
            $state['active'] = array_diff_key((array) ($state['active'] ?? []), array_flip($batch));

            // Neue Teilgebiete bekommen gleich noch ihre Infos.
            if ($result['created'] !== [] && $run->isRunning()) {
                $run->pending = [...(array) $run->pending, ...array_map(fn ($id) => 's'.$id, $result['created'])];
                $run->total += count($result['created']);
            }

            // Abbruch: Die betroffenen Eintraege bleiben offen, ein neuer Lauf holt sie nach.
            if ($fatal !== null && $run->isRunning()) {
                $run->pending = [...array_map(fn ($id) => $type.$id, array_keys($result['failed'])), ...(array) $run->pending];
                $run->status = RegionInfoRun::STATUS_FAILED;
                $run->error = $fatal;
                $run->finished_at = now();
                $aborted = true;
            }

            $run->done += count($result['done']);
            $run->failed = $failed;
            $run->result = $state;
        });

        if ($aborted) {
            self::notify($run);
            RegionInfoTask::finished($run);
        }
    }

    /**
     * Die erste Fehlermeldung, bei der weiterzumachen nichts bringt.
     *
     * @param  array<int|string, string>  $messages
     */
    public static function fatalError(array $messages): ?string
    {
        foreach ($messages as $message) {
            if (Str::contains(mb_strtolower((string) $message), self::FATAL_ERRORS)) {
                return 'Abgebrochen – OpenAI nimmt keine Anfragen an: '.mb_substr((string) $message, 0, 200);
            }
        }

        return null;
    }

    /**
     * Lauf abschliessen, wenn nichts mehr offen und nichts mehr in Arbeit ist
     * (verlorene Eintraege zaehlen als fehlgeschlagen). Gibt zurueck, ob noch
     * etwas offen ist.
     */
    protected static function finishIfDone(int $runId): bool
    {
        $finished = false;

        $run = RegionInfoFillRun::update($runId, function (RegionInfoRun $run) use (&$finished) {
            if (! $run->isRunning()) {
                return;
            }

            $state = (array) $run->result;
            $failed = (array) $run->failed;

            foreach ((array) ($state['active'] ?? []) as $key => $since) {
                if (now()->subMinutes(self::STALE_MINUTES)->greaterThan($since)) {
                    $failed[$key] = 'Abgebrochen (Zeitüberschreitung).';
                    unset($state['active'][$key]);
                }
            }

            $run->failed = $failed;
            $run->result = $state;

            if ((array) $run->pending === [] && (array) ($state['active'] ?? []) === []) {
                $run->status = RegionInfoRun::STATUS_DONE;
                $run->finished_at = now();
                $finished = true;
            }
        });

        if ($finished) {
            self::notify($run);
            RegionInfoTask::finished($run);

            return false;
        }

        return (bool) $run?->isRunning() && (array) $run->pending !== [];
    }

    /**
     * Abgebrochener Job (Zeitlimit, Absturz des Workers): Der Lauf setzt sich
     * mit einer neuen Kette fort; nach zu vielen Abbruechen gilt er als
     * fehlgeschlagen und die Mail geht trotzdem raus.
     */
    public function failed(?Throwable $exception): void
    {
        $restart = false;

        $run = RegionInfoFillRun::update($this->runId, function (RegionInfoRun $run) use (&$restart, $exception) {
            if (! $run->isRunning()) {
                return;
            }

            $state = (array) $run->result;
            $state['restarts'] = (int) ($state['restarts'] ?? 0) + 1;
            $run->result = $state;

            if ($state['restarts'] <= self::MAX_RESTARTS) {
                $restart = true;

                return;
            }

            $run->status = RegionInfoRun::STATUS_FAILED;
            $run->error = mb_substr((string) $exception?->getMessage(), 0, 300) ?: 'Abgebrochen.';
            $run->finished_at = now();
        });

        if ($restart) {
            self::dispatch($this->runId)->delay(now()->addMinute());
        } elseif ($run?->status === RegionInfoRun::STATUS_FAILED) {
            self::notify($run);
            RegionInfoTask::finished($run);
        }
    }

    /**
     * Bearbeitet, aber ohne neue Angaben (die KI wusste nichts Sicheres).
     */
    public static function unchanged(RegionInfoRun $run): int
    {
        return max(0, (int) ($run->result['filled'] ?? 0) - (int) ($run->result['changed'] ?? 0));
    }

    /**
     * Ergebnis an die beim Start genannten Adressen schicken.
     */
    public static function notify(?RegionInfoRun $run): void
    {
        $recipients = (array) ($run?->result['notify'] ?? []);

        if ($recipients === []) {
            return;
        }

        try {
            Mail::to($recipients)->send(new FillMissingRegionInfoMail($run));
        } catch (Throwable $e) {
            Log::error('Mail zu regions:fill-missing fehlgeschlagen', ['run' => $run->id, 'message' => $e->getMessage()]);
        }
    }
}
