<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SsoLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SsoLogController extends Controller
{
    /**
     * Display a listing of SSO logs with filters.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = SsoLog::query()->orderBy('created_at', 'DESC');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'LIKE', '%' . $request->ip_address . '%');
        }

        if ($request->filled('request_id')) {
            $query->where('request_id', 'LIKE', '%' . $request->request_id . '%');
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Step filter
        if ($request->filled('step')) {
            $query->where('step', $request->step);
        }

        // Pagination
        $logs = $query->paginate(25)->withQueryString();

        // Get unique values for filters
        $statuses = SsoLog::select('status')->distinct()->pluck('status');
        $steps = SsoLog::select('step')->distinct()->pluck('step');

        return view('admin.sso-logs.index', compact('logs', 'statuses', 'steps'));
    }

    /**
     * Show detailed view of all steps for a single SSO request.
     *
     * @param string $requestId
     * @return \Illuminate\View\View
     */
    public function show(string $requestId)
    {
        $logs = SsoLog::where('request_id', $requestId)
            ->orderBy('created_at', 'ASC')
            ->get();

        if ($logs->isEmpty()) {
            abort(404, 'SSO request not found');
        }

        // Get first log for summary info
        $firstLog = $logs->first();

        // Calculate total duration
        $totalDuration = $logs->sum('duration_ms');

        // Get customer info if available
        $customer = null;
        if ($firstLog->customer_id) {
            $customer = \App\Models\Customer::find($firstLog->customer_id);
        }

        return view('admin.sso-logs.show', compact('logs', 'requestId', 'totalDuration', 'customer', 'firstLog'));
    }

    /**
     * Show statistics dashboard for SSO logs.
     *
     * @return \Illuminate\View\View
     */
    public function stats()
    {
        // Today's attempts
        $todayAttempts = SsoLog::whereDate('created_at', today())
            ->distinct('request_id')
            ->count('request_id');

        // This week's attempts
        $weekAttempts = SsoLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->distinct('request_id')
            ->count('request_id');

        // This month's attempts
        $monthAttempts = SsoLog::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->distinct('request_id')
            ->count('request_id');

        // Success rate calculation
        $totalRequests = SsoLog::select('request_id')
            ->distinct()
            ->count();

        $successfulRequests = SsoLog::where('step', 'login')
            ->where('status', 'success')
            ->distinct('request_id')
            ->count('request_id');

        $successRate = $totalRequests > 0 ? round(($successfulRequests / $totalRequests) * 100, 2) : 0;

        // Most common errors
        $commonErrors = SsoLog::select('error_message', DB::raw('count(*) as count'))
            ->where('status', 'error')
            ->whereNotNull('error_message')
            ->groupBy('error_message')
            ->orderBy('count', 'DESC')
            ->limit(10)
            ->get();

        // Average duration
        $avgDuration = SsoLog::where('status', 'success')
            ->where('step', 'login')
            ->avg('duration_ms');

        // Attempts by hour (last 24 hours)
        $attemptsByHour = SsoLog::select(
            DB::raw('HOUR(created_at) as hour'),
            DB::raw('count(DISTINCT request_id) as count')
        )
            ->where('created_at', '>=', now()->subDay())
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Top agents by SSO usage
        $topAgents = SsoLog::select('agent_id', DB::raw('count(DISTINCT request_id) as count'))
            ->whereNotNull('agent_id')
            ->groupBy('agent_id')
            ->orderBy('count', 'DESC')
            ->limit(10)
            ->get();

        // Recent errors
        $recentErrors = SsoLog::where('status', 'error')
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get();

        // Status distribution
        $statusDistribution = SsoLog::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        return view('admin.sso-logs.stats', compact(
            'todayAttempts',
            'weekAttempts',
            'monthAttempts',
            'successRate',
            'commonErrors',
            'avgDuration',
            'attemptsByHour',
            'topAgents',
            'recentErrors',
            'statusDistribution'
        ));
    }
}
