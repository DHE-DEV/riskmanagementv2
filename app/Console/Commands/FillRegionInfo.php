<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\Region;
use App\Services\RegionInfoGenerator;
use App\Support\AdminV2\RegionInfo;
use Illuminate\Console\Command;

/**
 * Regionsinfos per KI vorbefuellen – fuer grosse Mengen auf dem Server, ohne
 * Zeitlimit einer Web-Anfrage. Gespeichert wird als ungepruefter KI-Entwurf.
 *
 *   php artisan regions:fill-info --country=IT --country=ES
 *   php artisan regions:fill-info --limit=50
 *   php artisan regions:fill-info --all --overwrite
 */
class FillRegionInfo extends Command
{
    protected $signature = 'regions:fill-info
        {--country=* : Nur Regionen dieser Laender (ISO-Code)}
        {--region=* : Nur diese Regionen (ID)}
        {--all : Auch Regionen, die schon Infos haben}
        {--overwrite : Vorhandene Texte ueberschreiben (sonst nur leere Felder fuellen)}
        {--limit=0 : Hoechstens so viele Regionen}
        {--parallel=4 : So viele Anfragen gleichzeitig}
        {--dry-run : Nur zaehlen}';

    protected $description = 'Regionsinfos (Beschreibung, Reiseinfos, Fakten) per KI vorbefuellen';

    public function handle(RegionInfoGenerator $generator): int
    {
        $query = Region::query()->with('country')->orderBy('country_id')->orderBy('id');

        if ($countries = array_filter(array_map('strtoupper', (array) $this->option('country')))) {
            $query->whereIn('country_id', Country::query()->whereIn('iso_code', $countries)->pluck('id'));
        }

        if ($ids = array_filter(array_map('intval', (array) $this->option('region')))) {
            $query->whereIn('id', $ids);
        }

        $regions = $query->get()
            ->when(! $this->option('all') && ! $this->option('overwrite'), fn ($regions) => $regions->filter(fn (Region $region) => ! RegionInfo::hasContent($region->info)))
            ->values();

        if ($limit = (int) $this->option('limit')) {
            $regions = $regions->take($limit);
        }

        $this->info($regions->count().' Regionen'.($this->option('dry-run') ? ' (nur gezählt)' : ''));

        if ($this->option('dry-run') || $regions->isEmpty()) {
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($regions->count());
        $failed = [];
        $tokens = 0;

        foreach ($regions->chunk(max(1, (int) $this->option('parallel'))) as $chunk) {
            $result = $generator->fill($chunk->values(), (bool) $this->option('overwrite'));
            $failed += $result['failed'];
            $tokens += (int) ($result['usage']['total_tokens'] ?? 0);
            $bar->advance($chunk->count());
        }

        $bar->finish();
        $this->newLine(2);
        $this->info(($regions->count() - count($failed)).' vorbefüllt, '.count($failed).' fehlgeschlagen, '.number_format($tokens, 0, ',', '.').' Tokens.');

        foreach ($failed as $id => $message) {
            $this->warn("Region {$id}: {$message}");
        }

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }
}
