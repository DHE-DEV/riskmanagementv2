<?php

namespace App\Http\Controllers;

use App\Models\CustomEvent;
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
            $events = CustomEvent::where('is_active', true)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->with(['creator', 'updater', 'country', 'eventType'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($event) {
                    // Verwende EventType Icon falls verfügbar, sonst Fallback auf marker_icon
                    $eventTypeIcon = $event->eventType?->icon;
                    
                    return [
                        'id' => $event->id,
                        'title' => $event->title,
                        'description' => $event->description,
                        'event_type' => $event->event_type,
                        'country' => $event->country?->getName('de') ?? 'Unbekannt',
                        'country_relation' => $event->country,
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
            $events = CustomEvent::where('is_active', true)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->with(['country', 'eventType'])
                ->get()
                ->map(function ($event) {
                    // Verwende EventType Icon falls verfügbar, sonst Fallback auf marker_icon
                    $eventTypeIcon = $event->eventType?->icon;
                    
                    return [
                        'id' => $event->id,
                        'title' => $event->title,
                        'description' => $event->description,
                        'event_type' => $event->event_type,
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
            $stats = [
                'total_events' => CustomEvent::count(),
                'active_events' => CustomEvent::where('is_active', true)->count(),
                'events_by_type' => CustomEvent::selectRaw('event_type, count(*) as count')
                    ->groupBy('event_type')
                    ->pluck('count', 'event_type')
                    ->toArray(),
                'events_by_priority' => CustomEvent::selectRaw('priority, count(*) as count')
                    ->groupBy('priority')
                    ->pluck('count', 'priority')
                    ->toArray(),
                'events_by_severity' => CustomEvent::selectRaw('severity, count(*) as count')
                    ->groupBy('severity')
                    ->pluck('count', 'severity')
                    ->toArray(),
                'recent_events' => CustomEvent::where('created_at', '>=', now()->subDays(7))->count(),
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
     * Get marker color based on priority
     */
    private function getPriorityColor(string $priority): string
    {
        return match(strtolower($priority)) {
            'low' => '#0fb67f',     // Grün - geringes Risiko
            'medium' => '#e6a50a',  // Orange - mittleres Risiko  
            'high' => '#ff0000',    // Rot - hohes Risiko
            'critical' => '#8b0000', // Dunkelrot - kritisches Risiko
            default => '#e6a50a'    // Orange als Fallback
        };
    }
}
