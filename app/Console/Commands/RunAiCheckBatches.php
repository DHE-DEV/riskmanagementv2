<?php

namespace App\Console\Commands;

use App\Models\AiCheckRun;
use App\Services\AiCheckBatchService;
use Illuminate\Console\Command;

/**
 * Fuehrt laufende Sammellaeufe von KI-Pruefungen weiter (System > KI): jede
 * Pruefung wird fuer alle Datensaetze ihres Bereichs ausgefuehrt und legt
 * dabei Unteraufgaben an, wo ihre Bedingung zutrifft.
 */
class RunAiCheckBatches extends Command
{
    protected $signature = 'ai:run-check-batches {--seconds=50 : So lange wird je Aufruf hoechstens gearbeitet}';

    protected $description = 'Führt laufende Sammelläufe von KI-Prüfungen weiter';

    public function handle(AiCheckBatchService $batches): int
    {
        set_time_limit(0);

        $deadline = microtime(true) + max(5, (int) $this->option('seconds'));
        $runs = AiCheckRun::query()->where('status', AiCheckRun::STATUS_RUNNING)->orderBy('id')->get();

        foreach ($runs as $run) {
            $remaining = (int) floor($deadline - microtime(true));

            if ($remaining < 1) {
                break;
            }

            $run = $batches->advance($run, $remaining);

            $this->info("Lauf {$run->id}: {$run->processed} von {$run->total} geprüft, {$run->created} Unteraufgaben, Status {$run->status}.");
        }

        return self::SUCCESS;
    }
}
