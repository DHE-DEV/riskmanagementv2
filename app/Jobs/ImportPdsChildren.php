<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\User;
use App\Notifications\PdsChildrenImportCompleted;
use App\Services\PdsAccountImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Importiert die Unteraccounts einer Agentur aus pds-admin im Hintergrund
 * (Admin v2 › Kunde › Unteraccounts). Die Liste liegt im Cache (queueKey),
 * den Fortschritt schreibt der Job unter progressKey – die Importseite liest
 * ihn von dort. Am Ende bekommt der Admin, der den Import gestartet hat, eine
 * E-Mail. Bricht der Job ab, setzt ein neuer Lauf fort: Vorhandenes wird erkannt.
 *
 * Wie FillRegionInfoJob arbeitet ein Job nur $runSeconds lang und stellt sich
 * dann fuer den Rest neu ein – so bleibt er unter dem Worker-Timeout und wird
 * nicht wegen retry_after doppelt ausgeliefert.
 */
class ImportPdsChildren implements ShouldQueue
{
    use Queueable;

    public int $timeout = 290;

    /** Arbeitszeit je Job in Sekunden, danach folgt der naechste. */
    public static int $runSeconds = 60;

    public int $tries = 1;

    /** Fortschritt so oft speichern (je n Unteraccounts). */
    protected const PROGRESS_EVERY = 10;

    public function __construct(
        public int $agencyId,
        public int $userId,
        public string $runKey,
    ) {}

    public static function queueKey(string $runKey): string
    {
        return $runKey.':queue';
    }

    public static function progressKey(string $runKey): string
    {
        return $runKey.':progress';
    }

    /**
     * Laufender Import einer Agentur (Cache-Schluessel des Laufs) – damit es je
     * Agentur nur einen gibt und die Seite sich nach dem Neuladen wieder anhaengt.
     */
    public static function agencyKey(int $agencyId): string
    {
        return 'adminv2:pds-children-import:agency:'.$agencyId;
    }

    /** E-Mail-Adresse, an die die Abschluss-Mail geht (fuer die Anzeige). */
    public static function notifyKey(string $runKey): string
    {
        return $runKey.':notify';
    }

    /**
     * Ist der Job dieses Laufs fehlgeschlagen, ohne dass failed() lief – etwa
     * weil ein veralteter Worker die Klasse nicht kennt ("incomplete class")?
     */
    public static function hasFailedJob(string $runKey): bool
    {
        return DB::table('failed_jobs')->where('payload', 'like', '%'.addcslashes($runKey, '%_\\').'%')->exists();
    }

    public static function cancelKey(string $runKey): string
    {
        return $runKey.':cancel';
    }

    /**
     * Import abbrechen: Sperre sofort frei, als beendet melden; der Job hoert
     * beim naechsten Unteraccount auf.
     */
    public static function cancel(int $agencyId, string $runKey): void
    {
        Cache::put(self::cancelKey($runKey), true, now()->addDay());

        $progress = self::progress($runKey) ?? ['done' => 0, 'total' => 0, 'result' => []];
        Cache::put(self::progressKey($runKey), [...$progress, 'finished' => true, 'error' => 'Der Import wurde abgebrochen. Ein neuer Lauf setzt fort – bereits Importiertes wird erkannt.'], now()->addDay());
        Cache::forget(self::queueKey($runKey));

        if (Cache::get(self::agencyKey($agencyId)) === $runKey) {
            Cache::forget(self::agencyKey($agencyId));
        }
    }

    /**
     * @return array{done: int, total: int, result: array<string, int>, finished: bool, error: ?string}|null
     */
    public static function progress(string $runKey): ?array
    {
        $progress = Cache::get(self::progressKey($runKey));

        return is_array($progress) ? $progress : null;
    }

    public function handle(PdsAccountImportService $service): void
    {
        $agency = Customer::findOrFail($this->agencyId);
        // Abgebrochen: still aufhoeren, cancel() hat schon alles gemeldet.
        if (Cache::has(self::cancelKey($this->runKey))) {
            return;
        }

        $queue = Cache::get(self::queueKey($this->runKey));

        if (! is_array($queue)) {
            $this->finish(['done' => 0, 'total' => 0, 'result' => []], 'Die Liste der Unteraccounts war nicht mehr vorhanden. Bitte erneut prüfen und importieren.');

            return;
        }

        $stored = self::progress($this->runKey);
        $progress = [
            'done' => (int) ($stored['done'] ?? 0),
            'total' => count($queue),
            'result' => (array) ($stored['result'] ?? []),
        ];
        $deadline = microtime(true) + self::$runSeconds;

        foreach (array_slice($queue, $progress['done']) as [$child, $status]) {
            if (Cache::has(self::cancelKey($this->runKey))) {
                return;
            }

            $status ??= $service->import($child, $agency)['status'];
            $progress['result'][$status] = ($progress['result'][$status] ?? 0) + 1;
            $progress['done']++;

            if ($progress['done'] % self::PROGRESS_EVERY === 0) {
                $this->store($progress);
            }

            if ($progress['done'] < $progress['total'] && microtime(true) >= $deadline) {
                $this->store($progress);
                self::dispatch($this->agencyId, $this->userId, $this->runKey);

                return;
            }
        }

        $this->finish($progress);
    }

    public function failed(?Throwable $exception): void
    {
        if (Cache::has(self::cancelKey($this->runKey))) {
            return;
        }

        $progress = self::progress($this->runKey) ?? ['done' => 0, 'total' => 0, 'result' => []];

        $this->finish($progress, 'Der Import ist abgebrochen'.($exception ? ': '.$exception->getMessage() : '.').' Ein neuer Lauf setzt fort – bereits Importiertes wird erkannt.');
    }

    /**
     * @param  array{done: int, total: int, result: array<string, int>}  $progress
     */
    protected function store(array $progress, bool $finished = false, ?string $error = null): void
    {
        Cache::put(self::progressKey($this->runKey), [...$progress, 'finished' => $finished, 'error' => $error], now()->addDay());
    }

    /**
     * @param  array{done: int, total: int, result: array<string, int>}  $progress
     */
    protected function finish(array $progress, ?string $error = null): void
    {
        $this->store($progress, true, $error);
        Cache::forget(self::queueKey($this->runKey));

        if (Cache::get(self::agencyKey($this->agencyId)) === $this->runKey) {
            Cache::forget(self::agencyKey($this->agencyId));
        }

        $agency = Customer::withTrashed()->find($this->agencyId);

        if ($agency) {
            User::find($this->userId)?->notify(new PdsChildrenImportCompleted($agency, $progress['result'], $progress['total'], $error));
        }
    }
}
