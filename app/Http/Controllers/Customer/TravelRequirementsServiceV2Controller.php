<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\TrsV2DataService;
use App\Services\TrsV2SearchService;
use App\Services\TrsV2SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;

/**
 * Travel Requirements Service v2 – native rebuild of the pds-homepage search.
 *
 * Renders the homepage + search form (3 tabs). Lookup data comes from the
 * public pds-api endpoints; search/PDF (later milestones) use the customer's
 * Bearer token via PdsApiService.
 */
class TravelRequirementsServiceV2Controller extends Controller
{
    /** Locales the language switcher offers, mirroring pds-homepage. */
    public const LOCALES = ['de', 'en', 'nl'];

    public function __construct(
        protected TrsV2DataService $data,
        protected TrsV2SearchService $searchService,
        protected TrsV2SubscriptionService $subscriptionService,
    ) {
    }

    public function index(Request $request)
    {
        $locale = $this->resolveLocale($request);
        App::setLocale($locale);

        $nameKey = 'name_'.$locale;

        $countries = $this->localize($this->data->countries(), $nameKey);
        $nationalities = $this->localize($this->data->nationalities(), $nameKey);
        $languages = $this->data->languages();
        $tourOperators = $this->data->tourOperators($request->user('customer'));

        return view('trs-v2.index', [
            'locale' => $locale,
            'locales' => self::LOCALES,
            'countries' => $countries,
            'nationalities' => $nationalities,
            'languages' => $languages,
            'tourOperators' => $tourOperators,
        ]);
    }

    /**
     * Resolve and persist the UI locale (?lang= overrides session, then app default).
     */
    protected function resolveLocale(Request $request): string
    {
        $requested = $request->query('lang');
        if (is_string($requested) && in_array($requested, self::LOCALES, true)) {
            session(['trs_locale' => $requested]);

            return $requested;
        }

        $session = session('trs_locale');
        if (is_string($session) && in_array($session, self::LOCALES, true)) {
            return $session;
        }

        $default = config('app.locale', 'de');

        return in_array($default, self::LOCALES, true) ? $default : 'de';
    }

    /**
     * Reduce localized lookup rows to {code, name} for the current locale,
     * sorted by the localized name (umlaut-aware where ICU is available).
     *
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array{code:string, name:string}>
     */
    protected function localize(array $rows, string $nameKey): array
    {
        $items = array_map(fn ($row) => [
            'code' => $row['code'],
            'name' => $row[$nameKey] ?? $row['name_en'] ?? $row['code'],
        ], $rows);

        if (class_exists(\Collator::class)) {
            $collator = new \Collator(App::getLocale());
            usort($items, fn ($a, $b) => $collator->compare($a['name'], $b['name']));
        } else {
            usort($items, fn ($a, $b) => strcasecmp($a['name'], $b['name']));
        }

        return $items;
    }

    /**
     * Run a travel-requirements search (proxy to pds-api content/all).
     * Returns the normalized grouped contract consumed by trsResults().
     */
    public function search(Request $request)
    {
        $customer = $request->user('customer');
        $tab = (string) $request->input('tab', 'ptd');
        $input = $request->all();

        // Remember the query so the PDF route can rebuild the same request.
        session(['trs_last_search' => $input]);

        return response()->json(
            $this->searchService->search($customer, $tab, $input)
        );
    }

    /**
     * Stream a PDF for the last search (optionally narrowed to one
     * destination/nationality via query params), proxied from pds-api.
     */
    public function pdf(Request $request)
    {
        $customer = $request->user('customer');
        $input = session('trs_last_search');
        if (! is_array($input) || empty($input)) {
            abort(404);
        }

        // Narrow to a single destination/nationality for per-record PDFs.
        if ($dest = $request->query('destination')) {
            $input['destinations'] = [$dest];
            $input['transit'] = [];
        }
        if ($nat = $request->query('nationality')) {
            $input['nationalities'] = [$nat];
        }

        $query = $this->searchService->pdfQuery($input);

        $base = rtrim(config('services.pds_api.base_url', 'https://api.passolution.eu/api/v2'), '/');
        $response = Http::withToken($customer->getActiveApiToken())
            ->timeout((int) config('services.pds_api.timeout', 60))
            ->accept('application/pdf')
            ->get($base.'/content/all/pdf', $query);

        if (! $response->successful()) {
            abort(502, 'PDF konnte nicht erzeugt werden.');
        }

        return response($response->body(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="travel-requirements.pdf"',
        ]);
    }

    /** Abo: verified company emails for the subscription form. */
    public function aboEmails(Request $request)
    {
        return response()->json([
            'emails' => $this->subscriptionService->verifiedEmails($request->user('customer')),
        ]);
    }

    /** Abo: add a new (unverified) company email; triggers verification mail. */
    public function aboAddEmail(Request $request)
    {
        $email = (string) $request->input('email', '');
        $ok = $email !== '' && $this->subscriptionService->addEmail($request->user('customer'), $email);

        return response()->json(['ok' => $ok]);
    }

    /** Abo: create an "important change" subscription for the chosen destinations. */
    public function aboSave(Request $request)
    {
        $result = $this->subscriptionService->save(
            $request->user('customer'),
            (string) $request->input('name', ''),
            (array) $request->input('countries', []),
            (array) $request->input('emails', []),
            (bool) $request->input('active_destinations_only', false),
        );

        return response()->json($result);
    }

    /** Cruise dropdown: lines that have future cruises. */
    public function cruiseLines(Request $request)
    {
        return response()->json(['data' => $this->data->cruiseLines($request->user('customer'))]);
    }

    /** Cruise dropdown: ships of a line. */
    public function cruiseShips(Request $request)
    {
        return response()->json(['data' => $this->data->cruiseShips(
            $request->user('customer'),
            (int) $request->input('line_id'),
            $request->input('start_date'),
            $request->input('end_date'),
        )]);
    }

    /** Cruise dropdown: routes of a ship. */
    public function cruiseRoutes(Request $request)
    {
        return response()->json(['data' => $this->data->cruiseRoutes(
            $request->user('customer'),
            (int) $request->input('ship_id'),
            $request->input('start_date'),
            $request->input('end_date'),
        )]);
    }

    /** Cruise dropdown: concrete cruises (dates + compass ids) of a route. */
    public function cruiseCruises(Request $request)
    {
        return response()->json(['data' => $this->data->cruiseCruises(
            $request->user('customer'),
            (int) $request->input('route_id'),
        )]);
    }
}
