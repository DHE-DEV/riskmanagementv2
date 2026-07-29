<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Einmal-Helfer: nachdem eine ältere Live-Datenbank auf diese Umgebung kopiert
 * wurde (ohne die `migrations`-Tabelle abzugleichen), fehlen einzelne
 * Schema-Änderungen — obwohl ihre Migration in der `migrations`-Tabelle bereits
 * als "gelaufen" markiert ist. Dieser Command prüft jede in `TARGETS` gelistete
 * Änderung, entfernt bei Bedarf den Migration-Eintrag und startet danach
 * `migrate --force`, damit die Änderung erneut angewandt wird.
 *
 * Aktuell abgedeckte Fälle (Stand 2026-07-29):
 * - `custom_events.title_translations` + `.popup_content_translations`
 *   (Migration 2026_06_22_120000_add_translations_to_custom_events_table).
 */
class ResetMigrationsAfterRestore extends Command
{
    protected $signature = 'migrations:reset-after-restore {--force : Ohne Rückfrage ausführen}';

    protected $description = 'Setzt Migration-Einträge zurück, deren Schema-Änderungen fehlen (nach Live-DB-Restore), und ruft migrate --force auf.';

    /**
     * Jede Zeile beschreibt eine erwartete Schema-Änderung und die zugehörige
     * Migration. Fehlt die Spalte/Tabelle in der aktuellen DB, wird der
     * Migrations-Eintrag gelöscht, sodass `migrate --force` sie neu ausführt.
     *
     * @var array<int, array{migration:string, kind:'column'|'table', table:string, column?:string}>
     */
    private const TARGETS = [
        [
            'migration' => '2026_06_22_120000_add_translations_to_custom_events_table',
            'kind'      => 'column',
            'table'     => 'custom_events',
            'column'    => 'title_translations',
        ],
    ];

    public function handle(): int
    {
        $toReset = [];

        foreach (self::TARGETS as $target) {
            $exists = match ($target['kind']) {
                'column' => Schema::hasColumn($target['table'], $target['column']),
                'table'  => Schema::hasTable($target['table']),
            };

            if ($exists) {
                $this->line(sprintf(
                    '  ✓ %s – %s.%s bereits vorhanden, überspringe.',
                    $target['migration'],
                    $target['table'],
                    $target['column'] ?? '*',
                ));
                continue;
            }

            $recorded = DB::table('migrations')->where('migration', $target['migration'])->exists();
            if (! $recorded) {
                $this->line(sprintf(
                    '  ⚠ %s – nicht in migrations-Tabelle eingetragen, wird per migrate ohnehin ausgeführt.',
                    $target['migration'],
                ));
                continue;
            }

            $this->warn(sprintf(
                '  → %s wird zurückgesetzt (Spalte/Tabelle %s.%s fehlt).',
                $target['migration'],
                $target['table'],
                $target['column'] ?? '*',
            ));
            $toReset[] = $target['migration'];
        }

        if (empty($toReset)) {
            $this->info('Nichts zurückzusetzen. Führe zur Sicherheit trotzdem migrate --force aus.');
        } else {
            $this->newLine();
            if (! $this->option('force') && ! $this->confirm('Migration-Einträge löschen und migrate --force ausführen?', false)) {
                $this->info('Abgebrochen.');
                return self::SUCCESS;
            }

            $deleted = DB::table('migrations')->whereIn('migration', $toReset)->delete();
            $this->info("Gelöscht: {$deleted} Einträge.");
        }

        $this->newLine();
        $this->info('Starte migrate --force ...');
        $exitCode = Artisan::call('migrate', ['--force' => true], $this->getOutput());

        return $exitCode === 0 ? self::SUCCESS : self::FAILURE;
    }
}
