<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Exception\MissingResourceException;

/**
 * Alle aktuellen Waehrungen nach ISO 4217 aus den ICU-Daten (symfony/intl):
 * Code, Nummer, Name auf Deutsch, Englisch und Niederlaendisch, Symbol und
 * Nachkommastellen. Abgeloeste Waehrungen sind inaktiv. Vorhandene
 * Eintraege werden aktualisiert, ihr Aktiv-Schalter bleibt unangetastet.
 */
class CurrencySeeder extends Seeder
{
    /** Keine echten Zahlungsmittel: Testcode, "keine Waehrung", Edelmetalle, Rechnungseinheiten */
    private const EXCLUDED = ['XTS', 'XXX', 'XAU', 'XAG', 'XPT', 'XPD', 'XBA', 'XBB', 'XBC', 'XBD', 'XDR', 'XSU', 'XUA'];

    /**
     * Abgeloeste Waehrungen (ISO 4217, Liste 3): sie bleiben in der Tabelle,
     * werden aber nicht angeboten. Wer sie braucht, schaltet sie aktiv.
     */
    private const HISTORICAL = [
        'ADP', 'AFA', 'ALK', 'AOK', 'AON', 'AOR', 'ARA', 'ARP', 'ARY', 'ATS', 'AYM', 'AZM', 'BAD', 'BEC', 'BEF', 'BEL',
        'BGJ', 'BGK', 'BGL', 'BOP', 'BRB', 'BRC', 'BRE', 'BRN', 'BRR', 'BUK', 'BYB', 'BYR', 'CHC', 'CSD', 'CSJ', 'CSK',
        'CUC', 'CYP', 'DDM', 'DEM', 'ECS', 'ECV', 'EEK', 'ESA', 'ESB', 'ESP', 'FIM', 'FRF', 'GEK', 'GHC', 'GHP', 'GNE',
        'GNS', 'GQE', 'GRD', 'GWE', 'GWP', 'HRD', 'HRK', 'IEP', 'ILP', 'ILR', 'ISJ', 'ITL', 'LAJ', 'LSM', 'LTL', 'LTT',
        'LUC', 'LUF', 'LUL', 'LVL', 'LVR', 'MGF', 'MLF', 'MRO', 'MTL', 'MTP', 'MVQ', 'MXP', 'MZE', 'MZM', 'NIC', 'NLG',
        'PEH', 'PEI', 'PES', 'PLZ', 'PTE', 'RHD', 'ROK', 'ROL', 'RUR', 'SDD', 'SDP', 'SIT', 'SKK', 'SLL', 'SRG', 'STD',
        'SUR', 'TJR', 'TMM', 'TPE', 'TRL', 'UAK', 'UGS', 'UGW', 'USS', 'UYN', 'UYP', 'VEB', 'VEF', 'VNC', 'XEU', 'XFO',
        'XFU', 'YDD', 'YUD', 'YUM', 'YUN', 'ZAL', 'ZMK', 'ZRN', 'ZRZ', 'ZWC', 'ZWD', 'ZWN', 'ZWR',
    ];

    public function run(): void
    {
        $count = 0;

        foreach (Currencies::getCurrencyCodes() as $code) {
            try {
                $numeric = Currencies::getNumericCode($code);
            } catch (MissingResourceException) {
                // Ohne Nummer sind es historische oder inoffizielle Codes.
                continue;
            }

            if ($numeric <= 0 || in_array($code, self::EXCLUDED, true)) {
                continue;
            }

            $names = [];
            foreach (['de', 'en', 'nl'] as $locale) {
                $names[$locale] = Currencies::getName($code, $locale);
            }

            $symbol = Currencies::getSymbol($code, 'de');

            $currency = Currency::query()->firstOrNew(['code' => $code]);
            $currency->fill([
                'numeric_code' => $numeric,
                'name_translations' => $names,
                'symbol' => $symbol !== $code ? $symbol : null,
                'minor_unit' => Currencies::getFractionDigits($code),
            ]);
            // Der Aktiv-Schalter wird nur beim Anlegen gesetzt.
            if (! $currency->exists) {
                $currency->is_active = ! in_array($code, self::HISTORICAL, true);
            }
            $currency->save();
            $count++;
        }

        $this->command?->info($count.' Währungen eingetragen.');
    }
}
