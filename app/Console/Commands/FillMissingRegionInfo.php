<?php

namespace App\Console\Commands;

use App\Jobs\FillMissingRegionInfoJob;
use App\Models\AdminTask;
use App\Models\Country;
use App\Models\Region;
use App\Models\RegionInfoRun;
use App\Models\Subregion;
use App\Services\RegionInfoGenerator;
use App\Support\AdminV2\RegionInfo;
use App\Support\AdminV2\RegionInfoFill;
use App\Support\AdminV2\RegionInfoTask;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Luecken der Regionsinfos per KI fuellen – je Land fuer alle Regionen und
 * Teilgebiete, nur leere Felder bzw. fehlende Sprachen. Dazwischen sucht die
 * KI je Region nach fehlenden Teilgebieten (Inseln, Reisegebiete), legt sie
 * an, ordnet Staedte zu und fuellt danach auch deren Infos. Vorhandenes bleibt
 * unangetastet; Ergaenztes wird zum ungeprueften KI-Entwurf. Laeuft in der
 * Queue und meldet sich am Ende per Mail. Laeuft er fuer genau ein Land,
 * erledigt er dessen Aufgabe "Regionsinfos per KI ergänzen – <Land>".
 *
 *   php artisan regions:fill-missing --country=ES --dry-run
 *   php artisan regions:fill-missing --country=ES --country=IT --notify=a@passolution.de,b@passolution.de
 *   php artisan regions:fill-missing --country=PT --web --notify=a@passolution.de
 *   php artisan regions:fill-missing --country=ES --subregion=694 --web
 *   php artisan regions:fill-missing --status=12
 *   php artisan regions:fill-missing --cancel=12
 */
class FillMissingRegionInfo extends Command
{
    protected $signature = 'regions:fill-missing
        {--country=* : Laender (ISO-Code), mehrfach moeglich}
        {--all : Alle Laender}
        {--only= : Nur "regions" oder nur "subregions"}
        {--subregion=* : Nur diese Teilgebiete (ID), mehrfach moeglich – ohne Regionen und ohne Suche nach neuen Teilgebieten}
        {--web : Die KI recherchiert im Internet (genauer, aber langsamer und teurer)}
        {--no-discover : Keine neuen Teilgebiete recherchieren und anlegen}
        {--rediscover : Auch in Regionen nach Teilgebieten suchen, die schon durchsucht wurden}
        {--limit=0 : Hoechstens so viele Eintraege (Regionen und Teilgebiete zusammen)}
        {--notify= : E-Mail-Adressen fuer die Meldung am Ende, mit Komma getrennt}
        {--task= : Diese Aufgabe am Ende erledigen (ID) – bei genau einem Land sonst dessen offene Aufgabe "Regionsinfos per KI ergänzen"}
        {--sync : Direkt hier abarbeiten statt in der Queue}
        {--dry-run : Nur zaehlen, was fehlt}
        {--status= : Stand eines Laufs anzeigen (ID)}
        {--cancel= : Einen Lauf anhalten (ID)}';

    protected $description = 'Fehlende Regionsinfos von Regionen und Teilgebieten per KI ergaenzen (je Land, in der Queue, Mail am Ende)';

    public function handle(RegionInfoGenerator $generator): int
    {
        if ($this->option('status')) {
            return $this->showStatus((int) $this->option('status'));
        }

        if ($this->option('cancel')) {
            return $this->cancel((int) $this->option('cancel'));
        }

        $codes = array_values(array_filter(array_map(fn ($code) => strtoupper(trim($code)), (array) $this->option('country'))));

        if ($codes === [] && ! $this->option('all')) {
            $this->error('Bitte Länder mit --country=XX angeben (oder --all für alle Länder).');

            return self::INVALID;
        }

        $only = (string) $this->option('only');
        if ($only !== '' && ! in_array($only, ['regions', 'subregions'], true)) {
            $this->error('--only kennt nur "regions" oder "subregions".');

            return self::INVALID;
        }

        $notify = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('notify')))));
        if ($invalid = array_filter($notify, fn ($address) => ! filter_var($address, FILTER_VALIDATE_EMAIL))) {
            $this->error('Ungültige E-Mail-Adresse: '.implode(', ', $invalid));

            return self::INVALID;
        }

        $subregionIds = array_values(array_filter(array_map('intval', (array) $this->option('subregion'))));
        if ($subregionIds !== []) {
            $only = 'subregions';
        }

        $countries = Country::query()->when($codes !== [], fn ($query) => $query->whereIn('iso_code', $codes))->orderBy('iso_code')->get();

        if ($unknown = array_diff($codes, $countries->pluck('iso_code')->map(fn ($code) => strtoupper($code))->all())) {
            $this->error('Unbekanntes Land: '.implode(', ', $unknown));

            return self::INVALID;
        }

        ['regions' => $regions, 'subregions' => $subregions, 'discover' => $discover] = RegionInfoFill::plan(
            $countries,
            $only,
            ! $this->option('no-discover'),
            (bool) $this->option('rediscover'),
            $subregionIds,
            (int) $this->option('limit'),
        );

        $this->summary($countries, $regions, $subregions, $discover);

        $task = $this->option('task')
            ? AdminTask::query()->find((int) $this->option('task'))
            : ($countries->count() === 1 && $only === '' && ! $this->option('limit') ? RegionInfoTask::openFor($countries->first()) : null);

        if ($this->option('task') && ! $task) {
            $this->error('Aufgabe #'.$this->option('task').' gibt es nicht.');

            return self::INVALID;
        }

        if ($task) {
            $this->line("Aufgabe #{$task->id} „{$task->title}“ wird ".($this->option('dry-run') ? 'beim Lauf' : 'am Ende').' erledigt.');
        }

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        if ($regions->isEmpty() && $subregions->isEmpty() && $discover->isEmpty()) {
            $this->info('Nichts zu tun – keine Lücken.');

            if ($task) {
                RegionInfoTask::nothingToDo($task);
            }

            return self::SUCCESS;
        }

        if ($busy = $countries->filter(fn (Country $country) => RegionInfoFill::runningFor($country))->map(fn (Country $country) => $country->getName('de').' (Lauf #'.RegionInfoFill::runningFor($country)->id.')')->values()->all()) {
            $this->error('Für diese Länder läuft schon ein Lauf: '.implode(', ', $busy));

            return self::FAILURE;
        }

        $run = FillMissingRegionInfoJob::start(
            $regions->keys()->all(),
            $subregions->keys()->all(),
            $countries->mapWithKeys(fn (Country $country) => [$country->id => $country->getName('de')])->all(),
            (bool) $this->option('web'),
            $notify,
            $task?->id,
            $discover->keys()->all(),
        );

        if ($task) {
            RegionInfoTask::started($task, $run);
        }

        if (! $this->option('sync')) {
            FillMissingRegionInfoJob::dispatch($run->id);

            $this->info("Lauf #{$run->id} in die Queue gestellt.".($notify ? ' Am Ende geht eine Mail an '.implode(', ', $notify).'.' : ''));
            $this->line("Stand: php artisan regions:fill-missing --status={$run->id}");
            $this->line("Anhalten: php artisan regions:fill-missing --cancel={$run->id}");

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($run->total);

        while (FillMissingRegionInfoJob::step($run->id, $generator)) {
            $bar->setProgress($run->total - count((array) $run->refresh()->pending));
        }

        $bar->finish();
        $this->newLine(2);

        return $this->showStatus($run->id);
    }

    protected function summary(Collection $countries, Collection $regions, Collection $subregions, Collection $discover): void
    {
        $rows = $countries
            ->map(function (Country $country) use ($regions, $subregions, $discover) {
                $r = $regions->where('country_id', $country->id);
                $s = $subregions->where('country_id', $country->id);

                return [
                    $country->iso_code.' '.$country->getName('de'),
                    $r->count(),
                    $discover->where('country_id', $country->id)->count(),
                    $s->count(),
                    $r->merge($s)->sum(fn (array $item) => RegionInfo::missingCount($item['missing'])),
                ];
            })
            ->filter(fn (array $row) => $row[1] + $row[2] + $row[3] > 0)
            ->values();

        if ($rows->isNotEmpty()) {
            $this->table(['Land', 'Regionen mit Lücken', 'Regionen: neue Teilgebiete suchen', 'Teilgebiete mit Lücken', 'Lücken (Feld × Sprache)'], $rows->all());
        }

        $this->info($regions->count().' Regionen, '.$subregions->count().' Teilgebiete, Suche nach neuen Teilgebieten in '.$discover->count().' Regionen'.($this->option('dry-run') ? ' (nur gezählt)' : ''));

        if ($discover->isNotEmpty()) {
            $this->line('Neu angelegte Teilgebiete bekommen im selben Lauf ihre Infos.');
        }
    }

    protected function showStatus(int $id): int
    {
        $run = RegionInfoRun::query()->where('kind', RegionInfoRun::KIND_FILL_MISSING)->find($id);

        if (! $run) {
            $this->error("Lauf #{$id} gibt es nicht.");

            return self::FAILURE;
        }

        $result = (array) $run->result;
        $failed = (array) $run->failed;

        $this->table(['Lauf', 'Stand'], [
            ['Länder', implode(', ', (array) ($result['countries'] ?? []))],
            ['Status', match ($run->status) {
                RegionInfoRun::STATUS_RUNNING => 'läuft',
                RegionInfoRun::STATUS_DONE => 'fertig',
                RegionInfoRun::STATUS_CANCELLED => 'angehalten',
                default => 'abgebrochen'.($run->error ? ': '.$run->error : ''),
            }],
            ['Bearbeitet', ($run->done + count($failed)).' von '.$run->total.' Schritten'],
            ['Ergänzt', (int) ($result['changed'] ?? 0)],
            ['Teilgebiete neu angelegt', (int) ($result['created'] ?? 0)],
            ['Ohne neue Angaben', FillMissingRegionInfoJob::unchanged($run)],
            ['Fehlgeschlagen', count($failed)],
            ['Tokens', number_format((int) ($result['tokens'] ?? 0), 0, ',', '.')],
        ]);

        foreach (RegionInfoFill::failures($run) as $failure) {
            $this->warn($failure['name'].' (#'.substr($failure['key'], 1).'): '.$failure['message']);
        }

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }

    protected function cancel(int $id): int
    {
        if (! RegionInfoFill::cancel($id)) {
            $this->error("Lauf #{$id} läuft nicht.");

            return self::FAILURE;
        }

        $this->info("Lauf #{$id} angehalten – Einträge, die gerade in Arbeit sind, werden noch fertig.");

        return self::SUCCESS;
    }
}
