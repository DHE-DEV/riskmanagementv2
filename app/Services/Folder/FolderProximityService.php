<?php

namespace App\Services\Folder;

use App\Models\Folder\FolderTimelineLocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FolderProximityService
{
    /**
     * Find travelers near a specific point during a time range.
     *
     * @param  float  $lat  Latitude
     * @param  float  $lng  Longitude
     * @param  float  $radiusKm  Radius in kilometers
     * @param  string|null  $startTime  Start of time range
     * @param  string|null  $endTime  End of time range
     * @param  array|null  $nationalities  Filter by participant nationalities
     */
    public function findTravelersNearPoint(
        float $lat,
        float $lng,
        float $radiusKm,
        ?string $startTime = null,
        ?string $endTime = null,
        ?array $nationalities = null
    ): Collection {
        $query = FolderTimelineLocation::query()
            ->withinRadius($lat, $lng, $radiusKm)
            ->withDistance($lat, $lng);

        // Apply time range filter if provided
        if ($startTime && $endTime) {
            $query->activeDuring($startTime, $endTime);
        }

        // Apply nationality filter if provided
        if ($nationalities && count($nationalities) > 0) {
            $query->withNationality($nationalities);
        }

        return $query
            ->with(['folder', 'itinerary'])
            ->orderBy('distance_km')
            ->get()
            ->map(function ($location) {
                return [
                    'folder_id' => $location->folder_id,
                    'folder_number' => $location->folder->folder_number ?? null,
                    'location_type' => $location->location_type,
                    'location_name' => $location->location_name,
                    'country_code' => $location->country_code,
                    'lat' => $location->lat,
                    'lng' => $location->lng,
                    'start_time' => $location->start_time,
                    'end_time' => $location->end_time,
                    'distance_km' => round($location->distance_km, 2),
                    'participant_count' => count($location->participant_ids ?? []),
                    'participant_nationalities' => $location->participant_nationalities,
                ];
            });
    }

    /**
     * Find travelers in a specific country during a time range.
     *
     * @param  string  $countryCode  ISO 3166-1 alpha-2 country code
     * @param  string|null  $startTime  Start of time range
     * @param  string|null  $endTime  End of time range
     * @param  array|null  $nationalities  Filter by participant nationalities
     */
    public function findTravelersInCountry(
        string $countryCode,
        ?string $startTime = null,
        ?string $endTime = null,
        ?array $nationalities = null
    ): Collection {
        $query = FolderTimelineLocation::query()
            ->inCountry($countryCode);

        // Apply time range filter if provided
        if ($startTime && $endTime) {
            $query->activeDuring($startTime, $endTime);
        }

        // Apply nationality filter if provided
        if ($nationalities && count($nationalities) > 0) {
            $query->withNationality($nationalities);
        }

        return $query
            ->with(['folder', 'itinerary'])
            ->orderBy('start_time')
            ->get()
            ->map(function ($location) {
                return [
                    'folder_id' => $location->folder_id,
                    'folder_number' => $location->folder->folder_number ?? null,
                    'location_type' => $location->location_type,
                    'location_name' => $location->location_name,
                    'country_code' => $location->country_code,
                    'lat' => $location->lat,
                    'lng' => $location->lng,
                    'start_time' => $location->start_time,
                    'end_time' => $location->end_time,
                    'participant_count' => count($location->participant_ids ?? []),
                    'participant_nationalities' => $location->participant_nationalities,
                ];
            });
    }

    /**
     * Get statistics about travelers in different countries.
     *
     * @param  string|null  $startTime  Start of time range
     * @param  string|null  $endTime  End of time range
     */
    public function getTravelerCountStatistics(?string $startTime = null, ?string $endTime = null): Collection
    {
        $query = FolderTimelineLocation::query();

        // Apply time range filter if provided
        if ($startTime && $endTime) {
            $query->activeDuring($startTime, $endTime);
        }

        return $query
            ->select([
                'country_code',
                DB::raw('COUNT(DISTINCT folder_id) as folder_count'),
                DB::raw('COUNT(*) as location_count'),
            ])
            ->whereNotNull('country_code')
            ->groupBy('country_code')
            ->orderByDesc('folder_count')
            ->get()
            ->map(function ($stat) {
                return [
                    'country_code' => $stat->country_code,
                    'folder_count' => $stat->folder_count,
                    'location_count' => $stat->location_count,
                ];
            });
    }

    /**
     * Get all affected folders within a radius and time range.
     *
     * @param  float  $lat  Latitude
     * @param  float  $lng  Longitude
     * @param  float  $radiusKm  Radius in kilometers
     * @param  string|null  $startTime  Start of time range
     * @param  string|null  $endTime  End of time range
     */
    public function getAffectedFolders(
        float $lat,
        float $lng,
        float $radiusKm,
        ?string $startTime = null,
        ?string $endTime = null
    ): Collection {
        $query = FolderTimelineLocation::query()
            ->withinRadius($lat, $lng, $radiusKm)
            ->select([
                'folder_id',
                DB::raw('COUNT(*) as location_count'),
                DB::raw('MIN(start_time) as earliest_time'),
                DB::raw('MAX(end_time) as latest_time'),
            ]);

        // Apply time range filter if provided
        if ($startTime && $endTime) {
            $query->activeDuring($startTime, $endTime);
        }

        return $query
            ->groupBy('folder_id')
            ->get()
            ->map(function ($result) {
                $folder = \App\Models\Folder\Folder::withoutGlobalScope('customer')
                    ->find($result->folder_id);

                return [
                    'folder_id' => $result->folder_id,
                    'folder_number' => $folder->folder_number ?? null,
                    'folder_name' => $folder->folder_name ?? null,
                    'location_count' => $result->location_count,
                    'earliest_time' => $result->earliest_time,
                    'latest_time' => $result->latest_time,
                    'participant_count' => $folder->total_participants ?? 0,
                ];
            });
    }

    /**
     * Get map locations for customer's folders.
     *
     * @param  string|null  $folderId  Filter by specific folder
     * @param  string|null  $startDate  Filter by start date
     * @param  string|null  $endDate  Filter by end date
     * @param  array|null  $locationTypes  Filter by location types
     */
    public function getMapLocations(
        ?string $folderId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?array $locationTypes = null
    ): Collection {
        $query = FolderTimelineLocation::query()
            ->with('folder:id,folder_number,folder_name');

        // Apply folder filter
        if ($folderId) {
            $query->where('folder_id', $folderId);
        }

        // Apply date range filter
        if ($startDate && $endDate) {
            $query->activeDuring($startDate.' 00:00:00', $endDate.' 23:59:59');
        }

        // Apply location type filter
        if ($locationTypes && count($locationTypes) > 0) {
            $query->ofType($locationTypes);
        }

        return $query
            ->orderBy('start_time')
            ->get()
            ->map(function ($location) {
                return [
                    'id' => $location->id,
                    'folder_id' => $location->folder_id,
                    'folder_number' => $location->folder->folder_number ?? null,
                    'folder_name' => $location->folder->folder_name ?? null,
                    'lat' => (float) $location->lat,
                    'lng' => (float) $location->lng,
                    'location_type' => $location->location_type,
                    'location_name' => $location->location_name,
                    'location_code' => $location->location_code,
                    'country_code' => $location->country_code,
                    'start_time' => $location->start_time->toIso8601String(),
                    'end_time' => $location->end_time->toIso8601String(),
                    'participant_count' => count($location->participant_ids ?? []),
                ];
            });
    }
}
