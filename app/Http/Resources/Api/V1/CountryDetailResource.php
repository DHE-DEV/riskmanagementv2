<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Country;
use App\Models\CountryHoliday;
use App\Models\CountryImage;
use App\Models\MobileOperator;
use App\Models\TaxiApp;
use App\Support\AdminV2\CountryRiskProfile;
use App\Support\AdminV2\CountryTravelInfo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Alle Angaben eines Landes fuer Apps und Partner: Grunddaten,
 * Laenderbeschreibung, Reiseinformationen, Strom, Trinkgeld, Taxi-Apps,
 * Mobilfunkanbieter, Feiertage, Bilder und Risikoprofil.
 *
 * Mehrsprachige Texte kommen als Objekt je Sprache ({"de": …, "en": …});
 * mit ?lang=de wird daraus der Text in dieser Sprache (Rueckfall Deutsch).
 *
 * @mixin Country
 */
class CountryDetailResource extends JsonResource
{
    private ?string $lang = null;

    public function toArray(Request $request): array
    {
        $this->lang = self::lang($request);

        /** @var Country $country */
        $country = $this->resource;
        $info = is_array($country->travel_info) ? $country->travel_info : [];

        return [
            'iso_code' => $country->iso_code,
            'iso3_code' => $country->iso3_code,
            'name' => $this->text($country->name_translations),
            'continent' => $country->continent ? [
                'code' => $country->continent->code,
                'name' => $this->text($country->continent->name_translations),
            ] : null,
            'territory' => [
                'type' => $country->territory_type,
                'type_label' => CountryTravelInfo::TERRITORY_TYPES[$country->territory_type] ?? null,
                'parent_country' => $country->parentCountry ? [
                    'iso_code' => $country->parentCountry->iso_code,
                    'name' => $this->text($country->parentCountry->name_translations),
                ] : null,
            ],
            'membership' => [
                'eu' => (bool) $country->is_eu_member,
                'schengen' => (bool) $country->is_schengen_member,
            ],
            'currency' => $country->currency_code ? [
                'code' => $country->currency_code,
                'name' => $country->currency_name,
                'symbol' => $country->currency_symbol,
            ] : null,
            'phone_prefix' => $country->phone_prefix,
            'timezone' => $country->timezone,
            'languages' => array_values((array) ($country->languages ?? [])),
            'population' => $country->population,
            'area_km2' => $country->area_km2 !== null ? (float) $country->area_km2 : null,
            'coordinates' => $country->lat !== null && $country->lng !== null ? [
                'lat' => (float) $country->lat,
                'lng' => (float) $country->lng,
            ] : null,
            'flag' => [
                'svg_url' => $country->flag_url,
                'emoji' => $country->flag_emoji,
            ],
            'description' => [
                'short' => $this->text($info['short_description'] ?? null),
                'long' => $this->text($info['description'] ?? null),
                'known_for' => $this->textList($info['known_for'] ?? null),
            ],
            'travel_info' => [
                'intro' => $this->text($info['intro'] ?? null),
                'driving_side' => $country->driving_side,
                'driving_side_label' => CountryTravelInfo::DRIVING_SIDES[$country->driving_side] ?? null,
                'emergency' => $this->emergency($info),
                'religions' => $this->religions($info),
                'national_day' => ($info['national_day']['date'] ?? null) ? [
                    'date' => $info['national_day']['date'],
                    'day_month' => substr((string) $info['national_day']['date'], 5),
                    'name' => $this->text($info['national_day']['name'] ?? null),
                ] : null,
            ],
            'power' => [
                'voltage' => $info['voltage'] ?? null,
                'frequency' => $info['frequency'] ?? null,
                'plug_types' => CountryTravelInfo::plugTypesForApi($info),
                'notes' => $this->text($info['power_notes'] ?? null),
            ],
            'tipping' => $this->tipping($info),
            'taxi_apps' => $country->taxiApps->map(fn (TaxiApp $app) => [
                'name' => $app->name,
                'description' => $this->text($app->description_translations),
                'logo_url' => $app->logo_url,
                'website_url' => $app->website_url,
                'app_store_url' => $app->app_store_url,
                'play_store_url' => $app->play_store_url,
            ])->values()->all(),
            'mobile_operators' => $country->mobileOperators->map(fn (MobileOperator $operator) => [
                'name' => $operator->name,
                'description' => $this->text($operator->description_translations),
                'logo_url' => $operator->logo_url,
                'website_url' => $operator->website_url,
                'prepaid_url' => $operator->prepaid_url,
                'offers_esim' => (bool) $operator->offers_esim,
            ])->values()->all(),
            'holidays' => $this->holidays($country, $request),
            'images' => $this->images($country),
            'risk_profile' => $this->riskProfile($country),
            'updated_at' => $country->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Gewuenschte Sprache aus ?lang= – nur bekannte Sprachen, sonst alle.
     */
    public static function lang(Request $request): ?string
    {
        $lang = strtolower(trim((string) $request->query('lang', '')));

        return in_array($lang, CountryTravelInfo::locales(), true) ? $lang : null;
    }

    /**
     * Mehrsprachiger Text: alle Sprachen als Objekt oder – mit ?lang – ein String.
     */
    private function text(mixed $translations): mixed
    {
        $translations = is_array($translations) ? array_filter($translations, fn ($value) => is_string($value) && $value !== '') : [];

        if ($this->lang === null) {
            return $translations ?: (object) [];
        }

        return $translations[$this->lang] ?? $translations['de'] ?? (reset($translations) ?: null);
    }

    /**
     * Mehrsprachige Stichwortliste: je Sprache ein Array, mit ?lang ein Array.
     */
    private function textList(mixed $translations): mixed
    {
        $translations = is_array($translations) ? array_filter($translations, fn ($value) => is_array($value) && $value !== []) : [];
        $translations = array_map(fn (array $tags) => array_values($tags), $translations);

        if ($this->lang === null) {
            return $translations ?: (object) [];
        }

        return $translations[$this->lang] ?? $translations['de'] ?? (reset($translations) ?: []);
    }

    /**
     * @param  array<string, mixed>  $info
     * @return array<string, string|null>
     */
    private function emergency(array $info): array
    {
        $numbers = [];

        foreach (array_keys(CountryTravelInfo::EMERGENCY) as $key) {
            $numbers[$key] = ($info['emergency'][$key] ?? null) ?: null;
        }

        return $numbers;
    }

    /**
     * @param  array<string, mixed>  $info
     * @return list<array<string, mixed>>
     */
    private function religions(array $info): array
    {
        $religions = [];

        foreach ((array) ($info['religions'] ?? []) as $key) {
            if (! isset(CountryTravelInfo::RELIGIONS[$key])) {
                continue;
            }

            [$german, $english] = CountryTravelInfo::RELIGIONS[$key];
            $religions[] = ['key' => $key, 'name' => $this->text(['de' => $german, 'en' => $english])];
        }

        return $religions;
    }

    /**
     * @param  array<string, mixed>  $info
     * @return array<string, array<string, mixed>|null>
     */
    private function tipping(array $info): array
    {
        $tipping = [];

        foreach (array_keys(CountryTravelInfo::TIPPING_CATEGORIES) as $category) {
            $row = $info['tipping'][$category] ?? null;

            if (! is_array($row) || $row === []) {
                $tipping[$category] = null;

                continue;
            }

            $mode = ($row['mode'] ?? 'range') === 'fixed' ? 'fixed' : 'range';

            $tipping[$category] = [
                'mode' => $mode,
                'from' => isset($row['from']) ? (float) $row['from'] : null,
                'to' => $mode === 'range' && isset($row['to']) ? (float) $row['to'] : null,
                'unit' => $row['unit'] ?? null,
                'currency' => $row['currency'] ?? null,
                'description' => $this->text($row['description'] ?? null),
            ];
        }

        return $tipping;
    }

    /**
     * Feiertage eines Jahres (?year=, sonst das laufende Jahr).
     *
     * @return array<string, mixed>
     */
    private function holidays(Country $country, Request $request): array
    {
        $year = (int) $request->query('year', (string) now()->year);

        if ($year < 1970 || $year > 2100) {
            $year = now()->year;
        }

        $items = $country->holidays
            ->filter(fn (CountryHoliday $holiday) => (int) $holiday->date->format('Y') === $year)
            ->map(function (CountryHoliday $holiday) {
                $row = $holiday->toApiArray();
                $row['name'] = $this->text($holiday->name_translations);
                $row['comment'] = $this->text($holiday->comment_translations);
                $row['regions'] = array_map(fn (array $region) => array_merge($region, ['name' => $this->text($region['name'])]), $row['regions']);

                return $row;
            })
            ->values()
            ->all();

        return ['year' => $year, 'items' => $items];
    }

    /**
     * @return array<string, mixed>
     */
    private function images(Country $country): array
    {
        $images = $country->images->map(function (CountryImage $image) {
            $row = $image->toApiArray();
            $row['alt'] = $this->text($image->alt_translations);
            $row['caption'] = $this->text($image->caption_translations);

            return $row;
        });

        return [
            'hero' => $images->first(fn (array $image) => $image['kind'] === CountryImage::KIND_HERO),
            'hero_url' => $country->hero_image_url,
            'gallery' => $images->filter(fn (array $image) => $image['kind'] !== CountryImage::KIND_HERO)->values()->all(),
        ];
    }

    /**
     * Risikoprofil je Bereich: jeder Punkt mit Wert, bei Stufen mit Bezeichnung, dazu die Notiz.
     *
     * @return array<string, mixed>|null
     */
    private function riskProfile(Country $country): ?array
    {
        $profile = is_array($country->risk_profile) ? $country->risk_profile : [];

        if ($profile === []) {
            return null;
        }

        $overall = $country->overall_risk_level;
        $categories = [];

        foreach (CountryRiskProfile::categories() as $category => $definition) {
            $values = is_array($profile[$category] ?? null) ? $profile[$category] : [];
            $fields = [];

            foreach ($definition['fields'] as $field => $meta) {
                $value = $values[$field] ?? null;

                $entry = ['value' => match ($meta['type']) {
                    'level' => $value !== null ? (int) $value : null,
                    'bool' => (bool) $value,
                    'number' => $value !== null ? (int) $value : null,
                    'tags' => array_values((array) $value),
                    default => $value !== null && $value !== '' ? (string) $value : null,
                }];

                if ($meta['type'] === 'level') {
                    $entry['label'] = $entry['value'] ? CountryRiskProfile::LEVELS[$entry['value']] ?? null : null;
                }

                if (CountryRiskProfile::hasNote($meta)) {
                    $entry['note'] = $this->text($values['notes'][$field] ?? null);
                }

                $fields[$field] = $entry;
            }

            $categories[$category] = ['label' => $definition['label'], 'fields' => $fields];
        }

        return [
            'overall' => ['level' => $overall, 'label' => $overall ? Country::getRiskLevelLabel($overall) : null],
            'categories' => $categories,
        ];
    }
}
