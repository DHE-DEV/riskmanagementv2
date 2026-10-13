<?php

namespace App\Support\AdminV2;

use App\Models\Country;
use App\Models\Region;
use App\Models\RegionInfoRun;
use App\Models\Subregion;
use App\Services\RegionInfoGenerator;
use Illuminate\Support\Collection;

/**
 * Gemeinsames von regions:fill-missing und dem Kasten "KI-Lauf" an der
 * Aufgabe "Regionsinfos per KI ergänzen – <Land>": was fehlt (plan), ob fuer
 * ein Land schon ein Lauf laeuft, Anhalten und die Namen fehlgeschlagener
 * Eintraege. Abgearbeitet wird der Lauf von FillMissingRegionInfoJob.
 */
class RegionInfoFill
{
    /**
     * Regionen und Teilgebiete mit Luecken (je ID Land und Luecken) und die
     * Regionen, in denen nach neuen Teilgebieten gesucht wird.
     *
     * @param  Collection<int, Country>  $countries
     * @param  string  $only  "", "regions" oder "subregions"
     * @param  array<int, int>  $subregionIds  nur diese Teilgebiete – ohne Regionen und ohne Suche
     * @return array{regions: Collection<int, array>, subregions: Collection<int, array>, discover: Collection<int, array>}
     */
    public static function plan(
        Collection $countries,
        string $only = '',
        bool $discover = true,
        bool $rediscover = false,
        array $subregionIds = [],
        int $limit = 0,
    ): array {
        $generator = app(RegionInfoGenerator::class);
        $countryIds = $countries->map(fn (Country $country) => $country->getKey())->all();

        if ($subregionIds !== []) {
            $only = 'subregions';
        }

        $regions = $only === 'subregions' ? collect() : self::withGaps(Region::query()->whereIn('country_id', $countryIds)->orderBy('country_id')->orderBy('id')->get(), $generator);
        $subregions = $only === 'regions' ? collect() : self::withGaps(Subregion::query()->whereIn('country_id', $countryIds)->when($subregionIds !== [], fn ($query) => $query->whereIn('id', $subregionIds))->orderBy('country_id')->orderBy('region_id')->orderBy('sort_order')->get(), $generator);

        $search = $only === 'regions' || $subregionIds !== [] || ! $discover ? collect() : Region::query()
            ->whereIn('country_id', $countryIds)
            ->when(! $rediscover, fn ($query) => $query->whereNull('subregions_checked_at'))
            ->orderBy('country_id')->orderBy('id')
            ->get(['id', 'country_id'])
            ->mapWithKeys(fn (Region $region) => [$region->id => ['country_id' => $region->country_id]]);

        if ($limit > 0) {
            $regions = $regions->take($limit);
            $search = $search->take(max(0, $limit - $regions->count()));
            $subregions = $subregions->take(max(0, $limit - $regions->count() - $search->count()));
        }

        return ['regions' => $regions, 'subregions' => $subregions, 'discover' => $search];
    }

    /**
     * Nur Eintraege mit Luecken, je ID die Luecken.
     *
     * @param  Collection<int, Region|Subregion>  $targets
     * @return Collection<int, array>
     */
    protected static function withGaps(Collection $targets, RegionInfoGenerator $generator): Collection
    {
        return $targets
            ->mapWithKeys(fn (Region|Subregion $target) => [$target->id => ['country_id' => $target->country_id, 'missing' => $generator->missingFor($target)]])
            ->filter(fn (array $item) => RegionInfo::missingCount($item['missing']) > 0);
    }

    /**
     * Der laufende Lauf, an dem das Land beteiligt ist.
     */
    public static function runningFor(Country $country): ?RegionInfoRun
    {
        return RegionInfoRun::query()
            ->where('kind', RegionInfoRun::KIND_FILL_MISSING)
            ->where('status', RegionInfoRun::STATUS_RUNNING)
            ->whereJsonContains('result->country_ids', $country->id)
            ->latest('id')
            ->first();
    }

    /**
     * Anhalten: Offenes faellt weg, was gerade in Arbeit ist, wird noch fertig.
     * Die Aufgabe des Laufs steht danach wieder auf offen.
     */
    public static function cancel(int $runId): ?RegionInfoRun
    {
        $run = RegionInfoFillRun::update($runId, function (RegionInfoRun $run) {
            if ($run->kind === RegionInfoRun::KIND_FILL_MISSING && $run->isRunning()) {
                $run->status = RegionInfoRun::STATUS_CANCELLED;
                $run->pending = [];
                $run->finished_at = now();
            }
        });

        if ($run?->status !== RegionInfoRun::STATUS_CANCELLED) {
            return null;
        }

        RegionInfoTask::finished($run);

        return $run;
    }

    /**
     * Fehlgeschlagene Eintraege mit Namen.
     *
     * @return Collection<int, array{key: string, name: string, message: string}>
     */
    public static function failures(RegionInfoRun $run): Collection
    {
        $failed = (array) $run->failed;
        $ids = fn (string $type) => array_map(fn ($key) => (int) substr($key, 1), array_filter(array_keys($failed), fn ($key) => $key[0] === $type));

        $names = Region::withTrashed()->whereIn('id', [...$ids('r'), ...$ids('d')])->get()
            ->flatMap(fn (Region $region) => ['r'.$region->id => $region->getName('de'), 'd'.$region->id => $region->getName('de').' (Suche nach Teilgebieten)'])->all()
            + Subregion::withTrashed()->whereIn('id', $ids('s'))->get()
                ->mapWithKeys(fn (Subregion $subregion) => ['s'.$subregion->id => $subregion->getName('de').' ('.$subregion->kindLabel().')'])->all();

        return collect($failed)->map(fn ($message, $key) => ['key' => (string) $key, 'name' => $names[$key] ?? (string) $key, 'message' => (string) $message])->values();
    }
}
