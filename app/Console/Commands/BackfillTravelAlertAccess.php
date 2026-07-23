<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerFeatureOverride;
use App\Models\TravelAlertOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Schaltet Travel Alert fuer alle Kunden explizit frei, zu deren E-Mail eine
 * Bestellung vorliegt.
 *
 * Hintergrund: solange NAVIGATION_RISK_OVERVIEW_ENABLED auf true steht, ist
 * Travel Alert fuer jeden eingeloggten Kunden offen – auch ohne Bestellung,
 * denn ohne Override entscheidet die .env. Soll die Freischaltung wirklich
 * pro Kunde greifen, muss die Variable auf false. Dann verlieren aber alle
 * Kunden ohne explizites Override sofort den Zugang.
 *
 * Reihenfolge deshalb:
 *   1. php artisan travel-alert:backfill-access --dry-run   (Zahlen ansehen)
 *   2. php artisan travel-alert:backfill-access             (Overrides setzen)
 *   3. NAVIGATION_RISK_OVERVIEW_ENABLED=false in der .env + config:clear
 *
 * Eine im Admin getroffene Entscheidung (Aktiviert oder Deaktiviert) wird nie
 * ueberschrieben – nur Kunden ohne gesetzten Wert werden angefasst.
 */
class BackfillTravelAlertAccess extends Command
{
    protected $signature = 'travel-alert:backfill-access
                            {--dry-run : Nur auswerten und anzeigen, nichts schreiben}';

    protected $description = 'Schaltet Travel Alert fuer alle Kunden mit Bestellung explizit frei';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->comment('Testlauf – es wird nichts geschrieben.');
        }

        $emails = TravelAlertOrder::query()
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn ($email) => mb_strtolower(trim($email)))
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            $this->warn('Keine Bestellungen gefunden – nichts zu tun.');

            return self::SUCCESS;
        }

        $customers = Customer::query()
            ->whereIn(DB::raw('LOWER(email)'), $emails->all())
            ->get();

        $enabled = 0;
        $keptExplicit = 0;

        foreach ($customers as $customer) {
            $override = CustomerFeatureOverride::firstOrNew(['customer_id' => $customer->id]);

            // Bereits explizit gesetzt (true oder false) -> Admin-Entscheidung
            // hat Vorrang, wir fassen den Datensatz nicht an.
            if ($override->exists && $override->navigation_risk_overview_enabled !== null) {
                $keptExplicit++;

                continue;
            }

            if (! $dryRun) {
                $override->navigation_risk_overview_enabled = true;
                $override->save();
            }

            $enabled++;
        }

        $withoutAccount = $emails->count() - $customers->count();

        $this->newLine();
        $this->table(['', 'Anzahl'], [
            ['Bestellungen (eindeutige E-Mails)', $emails->count()],
            ['davon mit Kundenkonto', $customers->count()],
            ['davon ohne Kundenkonto', max($withoutAccount, 0)],
            [$dryRun ? 'Wuerden freigeschaltet' : 'Freigeschaltet', $enabled],
            ['Uebersprungen (bereits explizit gesetzt)', $keptExplicit],
        ]);

        if ($dryRun) {
            $this->newLine();
            $this->comment('Zum Anwenden ohne --dry-run erneut ausfuehren.');
        }

        return self::SUCCESS;
    }
}
