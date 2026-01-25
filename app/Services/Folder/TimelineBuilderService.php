<?php

namespace App\Services\Folder;

use App\Models\Folder\Folder;
use App\Models\Folder\FolderItinerary;
use App\Models\Folder\FolderTimelineLocation;

class TimelineBuilderService
{
    /**
     * Rebuild timeline locations for a folder.
     */
    public function rebuildForFolder(Folder $folder): int
    {
        // Delete existing timeline locations for this folder
        FolderTimelineLocation::withoutGlobalScope('customer')
            ->where('folder_id', $folder->id)
            ->delete();

        $locationsCreated = 0;

        // Process each itinerary
        foreach ($folder->itineraries as $itinerary) {
            $locationsCreated += $this->buildForItinerary($itinerary);
        }

        return $locationsCreated;
    }

    /**
     * Build timeline locations for an itinerary.
     */
    public function buildForItinerary(FolderItinerary $itinerary): int
    {
        // Delete existing timeline locations for this itinerary
        FolderTimelineLocation::withoutGlobalScope('customer')
            ->where('itinerary_id', $itinerary->id)
            ->delete();

        $locationsCreated = 0;

        // Get participant data for this itinerary
        $participantIds = $itinerary->participants->pluck('id')->toArray();
        $participantNationalities = $itinerary->participants->pluck('nationality')->unique()->filter()->values()->toArray();

        // Process flight segments
        foreach ($itinerary->flightServices as $flightService) {
            foreach ($flightService->segments as $segment) {
                // Departure location
                if ($segment->departure_lat && $segment->departure_lng) {
                    $this->createTimelineLocation([
                        'folder_id' => $itinerary->folder_id,
                        'itinerary_id' => $itinerary->id,
                        'customer_id' => $itinerary->customer_id,
                        'location_type' => 'flight_departure',
                        'source_type' => 'flight_segment',
                        'source_id' => $segment->id,
                        'lat' => $segment->departure_lat,
                        'lng' => $segment->departure_lng,
                        'location_code' => $segment->departure_airport_code,
                        'location_name' => $segment->departure_airport_code,
                        'country_code' => $segment->departure_country_code,
                        'start_time' => $segment->departure_time,
                        'end_time' => $segment->departure_time,
                        'participant_ids' => $participantIds,
                        'participant_nationalities' => $participantNationalities,
                    ]);
                    $locationsCreated++;
                }

                // Arrival location
                if ($segment->arrival_lat && $segment->arrival_lng) {
                    $this->createTimelineLocation([
                        'folder_id' => $itinerary->folder_id,
                        'itinerary_id' => $itinerary->id,
                        'customer_id' => $itinerary->customer_id,
                        'location_type' => 'flight_arrival',
                        'source_type' => 'flight_segment',
                        'source_id' => $segment->id,
                        'lat' => $segment->arrival_lat,
                        'lng' => $segment->arrival_lng,
                        'location_code' => $segment->arrival_airport_code,
                        'location_name' => $segment->arrival_airport_code,
                        'country_code' => $segment->arrival_country_code,
                        'start_time' => $segment->arrival_time,
                        'end_time' => $segment->arrival_time,
                        'participant_ids' => $participantIds,
                        'participant_nationalities' => $participantNationalities,
                    ]);
                    $locationsCreated++;
                }
            }
        }

        // Process hotels
        foreach ($itinerary->hotelServices as $hotel) {
            if ($hotel->lat && $hotel->lng) {
                $this->createTimelineLocation([
                    'folder_id' => $itinerary->folder_id,
                    'itinerary_id' => $itinerary->id,
                    'customer_id' => $itinerary->customer_id,
                    'location_type' => 'hotel',
                    'source_type' => 'hotel_service',
                    'source_id' => $hotel->id,
                    'lat' => $hotel->lat,
                    'lng' => $hotel->lng,
                    'location_code' => $hotel->hotel_code,
                    'location_name' => $hotel->hotel_name,
                    'country_code' => $hotel->country_code,
                    'start_time' => $hotel->check_in_date->startOfDay(),
                    'end_time' => $hotel->check_out_date->endOfDay(),
                    'participant_ids' => $participantIds,
                    'participant_nationalities' => $participantNationalities,
                ]);
                $locationsCreated++;
            }
        }

        // Process cruise embarkation/disembarkation
        foreach ($itinerary->shipServices as $ship) {
            // Embarkation
            if ($ship->embarkation_lat && $ship->embarkation_lng) {
                $this->createTimelineLocation([
                    'folder_id' => $itinerary->folder_id,
                    'itinerary_id' => $itinerary->id,
                    'customer_id' => $itinerary->customer_id,
                    'location_type' => 'cruise_embark',
                    'source_type' => 'ship_service',
                    'source_id' => $ship->id,
                    'lat' => $ship->embarkation_lat,
                    'lng' => $ship->embarkation_lng,
                    'location_code' => null,
                    'location_name' => $ship->embarkation_port,
                    'country_code' => $ship->embarkation_country_code,
                    'start_time' => $ship->embarkation_date->startOfDay(),
                    'end_time' => $ship->embarkation_date->endOfDay(),
                    'participant_ids' => $participantIds,
                    'participant_nationalities' => $participantNationalities,
                ]);
                $locationsCreated++;
            }

            // Disembarkation
            if ($ship->disembarkation_lat && $ship->disembarkation_lng) {
                $this->createTimelineLocation([
                    'folder_id' => $itinerary->folder_id,
                    'itinerary_id' => $itinerary->id,
                    'customer_id' => $itinerary->customer_id,
                    'location_type' => 'cruise_disembark',
                    'source_type' => 'ship_service',
                    'source_id' => $ship->id,
                    'lat' => $ship->disembarkation_lat,
                    'lng' => $ship->disembarkation_lng,
                    'location_code' => null,
                    'location_name' => $ship->disembarkation_port,
                    'country_code' => $ship->disembarkation_country_code,
                    'start_time' => $ship->disembarkation_date->startOfDay(),
                    'end_time' => $ship->disembarkation_date->endOfDay(),
                    'participant_ids' => $participantIds,
                    'participant_nationalities' => $participantNationalities,
                ]);
                $locationsCreated++;
            }

            // Port calls
            if ($ship->port_calls && is_array($ship->port_calls)) {
                foreach ($ship->port_calls as $port) {
                    if (isset($port['lat'], $port['lng'], $port['date'])) {
                        $this->createTimelineLocation([
                            'folder_id' => $itinerary->folder_id,
                            'itinerary_id' => $itinerary->id,
                            'customer_id' => $itinerary->customer_id,
                            'location_type' => 'cruise_port',
                            'source_type' => 'ship_service',
                            'source_id' => $ship->id,
                            'lat' => $port['lat'],
                            'lng' => $port['lng'],
                            'location_code' => $port['code'] ?? null,
                            'location_name' => $port['name'] ?? null,
                            'country_code' => $port['country_code'] ?? null,
                            'start_time' => $port['date'].' 00:00:00',
                            'end_time' => $port['date'].' 23:59:59',
                            'participant_ids' => $participantIds,
                            'participant_nationalities' => $participantNationalities,
                        ]);
                        $locationsCreated++;
                    }
                }
            }
        }

        // Process car rentals
        foreach ($itinerary->carRentalServices as $car) {
            // Pickup location
            if ($car->pickup_lat && $car->pickup_lng) {
                $this->createTimelineLocation([
                    'folder_id' => $itinerary->folder_id,
                    'itinerary_id' => $itinerary->id,
                    'customer_id' => $itinerary->customer_id,
                    'location_type' => 'car_pickup',
                    'source_type' => 'car_rental_service',
                    'source_id' => $car->id,
                    'lat' => $car->pickup_lat,
                    'lng' => $car->pickup_lng,
                    'location_code' => null,
                    'location_name' => $car->pickup_location,
                    'country_code' => $car->pickup_country_code,
                    'start_time' => $car->pickup_datetime,
                    'end_time' => $car->pickup_datetime,
                    'participant_ids' => $participantIds,
                    'participant_nationalities' => $participantNationalities,
                ]);
                $locationsCreated++;
            }

            // Return location
            if ($car->return_lat && $car->return_lng) {
                $this->createTimelineLocation([
                    'folder_id' => $itinerary->folder_id,
                    'itinerary_id' => $itinerary->id,
                    'customer_id' => $itinerary->customer_id,
                    'location_type' => 'car_return',
                    'source_type' => 'car_rental_service',
                    'source_id' => $car->id,
                    'lat' => $car->return_lat,
                    'lng' => $car->return_lng,
                    'location_code' => null,
                    'location_name' => $car->return_location,
                    'country_code' => $car->return_country_code,
                    'start_time' => $car->return_datetime,
                    'end_time' => $car->return_datetime,
                    'participant_ids' => $participantIds,
                    'participant_nationalities' => $participantNationalities,
                ]);
                $locationsCreated++;
            }
        }

        return $locationsCreated;
    }

    /**
     * Create a timeline location.
     */
    protected function createTimelineLocation(array $data): void
    {
        FolderTimelineLocation::withoutGlobalScope('customer')->create($data);
    }
}
