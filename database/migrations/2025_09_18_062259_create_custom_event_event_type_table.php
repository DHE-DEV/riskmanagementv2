<?php

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
        Schema::create('custom_event_event_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_event_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_type_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Unique constraint to prevent duplicates
            $table->unique(['custom_event_id', 'event_type_id']);
        });

        // Migrate existing data from single event_type_id to many-to-many relationship
        DB::table('custom_events')
            ->whereNotNull('event_type_id')
            ->orderBy('id')
            ->chunk(100, function ($events) {
                foreach ($events as $event) {
                    DB::table('custom_event_event_type')->insert([
                        'custom_event_id' => $event->id,
                        'event_type_id' => $event->event_type_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_event_event_type');
    }
};