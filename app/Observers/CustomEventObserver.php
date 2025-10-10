<?php

namespace App\Observers;

use App\Models\CustomEvent;
use App\Models\EventType;

class CustomEventObserver
{
    /**
     * Handle the CustomEvent "saved" event.
     * Update marker_icon from eventTypes after saving.
     */
    public function saved(CustomEvent $customEvent): void
    {
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
}
