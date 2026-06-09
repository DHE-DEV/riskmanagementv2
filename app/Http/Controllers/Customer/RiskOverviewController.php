<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Mail\TravelAlertOrderMail;
use App\Models\Customer;
use App\Models\CustomerFeatureOverride;
use App\Models\CustomEvent;
use App\Models\TravelAlertOrder;
use App\Models\Folder\Folder;
use App\Models\Label;
use App\Notifications\TravelAlertWelcomeNotification;
use App\Services\CustomerFeatureService;
use App\Services\KeycloakUserService;
use App\Services\RiskOverviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RiskOverviewController extends Controller
{
    protected RiskOverviewService $riskOverviewService;

    protected CustomerFeatureService $featureService;

    public function __construct(RiskOverviewService $riskOverviewService, CustomerFeatureService $featureService)
    {
        $this->riskOverviewService = $riskOverviewService;
        $this->featureService = $featureService;
    }

    /**
     * Display the risk overview page.
     */
    public function index()
    {
        $customer = auth('customer')->user();
        $isLoggedIn = (bool) $customer;

        // Show promo page if not logged in or feature not enabled
        if (! $customer || ! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            return response()
                ->view('livewire.pages.risk-overview-promo', ['isLoggedIn' => $isLoggedIn])
                ->header('Cache-Control', 'no-cache, private');
        }

        $isDebugUser = config('feed.debug_enabled') || in_array($customer->email, config('feed.debug_emails', []));

        return view('livewire.pages.risk-overview', [
            'customer' => $customer,
            'isDebugUser' => $isDebugUser,
        ]);
    }

    /**
     * Handle TravelAlert order form submission.
     */
    public function submitOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_type' => 'required|in:private,business',
            'business_type' => 'nullable|array',
            'business_type.*' => 'string|in:travel_agency,organizer,online_provider,mobile_travel_consultant,software_provider,cooperation,other',
            'company' => 'required_if:customer_type,business|nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'street' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'existing_billing' => 'required|in:ja,nein',
            'remarks' => 'nullable|string|max:2000',
        ]);

        try {
            TravelAlertOrder::create($validated);

            // Create customer account if none exists for this email
            $existingCustomer = Customer::where('email', $validated['email'])->first();
            $accountCreated = false;

            if (! $existingCustomer) {
                $name = trim(($validated['first_name'] ?? '').' '.($validated['last_name'] ?? ''));
                if (empty($name)) {
                    $name = $validated['company'];
                }

                $customerData = [
                    'name' => $name,
                    'email' => $validated['email'],
                    'password' => Hash::make(Str::random(32)),
                    'company_street' => $validated['street'],
                    'company_postal_code' => $validated['postal_code'],
                    'company_city' => $validated['city'],
                    'company_country' => $validated['country'],
                    'phone' => $validated['phone'],
                    'customer_type' => $validated['customer_type'],
                ];

                if ($validated['customer_type'] === 'business') {
                    $customerData['company_name'] = $validated['company'] ?? '';
                    $customerData['business_type'] = $validated['business_type'] ?? [];
                }

                $customer = Customer::create($customerData);

                // Auto-enable TravelAlert feature for the new customer
                CustomerFeatureOverride::create([
                    'customer_id' => $customer->id,
                    'navigation_risk_overview_enabled' => true,
                ]);

                // Sync new customer to Keycloak
                try {
                    app(KeycloakUserService::class)->syncCustomer($customer);
                } catch (\Exception $e) {
                    Log::warning('Keycloak sync failed for new TravelAlert customer', [
                        'customer_id' => $customer->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Send welcome email with verification link and order details
                $customer->notify(new TravelAlertWelcomeNotification($validated));

                $accountCreated = true;
                Log::info('Customer account created from TravelAlert order', ['customer_id' => $customer->id, 'email' => $customer->email]);
            } else {
                $customer = $existingCustomer;

                // Auto-enable TravelAlert feature for existing customer if not already enabled
                CustomerFeatureOverride::firstOrCreate(
                    ['customer_id' => $customer->id],
                    ['navigation_risk_overview_enabled' => true]
                );

                // Ensure navigation_risk_overview_enabled is true even if override already existed
                CustomerFeatureOverride::where('customer_id', $customer->id)
                    ->update(['navigation_risk_overview_enabled' => true]);

                Log::info('TravelAlert order for existing customer', ['customer_id' => $customer->id, 'email' => $customer->email]);
            }

            $recipient = config('mail.order_recipient', 'info@passolution.de');

            Mail::to($recipient)
                ->bcc(['info@passolution.de', 'info@dhe.de'])
                ->send(new TravelAlertOrderMail($validated, $accountCreated, $customer->id));

            Log::info('TravelAlert order submitted', ['company' => $validated['company'], 'email' => $validated['email']]);

            return response()->json([
                'success' => true,
                'message' => 'Bestellung erfolgreich eingereicht.',
                'account_created' => $accountCreated,
            ]);
        } catch (\Exception $e) {
            Log::error('TravelAlert order failed: '.$e->getMessage(), ['data' => $validated]);

            return response()->json(['success' => false, 'message' => 'Fehler beim Senden. Bitte versuchen Sie es erneut.'], 500);
        }
    }

    /**
     * Mark a product tour as completed.
     */
    public function completeTour(Request $request): JsonResponse
    {
        $tour = $request->input('tour', 'platform');
        $dontShowAgain = $request->boolean('dont_show_again', false);

        if ($dontShowAgain) {
            $field = self::tourField($tour);
            $customer = auth('customer')->user();
            $customer->update([$field => true]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Reset a product tour so it shows again.
     */
    public function resetTour(Request $request): JsonResponse
    {
        $tour = $request->input('tour', 'platform');
        $fields = ['platform' => 'has_seen_platform_tour', 'travel_alert' => 'has_seen_travel_alert_tour', 'gtm' => 'has_seen_gtm_tour'];
        $field = $fields[$tour] ?? 'has_seen_platform_tour';

        $customer = auth('customer')->user();
        $customer->update([$field => false]);

        return response()->json(['success' => true]);
    }

    private static function tourField(string $tour): string
    {
        $fields = [
            'platform' => 'has_seen_platform_tour',
            'travel_alert' => 'has_seen_travel_alert_tour',
            'gtm' => 'has_seen_gtm_tour',
            'trs' => 'has_seen_trs_tour',
            'entry_conditions' => 'has_seen_entry_conditions_tour',
            'travel_data' => 'has_seen_travel_data_tour',
            'travel_links' => 'has_seen_travel_links_tour',
            'booking' => 'has_seen_booking_tour',
            'airports' => 'has_seen_airports_tour',
            'branches' => 'has_seen_branches_tour',
            'my_travelers' => 'has_seen_my_travelers_tour',
            'customer_events' => 'has_seen_customer_events_tour',
            'cruise' => 'has_seen_cruise_tour',
            'business_visa' => 'has_seen_business_visa_tour',
            'visumpoint' => 'has_seen_visumpoint_tour',
            'settings' => 'has_seen_settings_tour',
        ];

        return $fields[$tour] ?? 'has_seen_platform_tour';
    }

    /**
     * Get aggregated risk data for all countries with events.
     */
    public function getData(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        $customer = auth('customer')->user();

        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'Nicht authentifiziert'], 401);
        }

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(404);
        }

        $isDebugUser = config('feed.debug_enabled') || in_array($customer->email, config('feed.debug_emails', []));

        if ($isDebugUser) {
            $this->riskOverviewService->enablePdsDebug();
        }

        $priorityFilter = $request->input('priority'); // null, high, medium, low, info

        // Validate priority parameter
        if ($priorityFilter && ! in_array($priorityFilter, ['high', 'medium', 'low', 'info'])) {
            $priorityFilter = null;
        }

        $labelId = $request->input('label') ? (int) $request->input('label') : null;

        // Check for custom date range
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($dateFrom) {
            // Custom date range
            $data = $this->riskOverviewService->getAggregatedRiskDataByDateRange(
                $customer->id,
                $dateFrom,
                $dateTo,
                $priorityFilter,
                $labelId
            );

            $response = [
                'success' => true,
                'data' => $data,
                'filters' => [
                    'priority' => $priorityFilter,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'label' => $labelId,
                ],
            ];

            if ($isDebugUser) {
                $response['debug'] = [
                    'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                    'params' => $request->all(),
                    'pds_api_calls' => $this->riskOverviewService->getPdsDebugLog(),
                ];
            }

            return response()->json($response);
        }

        // Default: days ahead
        $daysAhead = (int) $request->input('days', 30);

        // Validate days parameter (-1 = all)
        if (! in_array($daysAhead, [-1, 0, 7, 14, 30, 60, 90, 180, 360])) {
            $daysAhead = 30;
        }

        $data = $this->riskOverviewService->getAggregatedRiskData(
            $customer->id,
            $priorityFilter,
            $daysAhead,
            $labelId
        );

        $response = [
            'success' => true,
            'data' => $data,
            'filters' => [
                'priority' => $priorityFilter,
                'days' => $daysAhead,
            ],
        ];

        if ($isDebugUser) {
            $response['debug'] = [
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'params' => $request->all(),
                'pds_api_calls' => $this->riskOverviewService->getPdsDebugLog(),
            ];
        }

        return response()->json($response);
    }

    /**
     * Get trips with matched events from all destination countries.
     */
    public function getTrips(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        $customer = auth('customer')->user();

        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'Nicht authentifiziert'], 401);
        }

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(404);
        }

        $isDebugUser = config('feed.debug_enabled') || in_array($customer->email, config('feed.debug_emails', []));

        if ($isDebugUser) {
            $this->riskOverviewService->enablePdsDebug();
        }

        $priorityFilter = $request->input('priority');

        if ($priorityFilter && ! in_array($priorityFilter, ['high', 'medium', 'low', 'info'])) {
            $priorityFilter = null;
        }

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($dateFrom) {
            $data = $this->riskOverviewService->getTripsWithEventsByDateRange(
                $customer->id,
                $dateFrom,
                $dateTo,
                $priorityFilter
            );

            $response = [
                'success' => true,
                'data' => $data,
            ];

            if ($isDebugUser) {
                $response['debug'] = [
                    'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                    'params' => $request->all(),
                    'pds_api_calls' => $this->riskOverviewService->getPdsDebugLog(),
                ];
            }

            return response()->json($response);
        }

        $daysAhead = (int) $request->input('days', 30);

        // Validate days parameter (-1 = all)
        if (! in_array($daysAhead, [-1, 0, 7, 14, 30, 60, 90, 180, 360])) {
            $daysAhead = 30;
        }

        $data = $this->riskOverviewService->getTripsWithEvents(
            $customer->id,
            $priorityFilter,
            $daysAhead
        );

        $response = [
            'success' => true,
            'data' => $data,
        ];

        if ($isDebugUser) {
            $response['debug'] = [
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'params' => $request->all(),
                'pds_api_calls' => $this->riskOverviewService->getPdsDebugLog(),
            ];
        }

        return response()->json($response);
    }

    /**
     * Get detailed risk information for a specific country.
     */
    public function getCountryDetails(Request $request, string $countryCode): JsonResponse
    {
        $startTime = microtime(true);
        $customer = auth('customer')->user();

        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'Nicht authentifiziert'], 401);
        }

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(404);
        }

        $isDebugUser = config('feed.debug_enabled') || in_array($customer->email, config('feed.debug_emails', []));

        if ($isDebugUser) {
            $this->riskOverviewService->enablePdsDebug();
        }

        $labelId = $request->input('label') ? (int) $request->input('label') : null;

        // Check for custom date range
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($dateFrom) {
            $data = $this->riskOverviewService->getCountryRiskDetailsByDateRange(
                $customer->id,
                $countryCode,
                $dateFrom,
                $dateTo,
                $labelId
            );

            $response = [
                'success' => true,
                'data' => $data,
            ];

            if ($isDebugUser) {
                $response['debug'] = [
                    'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                    'params' => array_merge($request->all(), ['country_code' => $countryCode]),
                    'pds_api_calls' => $this->riskOverviewService->getPdsDebugLog(),
                ];
            }

            return response()->json($response);
        }

        // Default: days ahead
        $daysAhead = (int) $request->input('days', 30);

        // Validate days parameter
        if (! in_array($daysAhead, [7, 14, 30, 60, 90])) {
            $daysAhead = 30;
        }

        $data = $this->riskOverviewService->getCountryRiskDetails(
            $customer->id,
            $countryCode,
            $daysAhead,
            $labelId
        );

        $response = [
            'success' => true,
            'data' => $data,
        ];

        if ($isDebugUser) {
            $response['debug'] = [
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'params' => array_merge($request->all(), ['country_code' => $countryCode]),
                'pds_api_calls' => $this->riskOverviewService->getPdsDebugLog(),
            ];
        }

        return response()->json($response);
    }

    /**
     * Search labels for autocomplete.
     */
    public function searchLabels(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();

        if (! $customer) {
            return response()->json([], 401);
        }

        $query = $request->input('q', '');

        $labels = Label::where('customer_id', $customer->id)
            ->active()
            ->where('name', 'like', '%'.$query.'%')
            ->ordered()
            ->limit(20)
            ->get(['id', 'name', 'color', 'icon']);

        return response()->json($labels);
    }

    /**
     * Attach a label to a folder (create if new).
     */
    public function attachLabel(Request $request, string $folderId): JsonResponse
    {
        $customer = auth('customer')->user();

        if (! $customer) {
            return response()->json(['success' => false], 401);
        }

        $folder = Folder::where('customer_id', $customer->id)->where('id', $folderId)->first();

        if (! $folder) {
            return response()->json(['success' => false, 'message' => 'Reise nicht gefunden'], 404);
        }

        $labelName = trim($request->input('name', ''));
        $labelId = $request->input('label_id');

        if ($labelId) {
            $label = Label::where('customer_id', $customer->id)->where('id', $labelId)->first();
        } else {
            if (empty($labelName)) {
                return response()->json(['success' => false, 'message' => 'Label-Name erforderlich'], 422);
            }

            $label = Label::where('customer_id', $customer->id)
                ->where('name', $labelName)
                ->first();

            if (! $label) {
                $label = Label::create([
                    'customer_id' => $customer->id,
                    'name' => $labelName,
                ]);
            }
        }

        if (! $label) {
            return response()->json(['success' => false, 'message' => 'Label nicht gefunden'], 404);
        }

        $folder->labels()->syncWithoutDetaching([$label->id]);

        $labels = $folder->labels()->get(['labels.id', 'name', 'color', 'icon']);

        return response()->json([
            'success' => true,
            'labels' => $labels,
        ]);
    }

    /**
     * Detach a label from a folder.
     */
    public function detachLabel(string $folderId, string $labelId): JsonResponse
    {
        $customer = auth('customer')->user();

        if (! $customer) {
            return response()->json(['success' => false], 401);
        }

        $folder = Folder::where('customer_id', $customer->id)->where('id', $folderId)->first();

        if (! $folder) {
            return response()->json(['success' => false, 'message' => 'Reise nicht gefunden'], 404);
        }

        $folder->labels()->detach($labelId);

        $labels = $folder->labels()->get(['labels.id', 'name', 'color', 'icon']);

        return response()->json([
            'success' => true,
            'labels' => $labels,
        ]);
    }

    /**
     * Attach a label to a PDS API trip (create label if new).
     */
    public function attachPdsTripLabel(Request $request, string $pdsTid): JsonResponse
    {
        $customer = auth('customer')->user();

        if (! $customer) {
            return response()->json(['success' => false], 401);
        }

        $labelName = trim($request->input('name', ''));
        $labelId = $request->input('label_id');

        if ($labelId) {
            $label = Label::where('customer_id', $customer->id)->where('id', $labelId)->first();
        } else {
            if (empty($labelName)) {
                return response()->json(['success' => false, 'message' => 'Label-Name erforderlich'], 422);
            }

            $label = Label::where('customer_id', $customer->id)
                ->where('name', $labelName)
                ->first();

            if (! $label) {
                $label = Label::create([
                    'customer_id' => $customer->id,
                    'name' => $labelName,
                ]);
            }
        }

        if (! $label) {
            return response()->json(['success' => false, 'message' => 'Label nicht gefunden'], 404);
        }

        \Illuminate\Support\Facades\DB::table('pds_trip_label')->updateOrInsert(
            [
                'customer_id' => $customer->id,
                'pds_tid' => $pdsTid,
                'label_id' => $label->id,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $labels = Label::forPdsTrip($customer->id, $pdsTid)
            ->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'color' => $l->color, 'icon' => $l->icon]);

        return response()->json([
            'success' => true,
            'labels' => $labels->values(),
        ]);
    }

    /**
     * Detach a label from a PDS API trip.
     */
    public function detachPdsTripLabel(string $pdsTid, string $labelId): JsonResponse
    {
        $customer = auth('customer')->user();

        if (! $customer) {
            return response()->json(['success' => false], 401);
        }

        \Illuminate\Support\Facades\DB::table('pds_trip_label')
            ->where('customer_id', $customer->id)
            ->where('pds_tid', $pdsTid)
            ->where('label_id', $labelId)
            ->delete();

        $labels = Label::forPdsTrip($customer->id, $pdsTid)
            ->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'color' => $l->color, 'icon' => $l->icon]);

        return response()->json([
            'success' => true,
            'labels' => $labels->values(),
        ]);
    }

    /**
     * Attach a label to a custom event (create label if new).
     */
    public function attachEventLabel(Request $request, int $eventId): JsonResponse
    {
        $customer = auth('customer')->user();

        if (! $customer) {
            return response()->json(['success' => false], 401);
        }

        $event = CustomEvent::find($eventId);

        if (! $event) {
            return response()->json(['success' => false, 'message' => 'Ereignis nicht gefunden'], 404);
        }

        $labelName = trim($request->input('name', ''));
        $labelId = $request->input('label_id');

        if ($labelId) {
            $label = Label::where('customer_id', $customer->id)->where('id', $labelId)->first();
        } else {
            if (empty($labelName)) {
                return response()->json(['success' => false, 'message' => 'Label-Name erforderlich'], 422);
            }

            $label = Label::where('customer_id', $customer->id)
                ->where('name', $labelName)
                ->first();

            if (! $label) {
                $label = Label::create([
                    'customer_id' => $customer->id,
                    'name' => $labelName,
                ]);
            }
        }

        if (! $label) {
            return response()->json(['success' => false, 'message' => 'Label nicht gefunden'], 404);
        }

        $event->labels()->syncWithoutDetaching([$label->id]);

        // Return only this customer's labels for the event
        $labels = $event->labels()
            ->where('labels.customer_id', $customer->id)
            ->get(['labels.id', 'name', 'color', 'icon']);

        return response()->json([
            'success' => true,
            'labels' => $labels,
        ]);
    }

    /**
     * Detach a label from a custom event.
     */
    public function detachEventLabel(int $eventId, string $labelId): JsonResponse
    {
        $customer = auth('customer')->user();

        if (! $customer) {
            return response()->json(['success' => false], 401);
        }

        $event = CustomEvent::find($eventId);

        if (! $event) {
            return response()->json(['success' => false, 'message' => 'Ereignis nicht gefunden'], 404);
        }

        // Only detach if the label belongs to this customer
        $label = Label::where('customer_id', $customer->id)->where('id', $labelId)->first();

        if ($label) {
            $event->labels()->detach($label->id);
        }

        // Return only this customer's labels for the event
        $labels = $event->labels()
            ->where('labels.customer_id', $customer->id)
            ->get(['labels.id', 'name', 'color', 'icon']);

        return response()->json([
            'success' => true,
            'labels' => $labels,
        ]);
    }
}
