<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerFeatureOverride;
use App\Models\CustomerFeaturePreauthorization;
use App\Services\CustomerFeaturePreauthorizationService;
use Illuminate\Console\Command;

/**
 * Merkt Feature-Freischaltungen fuer pds_account_ids vor, auch wenn zu dem
 * Account noch kein Kunde existiert.
 *
 * Beispiele:
 *   php artisan customers:preauthorize-feature --ids=25893,25427 --dry-run
 *   php artisan customers:preauthorize-feature --file=accounts.txt
 *   php artisan customers:preauthorize-feature navigation_cruise_enabled --ids=6433
 *   php artisan customers:preauthorize-feature --apply-pending
 *
 * Bestehende Freischaltungen werden nie ueberschrieben: Steht im Override
 * bereits ein Wert (Aktiviert oder Deaktiviert), bleibt er unangetastet.
 */
class PreauthorizeCustomerFeature extends Command
{
    protected $signature = 'customers:preauthorize-feature
                            {feature=navigation_risk_overview_enabled : Feature-Key aus customer_feature_overrides}
                            {--ids= : Kommagetrennte oder mit Zeilenumbruch getrennte pds_account_id-Liste}
                            {--file= : Datei mit einer pds_account_id pro Zeile}
                            {--disable : Feature sperren statt freischalten}
                            {--note= : Notiz zur Herkunft der Liste}
                            {--apply-pending : Keine neuen IDs, nur offene Vormerkungen auf bestehende Kunden anwenden}
                            {--dry-run : Nur auswerten und anzeigen, nichts schreiben}';

    protected $description = 'Merkt eine Feature-Freischaltung fuer pds_account_ids vor (auch ohne bestehenden Kunden)';

    public function handle(CustomerFeaturePreauthorizationService $service): int
    {
        $feature = (string) $this->argument('feature');

        if (! in_array($feature, CustomerFeatureOverride::getFeatureKeys(), true)) {
            $this->error("Unbekanntes Feature [{$feature}].");
            $this->line('Erlaubt: '.implode(', ', CustomerFeatureOverride::getFeatureKeys()));

            return self::FAILURE;
        }

        $label = CustomerFeatureOverride::getFeatureLabels()[$feature] ?? $feature;
        $enabled = ! $this->option('disable');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->comment('Testlauf - es wird nichts geschrieben.');
        }

        if ($this->option('apply-pending')) {
            return $this->applyPending($service, $feature, $label, $dryRun);
        }

        $ids = $this->collectIds();

        if ($ids === []) {
            $this->error('Keine gueltigen IDs. Nutze --ids=... oder --file=... (oder --apply-pending).');

            return self::FAILURE;
        }

        $existing = Customer::query()->whereIn('pds_account_id', $ids)->count();
        $alreadyRecorded = CustomerFeaturePreauthorization::query()
            ->forFeature($feature)
            ->whereIn('pds_account_id', $ids)
            ->count();

        $this->newLine();
        $this->line("Feature: <info>{$label}</info> ({$feature}) -> ".($enabled ? 'freischalten' : 'sperren'));
        $this->table(['', 'Anzahl'], [
            ['IDs in der Liste', count($ids)],
            ['davon bereits vorgemerkt', $alreadyRecorded],
            ['davon mit bestehendem Kundenkonto', $existing],
            ['davon ohne Konto (greift beim ersten Login)', count($ids) - $existing],
        ]);

        if ($dryRun) {
            $this->newLine();
            $this->comment('Zum Anwenden ohne --dry-run erneut ausfuehren.');

            return self::SUCCESS;
        }

        $recorded = $service->record($feature, $ids, $enabled, $this->option('note'));
        $result = $service->applyToExistingCustomers($feature);

        $this->newLine();
        $this->info("{$recorded} Vormerkungen gespeichert.");
        $this->info("{$result['customers']} bestehende Kunden sofort angepasst.");
        $this->line('Alle uebrigen greifen automatisch beim ersten Login.');

        return self::SUCCESS;
    }

    private function applyPending(
        CustomerFeaturePreauthorizationService $service,
        string $feature,
        string $label,
        bool $dryRun
    ): int {
        $open = CustomerFeaturePreauthorization::query()->forFeature($feature)->pending()->count();

        $this->line("Feature: <info>{$label}</info> ({$feature})");
        $this->line("Noch nicht eingeloeste Vormerkungen: {$open}");

        if ($dryRun) {
            $this->comment('Testlauf - es wird nichts geschrieben.');

            return self::SUCCESS;
        }

        $result = $service->applyToExistingCustomers($feature);

        $this->newLine();
        $this->info("{$result['customers']} Kunden angepasst ({$result['applied']} Freischaltungen).");

        return self::SUCCESS;
    }

    /**
     * @return array<int, int>
     */
    private function collectIds(): array
    {
        $raw = (string) $this->option('ids');

        if ($file = $this->option('file')) {
            if (! is_readable($file)) {
                $this->error("Datei nicht lesbar: {$file}");

                return [];
            }

            $raw .= "\n".file_get_contents($file);
        }

        return collect(preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->filter(fn (string $value) => ctype_digit($value))
            ->map(fn (string $value) => (int) $value)
            ->filter(fn (int $value) => $value > 0)
            ->unique()
            ->values()
            ->all();
    }
}
