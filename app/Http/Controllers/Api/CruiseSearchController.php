<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TourismCruiseLine;
use App\Models\TourismCruiseShip;
use App\Models\TourismCruiseRoute;
use App\Models\TourismCruiseRouteCruise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CruiseSearchController extends Controller
{
    /**
     * Get all cruise lines (Reedereien)
     */
    public function getCruiseLines()
    {
        $lines = TourismCruiseLine::select('id', 'name', 'public_name')
            ->orderBy('name')
            ->get()
            ->map(function ($line) {
                return [
                    'id' => $line->id,
                    'name' => $line->display_name,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $lines,
            'count' => $lines->count(),
        ]);
    }

    /**
     * Get ships filtered by cruise line
     */
    public function getShips(Request $request)
    {
        $query = TourismCruiseShip::select('id', 'name', 'line_id')
            ->orderBy('name');

        // Filter by cruise line if provided
        if ($request->has('line_id') && $request->line_id) {
            $query->where('line_id', $request->line_id);
        }

        $ships = $query->get();

        return response()->json([
            'success' => true,
            'data' => $ships,
            'count' => $ships->count(),
        ]);
    }

    /**
     * Get routes filtered by ship
     */
    public function getRoutes(Request $request)
    {
        $query = TourismCruiseRoute::select('id', 'name', 'ship_id')
            ->orderBy('name');

        // Filter by ship if provided
        if ($request->has('ship_id') && $request->ship_id) {
            $query->where('ship_id', $request->ship_id);
        }

        $routes = $query->get();

        return response()->json([
            'success' => true,
            'data' => $routes,
            'count' => $routes->count(),
        ]);
    }

    /**
     * Get available cruise dates filtered by route
     */
    public function getCruiseDates(Request $request)
    {
        $query = TourismCruiseRouteCruise::query();

        // Filter by route if provided - use mapping table
        if ($request->has('route_id') && $request->route_id) {
            // Get cruise_compass_id from mapping table
            $cruiseCompassIds = DB::table('tourism_cruise_route_cruise_compass')
                ->where('route_id', $request->route_id)
                ->pluck('cruise_compass_id');

            if ($cruiseCompassIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'count' => 0,
                ]);
            }

            $query->whereIn('cruise_compass_route_id', $cruiseCompassIds);
        }

        // Optionally filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->where('start_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('start_date', '<=', $request->date_to);
        }

        $cruises = $query->select(
                'id',
                'cruise_compass_route_id',
                'start_date',
                'duration_in_days'
            )
            ->orderBy('start_date', 'asc')
            ->get()
            ->map(function ($cruise) {
                $endDate = null;
                if ($cruise->start_date && $cruise->duration_in_days) {
                    $endDate = \Carbon\Carbon::parse($cruise->start_date)
                        ->addDays($cruise->duration_in_days)
                        ->format('Y-m-d');
                }

                return [
                    'id' => $cruise->id,
                    'cruise_compass_route_id' => $cruise->cruise_compass_route_id,
                    'start_date' => $cruise->start_date,
                    'end_date' => $endDate,
                    'duration_days' => $cruise->duration_in_days,
                    'display_text' => \Carbon\Carbon::parse($cruise->start_date)->format('d.m.Y') .
                        ' - ' .
                        ($endDate ? \Carbon\Carbon::parse($endDate)->format('d.m.Y') : 'N/A') .
                        ' (' . $cruise->duration_in_days . ' Tage)',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $cruises,
            'count' => $cruises->count(),
        ]);
    }

    /**
     * Search for cruises with filters
     */
    public function search(Request $request)
    {
        $request->validate([
            'line_id' => 'nullable|integer|exists:tourism_cruise_lines,id',
            'ship_id' => 'nullable|integer|exists:tourism_cruise_ships,id',
            'route_id' => 'nullable|integer|exists:tourism_cruise_routes,id',
            'cruise_date_id' => 'nullable|integer|exists:tourism_cruise_route_cruises,id',
            'ship_name' => 'nullable|string|max:255',
            'route_name' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        // Start with cruise dates query - use mapping table
        $query = TourismCruiseRouteCruise::query()
            ->select(
                'tourism_cruise_route_cruises.*',
                'tourism_cruise_routes.name as route_name',
                'tourism_cruise_routes.ship_id',
                'tourism_cruise_ships.name as ship_name',
                'tourism_cruise_ships.line_id',
                'tourism_cruise_lines.name as line_name',
                'tourism_cruise_lines.public_name as line_public_name'
            )
            ->join('tourism_cruise_route_cruise_compass', 'tourism_cruise_route_cruises.cruise_compass_route_id', '=', 'tourism_cruise_route_cruise_compass.cruise_compass_id')
            ->join('tourism_cruise_routes', 'tourism_cruise_route_cruise_compass.route_id', '=', 'tourism_cruise_routes.id')
            ->join('tourism_cruise_ships', 'tourism_cruise_routes.ship_id', '=', 'tourism_cruise_ships.id')
            ->join('tourism_cruise_lines', 'tourism_cruise_ships.line_id', '=', 'tourism_cruise_lines.id');

        // Apply filters
        if ($request->has('line_id') && $request->line_id) {
            $query->where('tourism_cruise_ships.line_id', $request->line_id);
        }

        if ($request->has('ship_id') && $request->ship_id) {
            $query->where('tourism_cruise_routes.ship_id', $request->ship_id);
        }

        if ($request->has('route_id') && $request->route_id) {
            $query->where('tourism_cruise_routes.id', $request->route_id);
        }

        if ($request->has('cruise_date_id') && $request->cruise_date_id) {
            $query->where('tourism_cruise_route_cruises.id', $request->cruise_date_id);
        }

        if ($request->has('ship_name') && $request->ship_name) {
            $query->where('tourism_cruise_ships.name', 'LIKE', '%' . $request->ship_name . '%');
        }

        if ($request->has('route_name') && $request->route_name) {
            $query->where('tourism_cruise_routes.name', 'LIKE', '%' . $request->route_name . '%');
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->where('tourism_cruise_route_cruises.start_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('tourism_cruise_route_cruises.start_date', '<=', $request->date_to);
        }

        // Order by start date
        $query->orderBy('tourism_cruise_route_cruises.start_date', 'asc');

        // Limit results to 100 for performance
        $cruises = $query->limit(100)->get();

        // Get port information for each cruise
        $results = $cruises->map(function ($cruise) {
            // Get ports for this route
            $ports = DB::table('tourism_cruise_route_courses')
                ->join('tourism_cruise_ports', 'tourism_cruise_route_courses.port_id', '=', 'tourism_cruise_ports.id')
                ->where('tourism_cruise_route_courses.cruise_compass_route_id', $cruise->cruise_compass_route_id)
                ->orderBy('tourism_cruise_route_courses.day')
                ->select(
                    'tourism_cruise_ports.id',
                    'tourism_cruise_ports.name',
                    'tourism_cruise_ports.geocode_lat as lat',
                    'tourism_cruise_ports.geocode_lng as lng',
                    'tourism_cruise_route_courses.day',
                    'tourism_cruise_route_courses.arrive_at',
                    'tourism_cruise_route_courses.depart_at'
                )
                ->get()
                ->map(function ($port) {
                    // Format times to HH:MM
                    return [
                        'id' => $port->id,
                        'name' => $port->name,
                        'lat' => $port->lat,
                        'lng' => $port->lng,
                        'day' => $port->day,
                        'arrive_at' => $port->arrive_at ? substr($port->arrive_at, 0, 5) : null,
                        'depart_at' => $port->depart_at ? substr($port->depart_at, 0, 5) : null,
                    ];
                });

            $endDate = null;
            if ($cruise->start_date && $cruise->duration_in_days) {
                $endDate = \Carbon\Carbon::parse($cruise->start_date)
                    ->addDays($cruise->duration_in_days)
                    ->format('Y-m-d');
            }

            return [
                'id' => $cruise->id,
                'line_name' => $cruise->line_public_name ?: $cruise->line_name,
                'ship_name' => $cruise->ship_name,
                'route_name' => $cruise->route_name,
                'start_date' => $cruise->start_date,
                'end_date' => $endDate,
                'duration_days' => $cruise->duration_in_days,
                'ports' => $ports,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $results,
            'count' => $results->count(),
        ]);
    }
}
