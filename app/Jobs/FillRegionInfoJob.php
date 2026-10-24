<?php

namespace App\Jobs;

use App\Models\City;
use App\Models\Region;
use App\Models\RegionInfoRun;
use App\Models\Subregion;
use App\Services\RegionInfoGenerator;
use App\Services\SightGenerator;
use App\Support\AdminV2\RegionInfoFillRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Arbeitet einen laufenden KI-Lauf der Regionen (Regionsinfos oder
 * Sehenswuerdigkeiten) ein Stueck ab (einige
 * Regionen gleichzeitig) und stellt sich danach selbst wieder hinten an –
 * so kommen andere Jobs (z. B. Benachrichtigungen) zwischendurch dran.
 */
class FillRegionInfoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 290;

    public function __construct(public int $runId) {}

    public function handle(RegionInfoGenerator $generator, ?SightGenerator $sights = null): void
    {
        $batch = [];
        $sights ??= app(SightGenerator::class);

        $run = RegionInfoFillRun::update($this->runId, function (RegionInfoRun $run) use (&$batch) {
            if ($run->isRunning()) {
                $pending = (array) $run->pending;
                $batch = array_slice($pending, 0, $run->isSights() ? SightGenerator::PARALLEL : RegionInfoGenerator::PARALLEL);
                $run->pending = array_values(array_slice($pending, count($batch)));
            }
        });

        if (! $run?->isRunning()) {
            return;
        }

        if ($batch !== []) {
            if ($run->kind === RegionInfoRun::KIND_SIGHTS_COUNTRY) {
                // Gemischte Liste: "region:ID", "subregion:ID", "city:ID"
                [$regions, $missing] = self::places($batch);
            } else {
                $regions = Region::query()->with('country')->whereIn('id', $batch)->get();
                $missing = array_diff($batch, $regions->pluck('id')->all());
            }

            try {
                $result = $run->isSights()
                    ? $sights->fill($regions)
                    : $generator->fill($regions, $run->overwrite);
            } catch (\Throwable $e) {
                $ids = $run->isSights() ? $regions->map(fn ($place) => SightGenerator::id($place))->all() : $regions->pluck('id')->all();
                $result = ['done' => [], 'failed' => array_fill_keys($ids, mb_substr($e->getMessage(), 0, 200))];
            }

            $run = RegionInfoFillRun::update($this->runId, function (RegionInfoRun $run) use ($result, $missing) {
                $run->done += count($result['done']);
                if (isset($result['created'])) {
                    // Weitere Angaben des Laufs (z. B. country_id) bleiben erhalten.
                    $run->result = array_merge((array) $run->result, ['created' => (int) ($run->result['created'] ?? 0) + $result['created']]);
                }
                $failed = (array) $run->failed;
                foreach ($result['failed'] + array_fill_keys($missing, 'Gebiet nicht mehr vorhanden.') as $id => $message) {
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

    /**
     * Gebiete zu den Schluesseln eines Laender-Laufs, in derselben Reihenfolge; dazu die nicht mehr vorhandenen.
     *
     * @param  array<int, string>  $keys
     * @return array{0: \Illuminate\Support\Collection<int, Region|Subregion|City>, 1: array<int, string>}
     */
    public static function places(array $keys): array
    {
        $ids = ['region' => [], 'subregion' => [], 'city' => []];
        foreach ($keys as $key) {
            [$type, $id] = array_pad(explode(':', (string) $key, 2), 2, '');
            if (isset($ids[$type])) {
                $ids[$type][] = (int) $id;
            }
        }
        $found = collect()
            ->merge(Region::query()->with('country')->whereIn('id', $ids['region'])->get())
            ->merge(Subregion::query()->with('region.country')->whereIn('id', $ids['subregion'])->get())
            ->merge(City::query()->with(['region.country', 'subregion'])->whereIn('id', $ids['city'])->get())
            ->keyBy(fn ($place) => SightGenerator::key($place));

        $places = collect($keys)->map(fn ($key) => $found->get((string) $key))->filter()->values();
        $missing = array_values(array_diff(array_map('strval', $keys), $found->keys()->all()));

        return [$places, $missing];
    }
}
