<?php

namespace App\Jobs;

use App\Models\RegionInfoRun;
use App\Services\RegionInfoGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * KI-Vorschlag fuer eine Region im Hintergrund holen (dauert oft laenger,
 * als eine Web-Anfrage darf). Das Ergebnis liegt am Lauf; der Editor
 * uebernimmt es ins Formular, gespeichert wird erst mit "Speichern".
 */
class SuggestRegionInfoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 290;

    public function __construct(public int $runId) {}

    public function handle(RegionInfoGenerator $generator): void
    {
        $run = RegionInfoRun::query()->with('region.country')->find($this->runId);

        if (! $run || ! $run->isRunning()) {
            return;
        }

        try {
            if (! $run->region) {
                throw new \RuntimeException('Die Region gibt es nicht mehr.');
            }

            $run->update(['status' => RegionInfoRun::STATUS_DONE, 'result' => $generator->suggest($run->region), 'finished_at' => now()]);
        } catch (\Throwable $e) {
            $run->update([
                'status' => RegionInfoRun::STATUS_FAILED,
                'error' => mb_substr(str_replace('Fehler bei der Kommunikation mit ChatGPT: ', '', $e->getMessage()), 0, 300),
                'finished_at' => now(),
            ]);
        }
    }
}
