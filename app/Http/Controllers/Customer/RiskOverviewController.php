<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Folder\Folder;
use App\Models\Label;
use App\Services\CustomerFeatureService;
use App\Services\RiskOverviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        if (! $customer) {
            return redirect()->route('customer.login');
        }

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(404);
        }

        $isDebugUser = in_array($customer->email, config('feed.debug_emails', []));

        return view('livewire.pages.risk-overview', [
            'customer' => $customer,
            'isDebugUser' => $isDebugUser,
        ]);
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

        $isDebugUser = in_array($customer->email, config('feed.debug_emails', []));

        if ($isDebugUser) {
            $this->riskOverviewService->enablePdsDebug();
        }

        $priorityFilter = $request->input('priority'); // null, high, medium, low, info

        // Validate priority parameter
        if ($priorityFilter && ! in_array($priorityFilter, ['high', 'medium', 'low', 'info'])) {
            $priorityFilter = null;
        }

        // Check for custom date range
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($dateFrom) {
            // Custom date range
            $data = $this->riskOverviewService->getAggregatedRiskDataByDateRange(
                $customer->id,
                $dateFrom,
                $dateTo,
                $priorityFilter
            );

            $response = [
                'success' => true,
                'data' => $data,
                'filters' => [
                    'priority' => $priorityFilter,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
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
            $daysAhead
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

        $isDebugUser = in_array($customer->email, config('feed.debug_emails', []));

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

        $isDebugUser = in_array($customer->email, config('feed.debug_emails', []));

        if ($isDebugUser) {
            $this->riskOverviewService->enablePdsDebug();
        }

        // Check for custom date range
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($dateFrom) {
            $data = $this->riskOverviewService->getCountryRiskDetailsByDateRange(
                $customer->id,
                $countryCode,
                $dateFrom,
                $dateTo
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
            $daysAhead
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
}
