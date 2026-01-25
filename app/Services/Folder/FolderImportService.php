<?php

namespace App\Services\Folder;

use App\Events\Folder\FolderImported;
use App\Models\Folder\Folder;
use App\Models\Folder\FolderCustomer;
use App\Models\Folder\FolderImportLog;
use App\Models\Folder\FolderItinerary;
use App\Models\Folder\FolderParticipant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FolderImportService
{
    protected TimelineBuilderService $timelineBuilder;

    protected AirportLookupService $airportLookup;

    public function __construct(
        TimelineBuilderService $timelineBuilder,
        AirportLookupService $airportLookup
    ) {
        $this->timelineBuilder = $timelineBuilder;
        $this->airportLookup = $airportLookup;
    }

    /**
     * Import folder data from external source.
     */
    public function import(FolderImportLog $importLog): bool
    {
        $importLog->markAsStarted();

        try {
            // Parse the source data
            $data = $this->parseSourceData($importLog->source_data, $importLog->import_source);

            // Validate the data structure
            if (! $this->validateData($data)) {
                throw new \Exception('Invalid data structure');
            }

            // Import within a transaction with retry for race conditions
            DB::beginTransaction();

            // Retry folder creation if there's a duplicate folder_number (race condition)
            $maxRetries = 3;
            $attempt = 0;
            $folder = null;

            while ($attempt < $maxRetries && ! $folder) {
                try {
                    $folder = $this->createFolder($data, $importLog->customer_id);
                } catch (\Illuminate\Database\QueryException $e) {
                    // Check if it's a duplicate folder_number error
                    if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'folder_number_unique')) {
                        $attempt++;
                        if ($attempt >= $maxRetries) {
                            throw $e;
                        }
                        // Small delay to avoid immediate retry
                        usleep(rand(10000, 50000)); // 10-50ms random delay

                        continue;
                    }
                    throw $e;
                }
            }
            $importLog->update(['folder_id' => $folder->id]);

            // Import customer data
            if (isset($data['customer'])) {
                $this->createFolderCustomer($folder, $data['customer']);
            }

            // Import participants
            if (isset($data['participants'])) {
                foreach ($data['participants'] as $participantData) {
                    $this->createParticipant($folder, $participantData);
                }
            }

            // Import itineraries
            $recordsImported = 0;
            if (isset($data['itineraries'])) {
                foreach ($data['itineraries'] as $itineraryData) {
                    $itinerary = $this->createItinerary($folder, $itineraryData);

                    // Import services for this itinerary
                    $this->importServices($itinerary, $itineraryData);

                    $recordsImported++;
                }
            }

            // Update folder statistics
            $folder->updateStatistics();

            // Broadcast real-time event to customer
            $wasUpdated = $folder->wasRecentlyCreated === false;
            Log::info('Broadcasting FolderImported event', [
                'folder_id' => $folder->id,
                'customer_id' => $folder->customer_id,
                'was_updated' => $wasUpdated,
                'channel' => 'customer.' . $folder->customer_id,
            ]);
            try {
                broadcast(new FolderImported($folder, $wasUpdated));
                Log::info('FolderImported broadcast sent successfully');
            } catch (\Exception $e) {
                Log::error('FolderImported broadcast failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            // Rebuild timeline in the background
            $this->timelineBuilder->rebuildForFolder($folder);

            DB::commit();

            $importLog->markAsCompleted($recordsImported);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Folder import failed', [
                'import_log_id' => $importLog->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $importLog->markAsFailed($e->getMessage());

            return false;
        }
    }

    /**
     * Parse source data based on import source.
     */
    protected function parseSourceData(array $sourceData, string $importSource): array
    {
        // Handle different import sources
        switch ($importSource) {
            case 'api':
                return $this->parseApiData($sourceData);
            case 'file':
                return $this->parseFileData($sourceData);
            case 'manual':
                return $sourceData;
            default:
                throw new \Exception("Unknown import source: {$importSource}");
        }
    }

    /**
     * Parse API data.
     */
    protected function parseApiData(array $data): array
    {
        // Transform API-specific structure to internal format
        return $data;
    }

    /**
     * Parse file data.
     */
    protected function parseFileData(array $data): array
    {
        // Transform file-specific structure to internal format
        return $data;
    }

    /**
     * Validate data structure.
     */
    protected function validateData(array $data): bool
    {
        // Check required fields
        return isset($data['folder']);
    }

    /**
     * Map salutation to internal format.
     */
    protected function mapSalutation(?string $salutation): ?string
    {
        if (! $salutation) {
            return null;
        }

        $mapping = [
            'Herr' => 'mr',
            'Mr' => 'mr',
            'Mr.' => 'mr',
            'Mister' => 'mr',
            'Frau' => 'mrs',
            'Mrs' => 'mrs',
            'Mrs.' => 'mrs',
            'Ms' => 'mrs',
            'Ms.' => 'mrs',
            'Miss' => 'mrs',
            'Divers' => 'diverse',
            'Diverse' => 'diverse',
            'Other' => 'diverse',
        ];

        return $mapping[$salutation] ?? strtolower($salutation);
    }

    /**
     * Create folder from data.
     */
    protected function createFolder(array $data, string $customerId): Folder
    {
        $folderData = $data['folder'];
        $folderNumber = $folderData['folder_number'] ?? Folder::generateFolderNumber((int) $customerId);

        // First, check if an active folder with the same folder_number exists
        $existingFolder = Folder::withoutGlobalScope('customer')
            ->where('folder_number', $folderNumber)
            ->where('customer_id', $customerId)
            ->first();

        // If active folder exists, update it with new data
        if ($existingFolder) {
            $existingFolder->wasRecentlyCreated = false;

            // Delete existing relationships to start fresh
            $existingFolder->folderCustomer()?->forceDelete();
            $existingFolder->participants()->each(fn ($p) => $p->forceDelete());
            $existingFolder->itineraries()->each(function ($itinerary) {
                $itinerary->hotelServices()->each(fn ($h) => $h->forceDelete());
                $itinerary->flightServices()->each(function ($flight) {
                    $flight->segments()->each(fn ($s) => $s->forceDelete());
                    $flight->forceDelete();
                });
                $itinerary->shipServices()->each(fn ($s) => $s->forceDelete());
                $itinerary->carRentalServices()->each(fn ($c) => $c->forceDelete());
                $itinerary->forceDelete();
            });

            $existingFolder->update([
                'folder_name' => $folderData['folder_name'] ?? null,
                'travel_start_date' => $folderData['travel_start_date'] ?? null,
                'travel_end_date' => $folderData['travel_end_date'] ?? null,
                'primary_destination' => $folderData['primary_destination'] ?? null,
                'status' => $folderData['status'] ?? 'draft',
                'travel_type' => $folderData['travel_type'] ?? 'leisure',
                'agent_name' => $folderData['agent_name'] ?? null,
                'notes' => $folderData['notes'] ?? null,
                'currency' => $folderData['currency'] ?? 'EUR',
                'custom_field_1_label' => $folderData['custom_field_1_label'] ?? null,
                'custom_field_1_value' => $folderData['custom_field_1_value'] ?? null,
                'custom_field_2_label' => $folderData['custom_field_2_label'] ?? null,
                'custom_field_2_value' => $folderData['custom_field_2_value'] ?? null,
                'custom_field_3_label' => $folderData['custom_field_3_label'] ?? null,
                'custom_field_3_value' => $folderData['custom_field_3_value'] ?? null,
                'custom_field_4_label' => $folderData['custom_field_4_label'] ?? null,
                'custom_field_4_value' => $folderData['custom_field_4_value'] ?? null,
                'custom_field_5_label' => $folderData['custom_field_5_label'] ?? null,
                'custom_field_5_value' => $folderData['custom_field_5_value'] ?? null,
            ]);

            return $existingFolder;
        }

        // Check if a soft-deleted folder with the same folder_number exists
        $trashedFolder = Folder::withoutGlobalScope('customer')
            ->onlyTrashed()
            ->where('folder_number', $folderNumber)
            ->where('customer_id', $customerId)
            ->first();

        // If soft-deleted folder exists, restore and update it
        if ($trashedFolder) {
            $trashedFolder->restore();
            $trashedFolder->wasRecentlyCreated = false;

            // Delete existing relationships to start fresh
            $trashedFolder->folderCustomer()?->forceDelete();
            $trashedFolder->participants()->each(fn ($p) => $p->forceDelete());
            $trashedFolder->itineraries()->each(function ($itinerary) {
                $itinerary->hotelServices()->each(fn ($h) => $h->forceDelete());
                $itinerary->flightServices()->each(function ($flight) {
                    $flight->segments()->each(fn ($s) => $s->forceDelete());
                    $flight->forceDelete();
                });
                $itinerary->shipServices()->each(fn ($s) => $s->forceDelete());
                $itinerary->carRentalServices()->each(fn ($c) => $c->forceDelete());
                $itinerary->forceDelete();
            });

            $trashedFolder->update([
                'folder_name' => $folderData['folder_name'] ?? null,
                'travel_start_date' => $folderData['travel_start_date'] ?? null,
                'travel_end_date' => $folderData['travel_end_date'] ?? null,
                'primary_destination' => $folderData['primary_destination'] ?? null,
                'status' => $folderData['status'] ?? 'draft',
                'travel_type' => $folderData['travel_type'] ?? 'leisure',
                'agent_name' => $folderData['agent_name'] ?? null,
                'notes' => $folderData['notes'] ?? null,
                'currency' => $folderData['currency'] ?? 'EUR',
                'custom_field_1_label' => $folderData['custom_field_1_label'] ?? null,
                'custom_field_1_value' => $folderData['custom_field_1_value'] ?? null,
                'custom_field_2_label' => $folderData['custom_field_2_label'] ?? null,
                'custom_field_2_value' => $folderData['custom_field_2_value'] ?? null,
                'custom_field_3_label' => $folderData['custom_field_3_label'] ?? null,
                'custom_field_3_value' => $folderData['custom_field_3_value'] ?? null,
                'custom_field_4_label' => $folderData['custom_field_4_label'] ?? null,
                'custom_field_4_value' => $folderData['custom_field_4_value'] ?? null,
                'custom_field_5_label' => $folderData['custom_field_5_label'] ?? null,
                'custom_field_5_value' => $folderData['custom_field_5_value'] ?? null,
            ]);

            return $trashedFolder;
        }

        // Create new folder if none exists
        return Folder::withoutGlobalScope('customer')->create([
            'customer_id' => $customerId,
            'folder_number' => $folderNumber,
            'folder_name' => $folderData['folder_name'] ?? null,
            'travel_start_date' => $folderData['travel_start_date'] ?? null,
            'travel_end_date' => $folderData['travel_end_date'] ?? null,
            'primary_destination' => $folderData['primary_destination'] ?? null,
            'status' => $folderData['status'] ?? 'draft',
            'travel_type' => $folderData['travel_type'] ?? 'leisure',
            'agent_name' => $folderData['agent_name'] ?? null,
            'notes' => $folderData['notes'] ?? null,
            'currency' => $folderData['currency'] ?? 'EUR',
            'custom_field_1_label' => $folderData['custom_field_1_label'] ?? null,
            'custom_field_1_value' => $folderData['custom_field_1_value'] ?? null,
            'custom_field_2_label' => $folderData['custom_field_2_label'] ?? null,
            'custom_field_2_value' => $folderData['custom_field_2_value'] ?? null,
            'custom_field_3_label' => $folderData['custom_field_3_label'] ?? null,
            'custom_field_3_value' => $folderData['custom_field_3_value'] ?? null,
            'custom_field_4_label' => $folderData['custom_field_4_label'] ?? null,
            'custom_field_4_value' => $folderData['custom_field_4_value'] ?? null,
            'custom_field_5_label' => $folderData['custom_field_5_label'] ?? null,
            'custom_field_5_value' => $folderData['custom_field_5_value'] ?? null,
        ]);
    }

    /**
     * Create folder customer from data.
     */
    protected function createFolderCustomer(Folder $folder, array $data): FolderCustomer
    {
        return FolderCustomer::withoutGlobalScope('customer')->create([
            'folder_id' => $folder->id,
            'customer_id' => $folder->customer_id,
            'salutation' => $this->mapSalutation($data['salutation'] ?? null),
            'title' => $data['title'] ?? null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'street' => $data['street'] ?? null,
            'house_number' => $data['house_number'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'city' => $data['city'] ?? null,
            'country_code' => $data['country_code'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'nationality' => $data['nationality'] ?? null,
        ]);
    }

    /**
     * Create participant from data.
     */
    protected function createParticipant(Folder $folder, array $data): FolderParticipant
    {
        return FolderParticipant::withoutGlobalScope('customer')->create([
            'folder_id' => $folder->id,
            'customer_id' => $folder->customer_id,
            'salutation' => $this->mapSalutation($data['salutation'] ?? null),
            'title' => $data['title'] ?? null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'birth_date' => $data['birth_date'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'passport_number' => $data['passport_number'] ?? null,
            'passport_expiry_date' => $data['passport_expiry_date'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_main_contact' => $data['is_main_contact'] ?? false,
            'participant_type' => $data['participant_type'] ?? 'adult',
        ]);
    }

    /**
     * Create itinerary from data.
     */
    protected function createItinerary(Folder $folder, array $data): FolderItinerary
    {
        return FolderItinerary::withoutGlobalScope('customer')->create([
            'folder_id' => $folder->id,
            'customer_id' => $folder->customer_id,
            'booking_reference' => $data['booking_reference'] ?? null,
            'itinerary_name' => $data['itinerary_name'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'provider_name' => $data['provider_name'] ?? null,
            'provider_reference' => $data['provider_reference'] ?? null,
            'currency' => $data['currency'] ?? 'EUR',
        ]);
    }

    /**
     * Import services for itinerary.
     */
    protected function importServices(FolderItinerary $itinerary, array $data): void
    {
        // Import flight services
        if (isset($data['flights'])) {
            foreach ($data['flights'] as $flightData) {
                $this->importFlightService($itinerary, $flightData);
            }
        }

        // Import hotel services
        if (isset($data['hotels'])) {
            foreach ($data['hotels'] as $hotelData) {
                $this->importHotelService($itinerary, $hotelData);
            }
        }

        // Import ship services
        if (isset($data['ships'])) {
            foreach ($data['ships'] as $shipData) {
                $this->importShipService($itinerary, $shipData);
            }
        }

        // Import car rental services
        if (isset($data['car_rentals'])) {
            foreach ($data['car_rentals'] as $carData) {
                $this->importCarRentalService($itinerary, $carData);
            }
        }
    }

    /**
     * Import flight service.
     */
    protected function importFlightService(FolderItinerary $itinerary, array $data): void
    {
        $flightService = \App\Models\Folder\FolderFlightService::withoutGlobalScope('customer')->create([
            'itinerary_id' => $itinerary->id,
            'folder_id' => $itinerary->folder_id,
            'customer_id' => $itinerary->customer_id,
            'booking_reference' => $data['booking_reference'] ?? null,
            'service_type' => $data['service_type'] ?? 'outbound',
            'airline_pnr' => $data['airline_pnr'] ?? null,
            'ticket_numbers' => $data['ticket_numbers'] ?? null,
            'total_amount' => $data['total_amount'] ?? null,
            'currency' => $data['currency'] ?? 'EUR',
            'status' => $data['status'] ?? 'pending',
        ]);

        // Import flight segments
        if (isset($data['segments']) && is_array($data['segments'])) {
            foreach ($data['segments'] as $segmentData) {
                // Enrich segment data with airport and country IDs
                $enrichedSegmentData = $this->airportLookup->enrichSegmentData($segmentData);

                \App\Models\Folder\FolderFlightSegment::withoutGlobalScope('customer')->create([
                    'flight_service_id' => $flightService->id,
                    'folder_id' => $itinerary->folder_id,
                    'customer_id' => $itinerary->customer_id,
                    'segment_number' => $enrichedSegmentData['segment_number'] ?? 1,
                    'departure_airport_code' => $enrichedSegmentData['departure_airport_code'],
                    'departure_airport_id' => $enrichedSegmentData['departure_airport_id'] ?? null,
                    'departure_country_id' => $enrichedSegmentData['departure_country_id'] ?? null,
                    'departure_lat' => $enrichedSegmentData['departure_lat'] ?? null,
                    'departure_lng' => $enrichedSegmentData['departure_lng'] ?? null,
                    'departure_country_code' => $enrichedSegmentData['departure_country_code'] ?? null,
                    'departure_time' => $enrichedSegmentData['departure_time'],
                    'departure_terminal' => $enrichedSegmentData['departure_terminal'] ?? null,
                    'arrival_airport_code' => $enrichedSegmentData['arrival_airport_code'],
                    'arrival_airport_id' => $enrichedSegmentData['arrival_airport_id'] ?? null,
                    'arrival_country_id' => $enrichedSegmentData['arrival_country_id'] ?? null,
                    'arrival_lat' => $enrichedSegmentData['arrival_lat'] ?? null,
                    'arrival_lng' => $enrichedSegmentData['arrival_lng'] ?? null,
                    'arrival_country_code' => $enrichedSegmentData['arrival_country_code'] ?? null,
                    'arrival_time' => $enrichedSegmentData['arrival_time'],
                    'arrival_terminal' => $enrichedSegmentData['arrival_terminal'] ?? null,
                    'airline_code' => $enrichedSegmentData['airline_code'] ?? null,
                    'flight_number' => $enrichedSegmentData['flight_number'] ?? null,
                    'aircraft_type' => $enrichedSegmentData['aircraft_type'] ?? null,
                    'duration_minutes' => $enrichedSegmentData['duration_minutes'] ?? null,
                    'booking_class' => $enrichedSegmentData['booking_class'] ?? null,
                    'cabin_class' => $enrichedSegmentData['cabin_class'] ?? 'economy',
                ]);
            }

            // Update flight service with first/last segment times
            if (count($data['segments']) > 0) {
                $firstSegment = $data['segments'][0];
                $lastSegment = end($data['segments']);

                $flightService->update([
                    'departure_time' => $firstSegment['departure_time'],
                    'origin_airport_code' => $firstSegment['departure_airport_code'],
                    'origin_country_code' => $firstSegment['departure_country_code'] ?? null,
                    'arrival_time' => $lastSegment['arrival_time'],
                    'destination_airport_code' => $lastSegment['arrival_airport_code'],
                    'destination_country_code' => $lastSegment['arrival_country_code'] ?? null,
                ]);
            }
        }
    }

    /**
     * Import hotel service.
     */
    protected function importHotelService(FolderItinerary $itinerary, array $data): void
    {
        $hotelService = \App\Models\Folder\FolderHotelService::withoutGlobalScope('customer')->create([
            'itinerary_id' => $itinerary->id,
            'folder_id' => $itinerary->folder_id,
            'customer_id' => $itinerary->customer_id,
            'hotel_name' => $data['hotel_name'],
            'hotel_code' => $data['hotel_code'] ?? null,
            'hotel_code_type' => $data['hotel_code_type'] ?? null,
            'street' => $data['street'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'city' => $data['city'] ?? null,
            'country_code' => $data['country_code'] ?? null,
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
            'check_in_date' => $data['check_in_date'],
            'check_out_date' => $data['check_out_date'],
            'nights' => $data['nights'] ?? null,
            'room_type' => $data['room_type'] ?? null,
            'room_count' => $data['room_count'] ?? 1,
            'board_type' => $data['board_type'] ?? null,
            'booking_reference' => $data['booking_reference'] ?? null,
            'total_amount' => $data['total_amount'] ?? null,
            'currency' => $data['currency'] ?? 'EUR',
            'status' => $data['status'] ?? 'pending',
            'notes' => $data['notes'] ?? null,
        ]);

        // Note: POINT column is automatically set by the model's booted() method
    }

    /**
     * Import ship service.
     */
    protected function importShipService(FolderItinerary $itinerary, array $data): void
    {
        // Implementation would create ship service
        // This is a placeholder for the actual implementation
    }

    /**
     * Import car rental service.
     */
    protected function importCarRentalService(FolderItinerary $itinerary, array $data): void
    {
        // Implementation would create car rental service
        // This is a placeholder for the actual implementation
    }
}
