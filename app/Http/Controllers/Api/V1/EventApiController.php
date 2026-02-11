<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEventRequest;
use App\Http\Requests\Api\V1\UpdateEventRequest;
use App\Http\Resources\Api\V1\EventApiResource;
use App\Models\ApiClient;
use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\EventType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class EventApiController extends Controller
{
    /**
     * List events belonging to the authenticated API client.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $apiClient = $request->attributes->get('api_client');

        $events = CustomEvent::where('api_client_id', $apiClient->id)
            ->with(['eventTypes', 'countries'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 25));

        return EventApiResource::collection($events);
    }

    /**
     * Create a new event.
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        $apiClient = $request->attributes->get('api_client');
        $validated = $request->validated();

        // Sanitize HTML in description
        $description = isset($validated['description'])
            ? strip_tags($validated['description'], '<p><br><strong><em><ul><ol><li><a>')
            : null;

        // Resolve event types
        $eventTypes = EventType::whereIn('code', $validated['event_type_codes'])
            ->where('is_active', true)
            ->get();

        if ($eventTypes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No valid active event types found for the given codes.',
            ], 422);
        }

        // Resolve countries
        $countries = Country::whereIn('iso_code', $validated['country_codes'])->get();

        if ($countries->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No valid countries found for the given ISO codes.',
            ], 422);
        }

        // Determine review status
        $reviewStatus = $apiClient->auto_approve_events ? 'approved' : 'pending_review';
        $isActive = $apiClient->auto_approve_events;

        $event = CustomEvent::create([
            'uuid' => Str::uuid(),
            'title' => $validated['title'],
            'description' => $description,
            'priority' => $validated['priority'] ?? 'medium',
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'tags' => $validated['tags'] ?? null,
            'data_source' => 'api_client',
            'data_source_id' => $validated['external_id'] ?? null,
            'api_client_id' => $apiClient->id,
            'review_status' => $reviewStatus,
            'is_active' => $isActive,
            'event_type_id' => $eventTypes->first()->id,
            'event_type' => $eventTypes->first()->code,
            'country_id' => $countries->first()->id,
        ]);

        // Attach many-to-many relationships
        $event->eventTypes()->sync($eventTypes->pluck('id'));
        $event->countries()->sync($countries->pluck('id'));

        // Invalidate cache
        Cache::forget('gtm_active_events');

        $event->load(['eventTypes', 'countries']);

        return response()->json([
            'success' => true,
            'message' => $reviewStatus === 'approved'
                ? 'Event created and published successfully.'
                : 'Event created and submitted for review.',
            'data' => new EventApiResource($event),
        ], 201);
    }

    /**
     * Show a single event.
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $apiClient = $request->attributes->get('api_client');

        $event = CustomEvent::where('api_client_id', $apiClient->id)
            ->where('uuid', $uuid)
            ->with(['eventTypes', 'countries'])
            ->first();

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new EventApiResource($event),
        ]);
    }

    /**
     * Update an event.
     */
    public function update(UpdateEventRequest $request, string $uuid): JsonResponse
    {
        $apiClient = $request->attributes->get('api_client');
        $validated = $request->validated();

        $event = CustomEvent::where('api_client_id', $apiClient->id)
            ->where('uuid', $uuid)
            ->first();

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found.',
            ], 404);
        }

        // Build update data
        $updateData = [];

        if (isset($validated['title'])) {
            $updateData['title'] = $validated['title'];
        }
        if (array_key_exists('description', $validated)) {
            $updateData['description'] = $validated['description']
                ? strip_tags($validated['description'], '<p><br><strong><em><ul><ol><li><a>')
                : null;
        }
        if (isset($validated['priority'])) {
            $updateData['priority'] = $validated['priority'];
        }
        if (isset($validated['start_date'])) {
            $updateData['start_date'] = $validated['start_date'];
        }
        if (array_key_exists('end_date', $validated)) {
            $updateData['end_date'] = $validated['end_date'];
        }
        if (array_key_exists('latitude', $validated)) {
            $updateData['latitude'] = $validated['latitude'];
        }
        if (array_key_exists('longitude', $validated)) {
            $updateData['longitude'] = $validated['longitude'];
        }
        if (array_key_exists('tags', $validated)) {
            $updateData['tags'] = $validated['tags'];
        }
        if (isset($validated['external_id'])) {
            $updateData['data_source_id'] = $validated['external_id'];
        }

        if (!empty($updateData)) {
            $event->update($updateData);
        }

        // Update event types if provided
        if (isset($validated['event_type_codes'])) {
            $eventTypes = EventType::whereIn('code', $validated['event_type_codes'])
                ->where('is_active', true)
                ->get();

            if ($eventTypes->isNotEmpty()) {
                $event->eventTypes()->sync($eventTypes->pluck('id'));
                $event->update([
                    'event_type_id' => $eventTypes->first()->id,
                    'event_type' => $eventTypes->first()->code,
                ]);
            }
        }

        // Update countries if provided
        if (isset($validated['country_codes'])) {
            $countries = Country::whereIn('iso_code', $validated['country_codes'])->get();

            if ($countries->isNotEmpty()) {
                $event->countries()->sync($countries->pluck('id'));
                $event->update(['country_id' => $countries->first()->id]);
            }
        }

        // Invalidate cache
        Cache::forget('gtm_active_events');

        $event->load(['eventTypes', 'countries']);

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully.',
            'data' => new EventApiResource($event),
        ]);
    }

    /**
     * Delete (soft-delete) an event.
     */
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $apiClient = $request->attributes->get('api_client');

        $event = CustomEvent::where('api_client_id', $apiClient->id)
            ->where('uuid', $uuid)
            ->first();

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found.',
            ], 404);
        }

        $event->delete();

        // Invalidate cache
        Cache::forget('gtm_active_events');

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully.',
        ]);
    }
}
