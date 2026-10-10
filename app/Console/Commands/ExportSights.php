<?php

namespace App\Console\Commands;

use App\Support\AdminV2\SightTransfer;
use Illuminate\Console\Command;

/**
 * Sehenswuerdigkeiten von Laendern als JSON-Datei ausgeben – Grundlage einer
 * Migration, die sie auf anderen Systemen anlegt (SightTransfer::import).
 *
 *   php artisan sights:export --country=ES --country=IT --path=database/data/sights-es-it.json
 */
class ExportSights extends Command
{
    protected $signature = 'sights:export
        {--country=* : ISO-Codes der Laender}
        {--path= : Zieldatei relativ zum Projekt}';

    protected $description = 'Sehenswuerdigkeiten von Laendern als JSON-Datei fuer eine Migration ausgeben';

    public function handle(): int
    {
        $countries = array_filter((array) $this->option('country'));
        $path = (string) $this->option('path');

        if ($countries === [] || $path === '') {
            $this->error('Bitte --country und --path angeben.');

            return self::FAILURE;
        }

        $data = SightTransfer::export($countries);
        $file = base_path($path);
        @mkdir(dirname($file), 0755, true);
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");

        foreach ($data as $iso => $regions) {
            $this->line($iso.': '.count($regions).' Regionen, '.collect($regions)->flatten(1)->count().' Sehenswürdigkeiten');
        }
        $this->info('Gespeichert in '.$path);

        return self::SUCCESS;
    }
}
