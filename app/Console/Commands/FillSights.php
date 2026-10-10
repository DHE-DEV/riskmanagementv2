<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\Region;
use App\Services\SightGenerator;
use Illuminate\Console\Command;

/**
 * Sehenswuerdigkeiten je Region per KI anlegen – fuer grosse Mengen auf dem
 * Server. Angelegt wird als ungepruefter KI-Entwurf; Dubletten im Land werden
 * uebersprungen, Koordinaten mit OpenStreetMap abgeglichen.
 *
 *   php artisan sights:fill --country=IT --country=ES
 *   php artisan sights:fill --region=49 --all
 */
class FillSights extends Command
{
    protected $signature = 'sights:fill
        {--country=* : Nur Regionen dieser Laender (ISO-Code)}
        {--region=* : Nur diese Regionen (ID)}
        {--all : Auch Regionen, die schon Sehenswuerdigkeiten haben (ergaenzt nur Neue)}
        {--limit=0 : Hoechstens so viele Regionen}
        {--dry-run : Nur zaehlen}';

    protected $description = 'Sehenswuerdigkeiten der Regionen per KI anlegen';

    public function handle(SightGenerator $generator): int
    {
        $query = Region::query()->with('country')->withCount('sights')->orderBy('country_id')->orderBy('id');

        if ($countries = array_filter(array_map('strtoupper', (array) $this->option('country')))) {
            $query->whereIn('country_id', Country::query()->whereIn('iso_code', $countries)->pluck('id'));
        }

        if ($ids = array_filter(array_map('intval', (array) $this->option('region')))) {
            $query->whereIn('id', $ids);
        }

        $regions = $query->get()
            ->when(! $this->option('all'), fn ($regions) => $regions->where('sights_count', 0))
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
        $created = 0;
        $tokens = 0;

        foreach ($regions->chunk(SightGenerator::PARALLEL) as $chunk) {
            $result = $generator->fill($chunk->values());
            $failed += $result['failed'];
            $created += $result['created'];
            $tokens += (int) ($result['usage']['total_tokens'] ?? 0);
            $bar->advance($chunk->count());
        }

        $bar->finish();
        $this->newLine(2);
        $this->info(($regions->count() - count($failed)).' Regionen bearbeitet, '.$created.' Sehenswürdigkeiten angelegt, '.count($failed).' fehlgeschlagen, '.number_format($tokens, 0, ',', '.').' Tokens.');

        foreach ($failed as $id => $message) {
            $this->warn("Region {$id}: {$message}");
        }

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }
}
