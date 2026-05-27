<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\App;

/**
 * Builds the travel-requirements search request, calls pds-api
 * `content/all/{mode}` with the customer's Bearer token, and normalizes the
 * flat pds-api response into a grouped contract for the frontend.
 *
 * Baut den Such-Request, ruft die pds-api auf und normalisiert die flache
 * Antwort in eine nach Reisezielen gruppierte Struktur für das Frontend.
 *
 * Mirrors the request shape used by pds-homepage.
 */
class TrsV2SearchService
{
    /**
     * Section keys in display order. `overview` is rendered first, the rest
     * follow the editorial order. Each key maps to a section object on a
     * pds-api record (see {@see normalizeSections()}).
     *
     * @var array<int, string>
     */
    protected const SECTION_ORDER = [
        'overview',
        'entry',
        'visa',
        'transit_visa',
        'health',
        'final_provisions',
        'return_requirements',
        'tuic',
    ];

    public function __construct(
        protected PdsApiService $pdsApi,
        protected TrsV2DataService $data,
    ) {
    }

    /**
     * Run a search for the given tab and return the normalized grouped contract.
     *
     * @param  string  $tab  One of 'ptd', 'business', 'cruise'.
     * @param  array<string, mixed>  $input  The raw request payload from the controller.
     * @return array{ok:bool, error:string|null, request_id:string|int|null, groups:array<int, array<string, mixed>>}
     */
    public function search(Customer $customer, string $tab, array $input): array
    {
        // Cruise needs a resolved cruise-compass id (cruise or route level).
        if ($tab === 'cruise'
            && empty($input['cruise_compass_cruise_id'])
            && empty($input['cruise_compass_route_id'])) {
            return $this->fail('cruise_not_available');
        }

        $isBusiness = $tab === 'business';

        $body = $this->buildBody($tab, $input);
        $language = $this->language($input);
        $qs = $isBusiness ? '?type=business&lang='.rawurlencode($language) : '';

        $resp = $this->pdsApi->post($customer, 'content/all/html'.$qs, $body);

        if ($resp === null || ! $resp->successful()) {
            return $this->fail('request_failed');
        }

        $payload = $resp->json() ?? [];
        $records = $payload['records'] ?? [];

        return [
            'ok' => true,
            'error' => null,
            'request_id' => $payload['requestId'] ?? null,
            'groups' => $this->normalizeGroups(is_array($records) ? $records : []),
        ];
    }

    /**
     * Query params for the GET pdf proxy, mirroring the POST body builder so a
     * controller can call `$pdsApi->get($customer, 'content/all/pdf', $this->pdfQuery($input))`.
     *
     * The tab is inferred from the payload (presence of `travellers` => business).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function pdfQuery(array $input): array
    {
        $tab = ! empty($input['travellers']) ? 'business' : 'ptd';

        return $this->buildBody($tab, $input);
    }

    /**
     * Build the pds-api request body shared by the POST search and GET pdf calls.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function buildBody(string $tab, array $input): array
    {
        $isBusiness = $tab === 'business';
        $language = $this->language($input);

        // Cruise: the API resolves destinations from the cruise-compass id,
        // so we send no destinations array – just the cruise id(s).
        if ($tab === 'cruise') {
            $body = [
                'lang' => $language,
                'nat' => implode(',', $this->codes($input['nationalities'] ?? [])),
                'include_country_info' => (bool) ($input['showCountryInfo'] ?? false),
            ];

            if (! empty($input['cruise_compass_cruise_id'])) {
                $body['cruise_compass_cruise_id'] = (string) $input['cruise_compass_cruise_id'];
            } elseif (! empty($input['cruise_compass_route_id'])) {
                $body['cruise_compass_route_id'] = $input['cruise_compass_route_id'];
            }

            return $body;
        }

        $body = [
            'destinations' => $this->buildDestinations($input),
            'lang' => $language,
        ];

        if ($isBusiness) {
            $travellers = $this->buildTravellers($input);

            // Business nationalities are derived from the traveller list.
            $nat = collect($travellers)
                ->pluck('nationality')
                ->filter()
                ->unique()
                ->implode(',');

            $body['nat'] = $nat;
            $body['include_country_info'] = false;
            $body['type'] = 'business';
            $body['trip'] = [
                'start_date' => (string) ($input['tripStart'] ?? ''),
                'end_date' => (string) ($input['tripEnd'] ?? ''),
            ];
            $body['travellers'] = $travellers;

            return $body;
        }

        // PTD (general entry requirements) tab.
        $body['nat'] = implode(',', $this->codes($input['nationalities'] ?? []));
        $body['include_country_info'] = (bool) ($input['showCountryInfo'] ?? false);
        $body['travel'] = [
            'modes' => $this->selectedModes($input['modes'] ?? []),
            'with_minors' => (bool) ($input['withMinors'] ?? false),
        ];

        // Return travel requirements: best-effort state derived from returnCountry.
        $returnCountry = $input['returnCountry'] ?? null;
        if (! empty($input['showReturn']) && ! empty($returnCountry)) {
            $body['state'] = [(string) $returnCountry];
        }

        $tourOperators = $this->codes($input['tourOperators'] ?? []);
        if ($tourOperators !== []) {
            $body['tour_operators'] = $tourOperators;
        }

        return $body;
    }

    /**
     * Build the destinations array of objects: normal destinations plus transit
     * destinations flagged with `type=transit`.
     *
     * @param  array<string, mixed>  $input
     * @return array<int, array{destination:string, type?:string}>
     */
    protected function buildDestinations(array $input): array
    {
        $destinations = [];

        foreach ($this->codes($input['destinations'] ?? []) as $code) {
            $destinations[] = ['destination' => $code];
        }

        foreach ($this->codes($input['transit'] ?? []) as $code) {
            $destinations[] = ['destination' => $code, 'type' => 'transit'];
        }

        return $destinations;
    }

    /**
     * Build the business traveller list, omitting empty fields per entry.
     *
     * @param  array<string, mixed>  $input
     * @return array<int, array<string, string>>
     */
    protected function buildTravellers(array $input): array
    {
        $travellers = [];

        foreach ((array) ($input['travellers'] ?? []) as $traveller) {
            if (! is_array($traveller)) {
                continue;
            }

            $entry = array_filter([
                'nationality' => (string) ($traveller['nationality'] ?? ''),
                'residence_country' => (string) ($traveller['residence'] ?? ''),
                'secondary_nationality' => (string) ($traveller['secondary'] ?? ''),
                'purpose' => (string) ($traveller['purpose'] ?? ''),
            ], fn ($value) => $value !== '');

            if ($entry !== []) {
                $travellers[] = $entry;
            }
        }

        return $travellers;
    }

    /**
     * Filter the modes map down to the selected modes, in canonical order.
     *
     * @param  array<string, mixed>  $modes
     * @return array<int, string>
     */
    protected function selectedModes(array $modes): array
    {
        $selected = [];

        foreach (['air', 'land', 'sea'] as $mode) {
            if (! empty($modes[$mode])) {
                $selected[] = $mode;
            }
        }

        return $selected;
    }

    /**
     * Group flat records by destination code, preserving first-seen order.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeGroups(array $records): array
    {
        $groups = [];

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $destination = (string) ($record['destination'] ?? '');
            if ($destination === '') {
                continue;
            }

            if (! isset($groups[$destination])) {
                $groups[$destination] = [
                    'destination' => $destination,
                    'destination_type' => (string) ($record['destination_type'] ?? 'travel'),
                    'title' => $this->destinationTitle($destination, $record),
                    'flag' => $record['destination_flag'] ?? null,
                    'country_information' => null, // Country info handled later.
                    'records' => [],
                ];
            }

            $groups[$destination]['records'][] = $this->normalizeRecord($record);
        }

        return array_values($groups);
    }

    /**
     * Normalize a single flat record into the per-nationality result object.
     *
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    protected function normalizeRecord(array $record): array
    {
        $nationality = (string) ($record['nationality'] ?? '');
        $traveller = (array) ($record['traveller'] ?? []);
        $isBusiness = ($traveller['type'] ?? null) === 'business';

        $entry = is_array($record['entry'] ?? null) ? $record['entry'] : [];
        $entryStopped = (bool) ($entry['entry_stopped_temporarily'] ?? false);

        return [
            'nationality' => $nationality,
            'nationality_title' => $this->nationalityTitle($nationality),
            'nationality_flag' => $record['nationality_flag'] ?? null,
            'is_business' => $isBusiness,
            'entry_stopped_temporarily' => $entryStopped,
            'entry_stopped_content' => $entryStopped ? ($entry['content'] ?? null) : null,
            // When entry is temporarily stopped, suppress all other sections.
            'sections' => $entryStopped ? [] : $this->normalizeSections($record, $isBusiness),
        ];
    }

    /**
     * Collect the present, non-empty section objects in display order and map
     * each to {key, title, content, updated_at}.
     *
     * @param  array<string, mixed>  $record
     * @return array<int, array{key:string, title:string, content:string, updated_at:string|null}>
     */
    protected function normalizeSections(array $record, bool $isBusiness): array
    {
        $sections = [];

        foreach (self::SECTION_ORDER as $key) {
            $section = $record[$key] ?? null;
            if (! is_array($section)) {
                continue;
            }

            $content = $section['content'] ?? null;
            if (! is_string($content) || trim($content) === '') {
                continue;
            }

            $sections[] = [
                'key' => $key,
                'title' => $this->sectionTitle($key, $isBusiness),
                'content' => $content,
                'updated_at' => $section['updated_at'] ?? null,
            ];
        }

        return $sections;
    }

    /**
     * Localized section title via Laravel translations.
     */
    protected function sectionTitle(string $key, bool $isBusiness): string
    {
        return match ($key) {
            'overview' => __('trs.Overview'),
            'entry' => __('trs.EntryRequirements'),
            'visa' => $isBusiness
                ? __('trs.VisaRequirements').' (Business)'
                : __('trs.VisaRequirements'),
            'transit_visa' => __('trs.TransitVisa'),
            'health' => __('trs.Health'),
            'final_provisions' => __('trs.FinalProvisions'),
            'return_requirements' => __('trs.ReturnTravelRequirements'),
            'tuic' => 'TUI Cruises',
            default => $key,
        };
    }

    /**
     * Localized destination country name, falling back to the record title or code.
     *
     * @param  array<string, mixed>  $record
     */
    protected function destinationTitle(string $code, array $record): string
    {
        $localized = $this->localizedName($this->data->countries(), $code);

        if ($localized !== null) {
            return $localized;
        }

        $title = $record['title'] ?? null;

        return is_string($title) && $title !== '' ? $title : $code;
    }

    /**
     * Localized nationality name, falling back to the code.
     */
    protected function nationalityTitle(string $code): string
    {
        return $this->localizedName($this->data->nationalities(), $code) ?? $code;
    }

    /**
     * Look up a code in a lookup list and return the name for the current locale.
     *
     * @param  array<int, array{code:string, name_de:string, name_en:string, name_nl:string}>  $rows
     */
    protected function localizedName(array $rows, string $code): ?string
    {
        if ($code === '') {
            return null;
        }

        $nameKey = $this->localeNameKey();

        foreach ($rows as $row) {
            if (($row['code'] ?? null) === $code) {
                $name = $row[$nameKey] ?? $row['name_en'] ?? $row['code'] ?? null;

                return ($name !== null && $name !== '') ? $name : null;
            }
        }

        return null;
    }

    /**
     * Map the current app locale ({de,en,nl}) to a lookup-row name key.
     */
    protected function localeNameKey(): string
    {
        $locale = App::getLocale();

        return match ($locale) {
            'en' => 'name_en',
            'nl' => 'name_nl',
            default => 'name_de',
        };
    }

    /**
     * Resolve the output language from the payload, defaulting to the app locale.
     *
     * @param  array<string, mixed>  $input
     */
    protected function language(array $input): string
    {
        $language = $input['language'] ?? null;

        return is_string($language) && $language !== '' ? $language : App::getLocale();
    }

    /**
     * Normalize a list of codes to a clean array of non-empty strings.
     *
     * @param  mixed  $codes
     * @return array<int, string>
     */
    protected function codes(mixed $codes): array
    {
        return collect((array) $codes)
            ->map(fn ($code) => (string) $code)
            ->filter(fn (string $code) => $code !== '')
            ->values()
            ->all();
    }

    /**
     * Build a failed-search response with the shared empty contract.
     *
     * @return array{ok:bool, error:string, request_id:null, groups:array<int, mixed>}
     */
    protected function fail(string $error): array
    {
        return [
            'ok' => false,
            'error' => $error,
            'request_id' => null,
            'groups' => [],
        ];
    }
}
