<?php

namespace App\Http\Controllers\Embed;

use App\Http\Controllers\Controller;
use App\Services\CustomerFeatureService;
use App\Services\RiskOverviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmbedRiskOverviewController extends Controller
{
    protected RiskOverviewService $riskOverviewService;

    protected CustomerFeatureService $featureService;

    public function __construct(RiskOverviewService $riskOverviewService, CustomerFeatureService $featureService)
    {
        $this->riskOverviewService = $riskOverviewService;
        $this->featureService = $featureService;
    }

    /**
     * Show risk overview or login form depending on auth state.
     */
    public function index()
    {
        $customer = auth('customer')->user();

        if (! $customer) {
            return view('livewire.pages.embed.risk-overview-login');
        }

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            abort(403, 'Diese Funktion ist für Ihren Account nicht freigeschaltet.');
        }

        return view('livewire.pages.embed.risk-overview', [
            'customer' => $customer,
        ]);
    }

    /**
     * Handle login POST from embed form.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'password.required' => 'Bitte geben Sie Ihr Passwort ein.',
        ]);

        $this->ensureIsNotRateLimited($request);

        if (! Auth::guard('customer')->attempt(
            $request->only('email', 'password'),
            $request->boolean('remember')
        )) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => __('Die eingegebenen Anmeldedaten sind ungültig.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        $request->session()->regenerate();

        return redirect()->route('embed.risk-overview');
    }

    /**
     * Get aggregated risk data (delegates to RiskOverviewController logic).
     */
    public function getData(Request $request): JsonResponse
    {
        $customer = auth('customer')->user();

        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'Nicht authentifiziert'], 401);
        }

        if (! $this->featureService->isFeatureEnabled('navigation_risk_overview_enabled', $customer)) {
            return response()->json(['success' => false, 'message' => 'Nicht berechtigt'], 403);
        }

        $priorityFilter = $request->input('priority');

        if ($priorityFilter && ! in_array($priorityFilter, ['high', 'medium', 'low', 'info'])) {
            $priorityFilter = null;
        }

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($dateFrom) {
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
            return response()->json(['success' => false, 'message' => 'Nicht berechtigt'], 403);
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

        // Validate days parameter (-1 = all)
        if (! in_array($daysAhead, [-1, 0, 7, 14, 30, 60, 90, 180, 360])) {
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
            return response()->json(['success' => false, 'message' => 'Nicht berechtigt'], 403);
        }

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

        $daysAhead = (int) $request->input('days', 30);

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

    /**
     * Ensure the login request is not rate limited.
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());
    }
}
