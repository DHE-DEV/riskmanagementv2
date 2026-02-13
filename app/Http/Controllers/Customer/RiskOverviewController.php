<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
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

        return view('livewire.pages.risk-overview', [
            'customer' => $customer,
        ]);
    }

    /**
     * Get aggregated risk data for all countries with events.
     */
    public function getData(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();

        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'Nicht authentifiziert'], 401);
        }

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(404);
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

            return response()->json([
                'success' => true,
                'data' => $data,
                'filters' => [
                    'priority' => $priorityFilter,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ],
            ]);
        }

        // Default: days ahead
        $daysAhead = (int) $request->input('days', 30);

        // Validate days parameter
        if (! in_array($daysAhead, [7, 14, 30, 60, 90])) {
            $daysAhead = 30;
        }

        $data = $this->riskOverviewService->getAggregatedRiskData(
            $customer->id,
            $priorityFilter,
            $daysAhead
        );

        return response()->json([
            'success' => true,
            'data' => $data,
            'filters' => [
                'priority' => $priorityFilter,
                'days' => $daysAhead,
            ],
        ]);
    }

    /**
     * Get trips with matched events from all destination countries.
     */
    public function getTrips(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();

        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'Nicht authentifiziert'], 401);
        }

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(404);
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

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        $daysAhead = (int) $request->input('days', 30);

        if (! in_array($daysAhead, [7, 14, 30, 60, 90])) {
            $daysAhead = 30;
        }

        $data = $this->riskOverviewService->getTripsWithEvents(
            $customer->id,
            $priorityFilter,
            $daysAhead
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get detailed risk information for a specific country.
     */
    public function getCountryDetails(Request $request, string $countryCode): JsonResponse
    {
        $customer = auth('customer')->user();

        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'Nicht authentifiziert'], 401);
        }

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(404);
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

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
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

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
