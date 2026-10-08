<?php

use App\Models\Country;
use App\Models\TaxiApp;
use Illuminate\Database\Migrations\Migration;

/**
 * Laender, in denen Uber, Bolt und FREENOW verfuegbar sind (Stand Oktober
 * 2026, nach den Staedte-Seiten der Anbieter bzw. der Lyft-Uebernahme von
 * FREENOW). Es werden nur Zuordnungen ergaenzt, keine entfernt; fehlt die
 * App (TaxiAppSeeder nicht gelaufen), passiert nichts.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Stammdaten-Import – in Tests bleibt die Tabelle leer.
        if (app()->runningUnitTests()) {
            return;
        }

        $byIso = Country::query()->withTrashed()->get()->keyBy(fn (Country $country) => strtoupper((string) $country->iso_code));

        foreach (self::COUNTRIES as $name => $isos) {
            $app = TaxiApp::query()->where('name', $name)->first();

            if (! $app) {
                continue;
            }

            $ids = collect($isos)->map(fn (string $iso) => $byIso->get($iso)?->id)->filter()->all();
            $app->countries()->syncWithoutDetaching($ids);
        }
    }

    public function down(): void
    {
        // Zuordnungen lassen sich von Hand pflegen – ein Zuruecknehmen wuerde auch das treffen.
    }

    /** @var array<string, array<int, string>> Name der App => ISO-Codes */
    private const COUNTRIES = [
        'Uber' => ['AD', 'AE', 'AR', 'AT', 'AU', 'BB', 'BD', 'BE', 'BH', 'BO', 'BR', 'CA', 'CH', 'CL', 'CO', 'CR', 'CZ', 'DE', 'DK', 'DO', 'EC', 'EE', 'EG', 'ES', 'FI', 'FR', 'GB', 'GH', 'GR', 'GT', 'HK', 'HN', 'HR', 'HU', 'IE', 'IN', 'IT', 'JM', 'JO', 'JP', 'KE', 'KR', 'KW', 'LB', 'LC', 'LK', 'LT', 'LU', 'MT', 'MX', 'NG', 'NL', 'NO', 'NP', 'NZ', 'PA', 'PE', 'PL', 'PT', 'PY', 'QA', 'RO', 'SA', 'SE', 'SI', 'SK', 'SV', 'TR', 'TW', 'UA', 'UG', 'US', 'UY', 'ZA'],
        'Bolt' => ['AE', 'AT', 'AZ', 'BE', 'BG', 'CH', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GB', 'GE', 'GH', 'GR', 'HR', 'HU', 'IE', 'IT', 'KE', 'KZ', 'LT', 'LV', 'MA', 'MD', 'MT', 'MX', 'MY', 'NG', 'NL', 'NO', 'NZ', 'PL', 'PT', 'PY', 'RO', 'SA', 'SE', 'SI', 'SK', 'TH', 'TW', 'TZ', 'UA', 'UG', 'ZA', 'ZW'],
        'FREENOW' => ['AT', 'DE', 'ES', 'FR', 'GB', 'GR', 'IE', 'IT', 'PL'],
    ];
};
