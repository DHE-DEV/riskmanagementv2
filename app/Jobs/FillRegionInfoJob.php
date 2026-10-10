<?php

namespace App\Jobs;

use App\Models\Region;
use App\Models\RegionInfoRun;
use App\Services\RegionInfoGenerator;
use App\Support\AdminV2\RegionInfoFillRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Arbeitet den laufenden KI-Vorbefuellungslauf ein Stueck ab (einige
 * Regionen gleichzeitig) und stellt sich danach selbst wieder hinten an –
 * so kommen andere Jobs (z. B. Benachrichtigungen) zwischendurch dran.
 */
class FillRegionInfoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 290;

    public function __construct(public int $runId) {}

    public function handle(RegionInfoGenerator $generator): void
    {
        $batch = [];

        $run = RegionInfoFillRun::update($this->runId, function (RegionInfoRun $run) use (&$batch) {
            if ($run->isRunning()) {
                $pending = (array) $run->pending;
                $batch = array_slice($pending, 0, RegionInfoGenerator::PARALLEL);
                $run->pending = array_values(array_slice($pending, count($batch)));
            }
        });

        if (! $run?->isRunning()) {
            return;
        }

        if ($batch !== []) {
            $regions = Region::query()->with('country')->whereIn('id', $batch)->get();
            $missing = array_diff($batch, $regions->pluck('id')->all());

            try {
                $result = $generator->fill($regions, $run->overwrite);
            } catch (\Throwable $e) {
                $result = ['done' => [], 'failed' => array_fill_keys($regions->pluck('id')->all(), mb_substr($e->getMessage(), 0, 200))];
            }

            $run = RegionInfoFillRun::update($this->runId, function (RegionInfoRun $run) use ($result, $missing) {
                $run->done += count($result['done']);
                $failed = (array) $run->failed;
                foreach ($result['failed'] + array_fill_keys($missing, 'Region nicht mehr vorhanden.') as $id => $message) {
                    $failed[(string) $id] = $message;
                }
                $run->failed = $failed;
            });
        }

        if (! $run?->isRunning()) {
            return;
        }

        if ((array) $run->pending === []) {
            RegionInfoFillRun::update($this->runId, function (RegionInfoRun $run) {
                if ($run->isRunning()) {
                    $run->status = RegionInfoRun::STATUS_DONE;
                    $run->finished_at = now();
                }
            });

            return;
        }

        self::dispatch($this->runId);
    }
}
