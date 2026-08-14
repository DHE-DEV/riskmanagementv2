<?php

namespace App\Filament\Pages;

use App\Models\AirportCode;
use App\Models\Country;
use App\Services\CountryLocator;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Admin-Werkzeug: zu Geokoordinaten (oder einem 3-Letter-Code) das Land bestimmen.
 *
 * Nutzt dieselbe Aufloesung wie die Suche nach landesweiten Ereignissen
 * (CountryLocator) und zeigt zusaetzlich, ueber welche Stufe der Kaskade das
 * Ergebnis zustande kam - damit laesst sich nachvollziehen, ob ein Punkt im
 * Polygon lag, nur in dessen Naehe, oder ueber eine Naeherung zugeordnet wurde.
 */
class CountryLookup extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Land zu Koordinaten';

    protected static ?string $title = 'Land zu Koordinaten';

    protected static ?int $navigationSort = 90;

    /** Eingabe: Koordinatenpaar, Google-Maps-Link oder 3-Letter-Code. */
    public string $query = '';

    /** @var ?array<string, mixed> */
    public ?array $result = null;

    public ?string $error = null;

    public static function getNavigationGroup(): ?string
    {
        return 'Geografische Daten';
    }

    public function getView(): string
    {
        return 'filament.pages.country-lookup';
    }

    public function getSubheading(): ?string
    {
        return 'Koordinaten, Google-Maps-Link oder 3-Letter-Code eingeben. '
            . 'Verwendet dieselbe Zuordnung wie die Ereignis-Suche.';
    }

    public function lookup(): void
    {
        $this->result = null;
        $this->error = null;

        $input = trim($this->query);

        if ($input === '') {
            $this->error = 'Bitte Koordinaten oder einen 3-Letter-Code eingeben.';

            return;
        }

        $locator = app(CountryLocator::class);

        // 3-Letter-Code (IATA): ueber airport_codes_1.iso_country exakt aufloesbar.
        if (preg_match('/^[A-Za-z]{3}$/', $input)) {
            $this->lookupAirportCode(strtoupper($input), $locator);

            return;
        }

        $coordinates = self::parseCoordinates($input);

        if ($coordinates === null) {
            $this->error = 'Eingabe nicht erkannt. Erwartet werden z.B. "52.5200, 13.4050", '
                . 'ein Google-Maps-Link oder ein 3-Letter-Code wie "FRA".';

            return;
        }

        [$latitude, $longitude] = $coordinates;

        $description = $locator->describeCoordinates($latitude, $longitude);

        $this->result = $this->buildResult($description, $latitude, $longitude) + [
            'input_type' => 'coordinates',
        ];
    }

    private function lookupAirportCode(string $code, CountryLocator $locator): void
    {
        $airport = AirportCode::query()
            ->where('iata_code', $code)
            ->first(['name', 'municipality', 'iso_country', 'latitude_deg', 'longitude_deg']);

        if (! $airport) {
            $this->error = "Kein Flughafen mit dem 3-Letter-Code \"{$code}\" gefunden.";

            return;
        }

        $countryId = $locator->countryIdForAirportCode($code);

        $this->result = [
            'input_type' => 'airport_code',
            'code' => $code,
            'airport_name' => $airport->name,
            'airport_municipality' => $airport->municipality,
            'latitude' => (float) $airport->latitude_deg,
            'longitude' => (float) $airport->longitude_deg,
            'country' => $countryId ? Country::with('continent')->find($countryId) : null,
            'method' => 'airport_code',
            'distance_km' => null,
            'rejected_country' => null,
            'has_boundary' => $countryId
                ? Country::whereKey($countryId)->whereHas('boundary')->exists()
                : false,
        ];
    }

    /**
     * @param  array{country_id: ?int, method: ?string, distance_km: ?float, rejected_country_id: ?int}  $description
     * @return array<string, mixed>
     */
    private function buildResult(array $description, float $latitude, float $longitude): array
    {
        $country = $description['country_id']
            ? Country::with('continent')->find($description['country_id'])
            : null;

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'country' => $country,
            'method' => $description['method'],
            'distance_km' => $description['distance_km'],
            'rejected_country' => $description['rejected_country_id']
                ? Country::find($description['rejected_country_id'])
                : null,
            'has_boundary' => $country
                ? Country::whereKey($country->id)->whereHas('boundary')->exists()
                : false,
        ];
    }

    /**
     * Koordinaten aus freier Eingabe lesen: "52.52, 13.405", "52.52 13.405",
     * "52.52;13.405" oder ein Google-Maps-Link mit @lat,lng bzw. ?q=lat,lng.
     *
     * @return ?array{0: float, 1: float}
     */
    public static function parseCoordinates(string $input): ?array
    {
        // Google-Maps-Link: .../@52.5200,13.4050,15z oder ?q=52.52,13.405
        if (preg_match('/[@?&=\/](-?\d{1,3}\.\d+),\s*(-?\d{1,3}\.\d+)/', $input, $m)) {
            return self::validateCoordinates((float) $m[1], (float) $m[2]);
        }

        // Reines Zahlenpaar, getrennt durch Komma, Semikolon oder Leerzeichen.
        if (preg_match('/^\s*(-?\d{1,3}(?:[.,]\d+)?)\s*[;,\s]\s*(-?\d{1,3}(?:[.,]\d+)?)\s*$/', $input, $m)) {
            // Dezimalkomma zulassen, aber nur wenn es nicht als Trennzeichen dient:
            // "52,52 13,40" ist eindeutig, "52,52" allein waere es nicht.
            $lat = (float) str_replace(',', '.', $m[1]);
            $lng = (float) str_replace(',', '.', $m[2]);

            return self::validateCoordinates($lat, $lng);
        }

        return null;
    }

    /**
     * @return ?array{0: float, 1: float}
     */
    private static function validateCoordinates(float $latitude, float $longitude): ?array
    {
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return null;
        }

        return [$latitude, $longitude];
    }

    /**
     * Klartext zu den Stufen der Aufloesungs-Kaskade.
     *
     * @return array{label: string, color: string, explanation: string}
     */
    public static function methodInfo(?string $method): array
    {
        return match ($method) {
            'boundary' => [
                'label' => 'Exakt im Landespolygon',
                'color' => 'success',
                'explanation' => 'Der Punkt liegt innerhalb der hinterlegten Landesgrenze.',
            ],
            'boundary_nearby' => [
                'label' => 'Knapp außerhalb der Grenze',
                'color' => 'info',
                'explanation' => 'Der Punkt liegt nicht im Polygon, aber innerhalb der Toleranz von 25 km. '
                    . 'Typisch für Küstenorte, Häfen und küstennahe Schiffspositionen – die Grenzdaten haben '
                    . 'im Maßstab 1:10 Mio. keine metergenaue Küstenlinie.',
            ],
            'airport_code' => [
                'label' => 'Über den 3-Letter-Code',
                'color' => 'success',
                'explanation' => 'Das Land stammt direkt aus den Flughafen-Stammdaten (iso_country) und ist damit exakt.',
            ],
            'airport' => [
                'label' => 'Genähert über den nächsten Flughafen',
                'color' => 'warning',
                'explanation' => 'Für dieses Land liegt keine Grenze vor. Zugeordnet wurde über den '
                    . 'nächstgelegenen Flughafen – an Landesgrenzen kann das danebenliegen.',
            ],
            'city' => [
                'label' => 'Genähert über die nächste Stadt',
                'color' => 'warning',
                'explanation' => 'Für dieses Land liegt keine Grenze vor und kein Flughafen in Reichweite. '
                    . 'Zugeordnet wurde über die nächstgelegene Stadt.',
            ],
            'centroid' => [
                'label' => 'Genähert über den Landes-Mittelpunkt',
                'color' => 'danger',
                'explanation' => 'Gröbste Stufe: weder Grenze noch Flughafen oder Stadt in Reichweite. '
                    . 'Das Ergebnis ist wenig belastbar.',
            ],
            'outside_all_boundaries' => [
                'label' => 'Kein Land',
                'color' => 'gray',
                'explanation' => 'Der Punkt liegt in keinem Landespolygon und auch nicht in dessen Nähe – '
                    . 'etwa auf offener See. Eine Näherung wird bewusst verworfen, sobald für das '
                    . 'nächstgelegene Land eine Grenze vorliegt.',
            ],
            default => [
                'label' => 'Nicht bestimmbar',
                'color' => 'gray',
                'explanation' => 'Zu diesem Punkt ließ sich kein Land ermitteln.',
            ],
        };
    }
}
