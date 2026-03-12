<?php

namespace App\Observers;

use App\Jobs\SendRiskEventNotifications;
use App\Models\CustomEvent;
use App\Models\EventType;
use Illuminate\Support\Facades\Cache;

class CustomEventObserver
{
    /**
     * Handle the CustomEvent "created" event.
     * Dispatch notification if the event is already approved and active.
     */
    public function created(CustomEvent $customEvent): void
    {
        // Don't send global notifications for customer-owned events
        if ($customEvent->customer_id) {
            return;
        }

        if ($customEvent->is_active && $customEvent->review_status === 'approved') {
            SendRiskEventNotifications::dispatch($customEvent);
        }
    }

    /**
     * Handle the CustomEvent "updated" event.
     * Dispatch notification when an event gets approved.
     */
    public function updated(CustomEvent $customEvent): void
    {
        // Don't send global notifications for customer-owned events
        if ($customEvent->customer_id) {
            return;
        }

        if (
            $customEvent->isDirty('review_status')
            && $customEvent->review_status === 'approved'
            && $customEvent->is_active
        ) {
            SendRiskEventNotifications::dispatch($customEvent);
        }
    }

    /**
     * Handle the CustomEvent "saved" event.
     * Update marker_icon from eventTypes after saving.
     */
    public function saved(CustomEvent $customEvent): void
    {
        $this->clearFeedCaches();

        // Lade die EventTypes-Beziehung, falls noch nicht geladen
        if (!$customEvent->relationLoaded('eventTypes')) {
            $customEvent->load('eventTypes');
        }

        // Hole das Icon vom ersten EventType
        $eventTypeIcon = null;
        if ($customEvent->eventTypes->isNotEmpty()) {
            $eventTypeIcon = $customEvent->eventTypes->first()->icon;

            // Aktualisiere auch event_type_id für Backward Compatibility
            $firstEventTypeId = $customEvent->eventTypes->first()->id;

            // Nur aktualisieren, wenn sich etwas geändert hat
            if ($customEvent->marker_icon !== $eventTypeIcon || $customEvent->event_type_id !== $firstEventTypeId) {
                // Verwende updateQuietly, um eine Endlosschleife zu vermeiden
                $customEvent->updateQuietly([
                    'marker_icon' => $eventTypeIcon,
                    'event_type_id' => $firstEventTypeId,
                ]);
            }
        }
    }

    /**
     * Handle the CustomEvent "deleted" event.
     */
    public function deleted(CustomEvent $customEvent): void
    {
        $this->clearFeedCaches();
    }

    /**
     * Clear all feed caches so new/updated/deleted events are immediately visible.
     */
    private function clearFeedCaches(): void
    {
        // GTM API cache
        Cache::forget('gtm_all_events');

        // Static feed keys
        Cache::forget('feed:all_events:rss');
        Cache::forget('feed:all_events:atom');

        // Priority feeds
        foreach (['high', 'medium', 'low', 'info'] as $priority) {
            Cache::forget("feed:priority:{$priority}:rss");
        }

        // Dynamic keys (country, type, region) — clear via database query
        // since we can't enumerate all possible values
        if (config('cache.default') === 'database') {
            $table = config('cache.stores.database.table', 'cache');
            $prefix = config('cache.prefix', '');
            $pattern = ($prefix ? $prefix . '_' : '') . 'feed:%';

            \Illuminate\Support\Facades\DB::table($table)
                ->where('key', 'like', $pattern)
                ->delete();
        }
    }
}
