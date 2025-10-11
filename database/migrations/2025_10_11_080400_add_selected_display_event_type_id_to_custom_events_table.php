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
        Schema::table('custom_events', function (Blueprint $table) {
            $table->foreignId('selected_display_event_type_id')
                ->nullable()
                ->after('event_type_id')
                ->constrained('event_types')
                ->nullOnDelete()
                ->comment('Manually selected event type for icon display when multiple event types are selected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->dropForeign(['selected_display_event_type_id']);
            $table->dropColumn('selected_display_event_type_id');
        });
    }
};
