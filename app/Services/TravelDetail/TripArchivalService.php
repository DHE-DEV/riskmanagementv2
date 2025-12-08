<?php

namespace App\Services\TravelDetail;

use App\Models\TravelDetail\TdTrip;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

class TripArchivalService
{
    /**
     * Get query for archivable trips
     */
    public function getArchivableTripsQuery(int $daysAfterCompletion = null): Builder
    {
        $days = $daysAfterCompletion ?? config('travel_detail.archival.days_after_completion', 30);

        return TdTrip::query()
            ->where('computed_end_at', '<', now()->subDays($days))
            ->where('is_archived', false)
            ->where('status', 'completed');
    }

    /**
     * Mark trips as archived (without moving to archive DB)
     */
    public function markAsArchived(TdTrip $trip): bool
    {
        try {
            $trip->update(['is_archived' => true]);

            Log::channel(config('travel_detail.logging.channel'))
                ->info('Marked trip as archived', ['trip_id' => $trip->id]);

            return true;
        } catch (\Exception $e) {
            Log::channel(config('travel_detail.logging.channel'))
                ->error('Failed to mark trip as archived', [
                    'trip_id' => $trip->id,
                    'error' => $e->getMessage(),
                ]);
            return false;
        }
    }

    /**
     * Move trip to archive database
     */
    public function moveToArchiveDatabase(TdTrip $trip): bool
    {
        if (!config('travel_detail.archival.use_separate_database')) {
            return $this->markAsArchived($trip);
        }

        try {
            DB::transaction(function () use ($trip) {
                $archiveConnection = 'td_archive';

                // Copy main trip record
                DB::connection($archiveConnection)->table('td_trips')->insert(
                    $trip->toArray()
                );

                // Copy air legs
                foreach ($trip->airLegs as $leg) {
                    DB::connection($archiveConnection)->table('td_air_legs')->insert(
                        $leg->toArray()
                    );

                    // Copy segments
                    foreach ($leg->segments as $segment) {
                        DB::connection($archiveConnection)->table('td_flight_segments')->insert(
                            $segment->toArray()
                        );
                    }
                }

                // Copy stays
                foreach ($trip->stays as $stay) {
                    DB::connection($archiveConnection)->table('td_stays')->insert(
                        $stay->toArray()
                    );
                }

                // Copy transfers
                foreach ($trip->transfers as $transfer) {
                    DB::connection($archiveConnection)->table('td_transfers')->insert(
                        $transfer->toArray()
                    );
                }

                // Copy trip locations
                foreach ($trip->tripLocations as $location) {
                    $locationData = $location->toArray();
                    unset($locationData['point']); // POINT column needs special handling
                    DB::connection($archiveConnection)->table('td_trip_locations')->insert(
                        $locationData
                    );
                }

                // Copy PDS share links
                foreach ($trip->pdsShareLinks as $link) {
                    DB::connection($archiveConnection)->table('td_pds_share_links')->insert(
                        $link->toArray()
                    );
                }

                // Delete from main database (force delete to bypass soft deletes)
                $trip->forceDelete();
            });

            Log::channel(config('travel_detail.logging.channel'))
                ->info('Moved trip to archive database', ['trip_id' => $trip->id]);

            return true;

        } catch (\Exception $e) {
            Log::channel(config('travel_detail.logging.channel'))
                ->error('Failed to move trip to archive database', [
                    'trip_id' => $trip->id,
                    'error' => $e->getMessage(),
                ]);
            return false;
        }
    }

    /**
     * Archive trips in batch
     */
    public function archiveBatch(
        int $limit = null,
        bool $moveToArchive = false,
        int $daysAfterCompletion = null
    ): array {
        $limit = $limit ?? config('travel_detail.archival.batch_size', 1000);

        $query = $this->getArchivableTripsQuery($daysAfterCompletion);
        $trips = $query->limit($limit)->get();

        $results = [
            'processed' => 0,
            'archived' => 0,
            'moved' => 0,
            'failed' => 0,
            'trip_ids' => [],
        ];

        foreach ($trips as $trip) {
            $results['processed']++;

            if ($moveToArchive) {
                if ($this->moveToArchiveDatabase($trip)) {
                    $results['moved']++;
                    $results['trip_ids'][] = $trip->id;
                } else {
                    $results['failed']++;
                }
            } else {
                if ($this->markAsArchived($trip)) {
                    $results['archived']++;
                    $results['trip_ids'][] = $trip->id;
                } else {
                    $results['failed']++;
                }
            }
        }

        return $results;
    }

    /**
     * Get archival statistics
     */
    public function getStatistics(): array
    {
        $days = config('travel_detail.archival.days_after_completion', 30);

        return [
            'total_count' => TdTrip::count(),
            'active_count' => TdTrip::where('status', 'active')->count(),
            'completed_count' => TdTrip::where('status', 'completed')->count(),
            'archived_count' => TdTrip::where('is_archived', true)->count(),
            'archivable_count' => $this->getArchivableTripsQuery($days)->count(),
            'archival_days' => $days,
            'batch_size' => config('travel_detail.archival.batch_size', 1000),
            'archive_db_enabled' => config('travel_detail.archival.use_separate_database', false),
        ];
    }

    /**
     * Restore a trip from archive (unmark as archived)
     */
    public function restoreFromArchive(TdTrip $trip): bool
    {
        if (!$trip->is_archived) {
            return true;
        }

        try {
            $trip->update(['is_archived' => false]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
