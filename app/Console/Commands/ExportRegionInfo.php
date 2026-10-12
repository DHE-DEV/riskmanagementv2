<?php

namespace App\Console\Commands;

use App\Support\AdminV2\RegionInfoTransfer;
use Illuminate\Console\Command;

/**
 * Regionsinfos und Teilgebiete (mit Infos und Zuordnung) von Laendern als
 * JSON-Datei ausgeben – Grundlage einer Migration, die auf anderen Systemen
 * die Luecken fuellt (RegionInfoTransfer::import).
 *
 *   php artisan regions:export-info --country=ES --country=IT --path=database/data/region-info-es-it.json
 */
class ExportRegionInfo extends Command
{
    protected $signature = 'regions:export-info
        {--country=* : ISO-Codes der Laender}
        {--path= : Zieldatei relativ zum Projekt}';

    protected $description = 'Regionsinfos und Teilgebiete von Laendern als JSON-Datei fuer eine Migration ausgeben';

    public function handle(): int
    {
        $countries = array_filter((array) $this->option('country'));
        $path = (string) $this->option('path');

        if ($countries === [] || $path === '') {
            $this->error('Bitte --country und --path angeben.');

            return self::FAILURE;
        }

        $data = RegionInfoTransfer::export($countries);
        $file = base_path($path);
        @mkdir(dirname($file), 0755, true);
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");

        foreach ($data as $iso => $item) {
            $this->line($iso.': '.count($item['regions']).' Regionen, '.collect($item['subregions'])->flatten(1)->count().' Teilgebiete');
        }
        $this->info('Gespeichert in '.$path);

        return self::SUCCESS;
    }
}
