<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing country_id data to the pivot table
        DB::table('custom_events')
            ->whereNotNull('country_id')
            ->orderBy('id')
            ->chunk(100, function ($events) {
                foreach ($events as $event) {
                    // Check if relationship doesn't already exist
                    $exists = DB::table('country_custom_event')
                        ->where('custom_event_id', $event->id)
                        ->where('country_id', $event->country_id)
                        ->exists();

                    if (!$exists) {
                        DB::table('country_custom_event')->insert([
                            'custom_event_id' => $event->id,
                            'country_id' => $event->country_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all entries from the pivot table that match the original country_id
        DB::statement('
            DELETE cce FROM country_custom_event cce
            INNER JOIN custom_events ce ON ce.id = cce.custom_event_id
            WHERE cce.country_id = ce.country_id
        ');
    }
};
