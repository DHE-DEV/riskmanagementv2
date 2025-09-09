<?php

use App\Models\DisasterEvent;
use App\Models\EventType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Map old event_type codes to new EventType IDs
        $disasterEvents = DisasterEvent::whereNotNull('event_type')->get();
        
        foreach ($disasterEvents as $event) {
            $eventType = EventType::where('code', $event->event_type)->first();
            
            if ($eventType) {
                $event->update(['event_type_id' => $eventType->id]);
            } else {
                // If event type doesn't exist, create a fallback to 'other'
                $otherType = EventType::where('code', 'other')->first();
                if ($otherType) {
                    $event->update(['event_type_id' => $otherType->id]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset event_type_id to null
        DisasterEvent::whereNotNull('event_type_id')->update(['event_type_id' => null]);
    }
};
