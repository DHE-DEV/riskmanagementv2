<?php

namespace App\Http\Controllers;

use App\Models\CustomEvent;
use App\Models\EventClick;
use App\Models\EventType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomEventController extends Controller
{
    /**
     * Get custom events for dashboard
     */
    public function getDashboardEvents(): JsonResponse
    {
        try {
            $events = CustomEvent::visible()
                ->where('archived', false)
                ->where('priority', '!=', 'critical')
                ->where(function ($query) {
                    $query->whereNull('end_date')
                          ->orWhere('end_date', '>=', now()->startOfDay());
                })
                ->where(function ($query) {
                    // Event hat entweder direkte Koordinaten ODER zugeordnete Länder
                    $query->where(function ($q) {
                        $q->whereNotNull('latitude')
                          ->whereNotNull('longitude');
                    })
                    ->orWhereHas('countries');
                })
                ->where(function ($query) {
                    $query->whereHas('eventType', function ($subQuery) {
                        $subQuery->where('is_active', true);
                    })
                    ->orWhereNull('event_type_id');
                })
                ->with(['creator', 'updater', 'country', 'eventType', 'eventTypes', 'countries'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($event) {
                    // Bei Many-to-Many: Verwende Icon vom ersten EventType falls verfügbar
                    $eventTypeIcon = null;
                    if ($event->eventTypes->isNotEmpty()) {
                        $eventTypeIcon = $event->eventTypes->first()->icon;
                    } elseif ($event->eventType) {
                        // Fallback auf legacy single eventType
                        $eventTypeIcon = $event->eventType->icon;
                    }

                    // Länder mit ihren individuellen Koordinaten sammeln
                    $countriesData = $event->countries->map(function ($country) use ($event) {
                        $lat = $country->pivot->use_default_coordinates ? $country->lat : $country->pivot->latitude;
                        $lng = $country->pivot->use_default_coordinates ? $country->lng : $country->pivot->longitude;

                        // Falls keine Koordinaten vorhanden sind, verwende die Event-Koordinaten als Fallback
                        if (!$lat && !$lng && $event->latitude && $event->longitude) {
                            $lat = $event->latitude;
                            $lng = $event->longitude;
                        }

                        return [
                            'id' => $country->id,
                            'name' => $country->getName('de'),
                            'iso_code' => $country->iso_code,
                            'latitude' => $lat ? (float) $lat : null,
                            'longitude' => $lng ? (float) $lng : null,
                            'location_note' => $country->pivot->location_note,
                            'use_default_coordinates' => $country->pivot->use_default_coordinates,
                        ];
                    })->toArray();

                    return [
                        'id' => $event->id,
                        'title' => $event->title,
                        'description' => $event->description,
                        'event_type' => $event->getCorrectEventType(),
                        'event_type_id' => $event->event_type_id,
                        'event_type_name' => $event->eventType?->name ?? $event->getCorrectEventType(),
                        'event_types' => $event->eventTypes->pluck('name')->toArray(),
                        'event_types_codes' => $event->eventTypes->pluck('code')->toArray(),
                        'event_type_ids' => $event->eventTypes->pluck('id')->toArray(),
                        'country' => $event->country?->getName('de') ?? 'Unbekannt',
                        'country_relation' => $event->country,
                        'countries' => $countriesData, // Neue Länder-Daten mit individuellen Koordinaten
                        'latitude' => $event->latitude,
                        'longitude' => $event->longitude,
                        'marker_color' => $this->getPriorityColor($event->priority),
                        'marker_icon' => $eventTypeIcon ?? $event->marker_icon,
                        'icon_color' => $event->icon_color,
                        'marker_size' => $event->marker_size,
                        'popup_content' => $event->popup_content,
                        'start_date' => optional($event->start_date)?->toDateTimeString(),
                        'end_date' => $event->end_date,
                        'priority' => $event->priority,
                        'severity' => $event->severity,
                        'category' => $event->category,
                        'tags' => $event->tags,
                        'is_active' => $event->is_active,
                        'archived' => $event->archived,
                        'archived_at' => $event->archived_at,
                        'created_at' => $event->created_at,
                        'updated_at' => $event->updated_at,
                        'creator_name' => $event->creator?->name,
                        'updater_name' => $event->updater?->name,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'events' => $events,
                    'total_count' => $events->count(),
                    'active_count' => $events->where('is_active', true)->count(),
                ],
                'message' => 'Custom events loaded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load custom events: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get custom events for map display
     */
    public function getMapEvents(): JsonResponse
    {
        try {
            $events = CustomEvent::visible()
                ->where('archived', false)
                ->where('priority', '!=', 'critical')
                ->where(function ($query) {
                    $query->whereNull('end_date')
                          ->orWhere('end_date', '>=', now()->startOfDay());
                })
                ->where(function ($query) {
                    // Event hat entweder direkte Koordinaten ODER zugeordnete Länder
                    $query->where(function ($q) {
                        $q->whereNotNull('latitude')
                          ->whereNotNull('longitude');
                    })
                    ->orWhereHas('countries');
                })
                ->where(function ($query) {
                    $query->whereHas('eventType', function ($subQuery) {
                        $subQuery->where('is_active', true);
                    })
                    ->orWhereNull('event_type_id');
                })
                ->with(['country', 'eventType', 'eventTypes', 'countries'])
                ->get()
                ->map(function ($event) {
                    // Bei Many-to-Many: Verwende Icon vom ersten EventType falls verfügbar
                    $eventTypeIcon = null;
                    if ($event->eventTypes->isNotEmpty()) {
                        $eventTypeIcon = $event->eventTypes->first()->icon;
                    } elseif ($event->eventType) {
                        // Fallback auf legacy single eventType
                        $eventTypeIcon = $event->eventType->icon;
                    }

                    // Länder mit ihren individuellen Koordinaten sammeln
                    $countriesData = $event->countries->map(function ($country) use ($event) {
                        $lat = $country->pivot->use_default_coordinates ? $country->lat : $country->pivot->latitude;
                        $lng = $country->pivot->use_default_coordinates ? $country->lng : $country->pivot->longitude;

                        // Falls keine Koordinaten vorhanden sind, verwende die Event-Koordinaten als Fallback
                        if (!$lat && !$lng && $event->latitude && $event->longitude) {
                            $lat = $event->latitude;
                            $lng = $event->longitude;
                        }

                        return [
                            'id' => $country->id,
                            'name' => $country->getName('de'),
                            'iso_code' => $country->iso_code,
                            'latitude' => $lat ? (float) $lat : null,
                            'longitude' => $lng ? (float) $lng : null,
                            'location_note' => $country->pivot->location_note,
                            'use_default_coordinates' => $country->pivot->use_default_coordinates,
                        ];
                    })->toArray();

                    return [
                        'id' => $event->id,
                        'title' => $event->title,
                        'description' => $event->description,
                        'event_type' => $event->getCorrectEventType(),
                        'event_type_id' => $event->event_type_id,
                        'event_type_name' => $event->eventType?->name ?? $event->getCorrectEventType(),
                        'event_types' => $event->eventTypes->pluck('name')->toArray(),
                        'event_types_codes' => $event->eventTypes->pluck('code')->toArray(),
                        'event_type_ids' => $event->eventTypes->pluck('id')->toArray(),
                        'countries' => $countriesData, // Länder-Daten mit Koordinaten
                        'latitude' => $event->latitude,
                        'longitude' => $event->longitude,
                        'marker_color' => $this->getPriorityColor($event->priority),
                        'marker_icon' => $eventTypeIcon ?? $event->marker_icon,
                        'icon_color' => $event->icon_color,
                        'marker_size' => $event->marker_size,
                        'popup_content' => $event->popup_content,
                        'country' => $event->country?->getName('de') ?? 'Unbekannt',
                        'priority' => $event->priority,
                        'severity' => $event->severity,
                        'category' => $event->category,
                        'tags' => $event->tags,
                        'archived' => $event->archived,
                        'archived_at' => $event->archived_at,
                        'start_date' => optional($event->start_date)?->toDateTimeString(),
                        'end_date' => $event->end_date,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $events,
                'message' => 'Map events loaded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load map events: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get custom events statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            // Query for events with active event types or no event type
            $activeEventTypeQuery = CustomEvent::where(function ($query) {
                $query->whereHas('eventType', function ($subQuery) {
                    $subQuery->where('is_active', true);
                })
                ->orWhereNull('event_type_id');
            });

            $stats = [
                'total_events' => $activeEventTypeQuery->count(),
                'active_events' => (clone $activeEventTypeQuery)->where('is_active', true)->count(),
                'events_by_type' => CustomEvent::selectRaw('event_type, count(*) as count')
                    ->where(function ($query) {
                        $query->whereHas('eventType', function ($subQuery) {
                            $subQuery->where('is_active', true);
                        })
                        ->orWhereNull('event_type_id');
                    })
                    ->groupBy('event_type')
                    ->pluck('count', 'event_type')
                    ->toArray(),
                'events_by_priority' => CustomEvent::selectRaw('priority, count(*) as count')
                    ->where(function ($query) {
                        $query->whereHas('eventType', function ($subQuery) {
                            $subQuery->where('is_active', true);
                        })
                        ->orWhereNull('event_type_id');
                    })
                    ->groupBy('priority')
                    ->pluck('count', 'priority')
                    ->toArray(),
                'events_by_severity' => CustomEvent::selectRaw('severity, count(*) as count')
                    ->where(function ($query) {
                        $query->whereHas('eventType', function ($subQuery) {
                            $subQuery->where('is_active', true);
                        })
                        ->orWhereNull('event_type_id');
                    })
                    ->groupBy('severity')
                    ->pluck('count', 'severity')
                    ->toArray(),
                'recent_events' => CustomEvent::where(function ($query) {
                        $query->whereHas('eventType', function ($subQuery) {
                            $subQuery->where('is_active', true);
                        })
                        ->orWhereNull('event_type_id');
                    })
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistics loaded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all event types for filtering
     */
    public function getEventTypes(): JsonResponse
    {
        try {
            $eventTypes = EventType::where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(function ($eventType) {
                    return [
                        'id' => $eventType->id,
                        'code' => $eventType->code,
                        'name' => $eventType->name,
                        'color' => $eventType->color,
                        'icon' => $eventType->icon,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $eventTypes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load event types: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get marker color based on priority
     */
    private function getPriorityColor(string $priority): string
    {
        return match(strtolower($priority)) {
            'info' => '#0066cc',    // Blau - Information
            'low' => '#0fb67f',     // Grün - geringes Risiko
            'medium' => '#e6a50a',  // Orange - mittleres Risiko
            'high' => '#ff0000',    // Rot - hohes Risiko
            'critical' => '#8b0000', // Dunkelrot - kritisches Risiko
            default => '#e6a50a'    // Orange als Fallback
        };
    }

    /**
     * Track click on a custom event
     */
    public function trackClick(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'event_id' => 'required|exists:custom_events,id',
                'click_type' => 'required|in:list,map_marker,details_button',
            ]);

            EventClick::create([
                'custom_event_id' => $request->event_id,
                'click_type' => $request->click_type,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId(),
                'user_id' => auth()->id(),
                'clicked_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Click tracked successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track click: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get click statistics for a custom event
     */
    public function getClickStatistics($eventId): JsonResponse
    {
        try {
            $event = CustomEvent::findOrFail($eventId);
            $statistics = $event->getClickStatistics();

            // Add recent clicks
            $recentClicks = $event->clicks()
                ->with('user')
                ->orderBy('clicked_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($click) {
                    return [
                        'type' => $click->click_type_label,
                        'clicked_at' => $click->clicked_at->format('d.m.Y H:i'),
                        'user' => $click->user?->name ?? 'Anonym',
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $statistics,
                    'recent_clicks' => $recentClicks,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single custom event by ID
     */
    public function getEvent($eventId): JsonResponse
    {
        try {
            $event = CustomEvent::with(['creator', 'updater', 'country', 'eventType', 'eventTypes', 'countries'])
                ->findOrFail($eventId);

            // Format the event data similar to getDashboardEvents
            $eventTypeIcon = null;
            if ($event->eventTypes->isNotEmpty()) {
                $eventTypeIcon = $event->eventTypes->first()->icon;
            } elseif ($event->eventType) {
                $eventTypeIcon = $event->eventType->icon;
            }

            // Format countries data
            $countriesData = $event->countries->map(function ($country) use ($event) {
                $lat = $country->pivot->use_default_coordinates ? $country->lat : $country->pivot->latitude;
                $lng = $country->pivot->use_default_coordinates ? $country->lng : $country->pivot->longitude;

                // Use event coordinates as fallback
                if (!$lat && !$lng && $event->latitude && $event->longitude) {
                    $lat = $event->latitude;
                    $lng = $event->longitude;
                }

                return [
                    'id' => $country->id,
                    'name' => $country->getName('de'),
                    'iso_code' => $country->iso_code,
                    'latitude' => $lat ? (float) $lat : null,
                    'longitude' => $lng ? (float) $lng : null,
                    'location_note' => $country->pivot->location_note,
                    'use_default_coordinates' => $country->pivot->use_default_coordinates,
                ];
            })->toArray();

            $data = [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'event_type' => $event->getCorrectEventType(),
                'event_type_id' => $event->event_type_id,
                'event_type_name' => $event->eventType?->name ?? $event->getCorrectEventType(),
                'event_types' => $event->eventTypes->pluck('name')->toArray(),
                'event_types_codes' => $event->eventTypes->pluck('code')->toArray(),
                'event_type_ids' => $event->eventTypes->pluck('id')->toArray(),
                'country' => $event->country?->getName('de') ?? 'Unbekannt',
                'country_relation' => $event->country,
                'countries' => $countriesData,
                'latitude' => $event->latitude,
                'longitude' => $event->longitude,
                'marker_color' => $this->getPriorityColor($event->priority),
                'marker_icon' => $eventTypeIcon ?? $event->marker_icon,
                'icon_color' => $event->icon_color,
                'marker_size' => $event->marker_size,
                'popup_content' => $event->popup_content,
                'start_date' => optional($event->start_date)?->toDateTimeString(),
                'end_date' => $event->end_date,
                'priority' => $event->priority,
                'severity' => $event->severity,
                'category' => $event->category,
                'tags' => $event->tags,
                'is_active' => $event->is_active,
                'archived' => $event->archived,
                'archived_at' => $event->archived_at,
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
                'creator_name' => $event->creator?->name,
                'updater_name' => $event->updater?->name,
                'source' => 'custom',
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get event: ' . $e->getMessage()
            ], 404);
        }
    }
}
