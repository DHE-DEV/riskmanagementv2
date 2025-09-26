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
        Schema::table('country_custom_event', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('custom_event_id');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->text('location_note')->nullable()->after('longitude');
            $table->boolean('use_default_coordinates')->default(true)->after('location_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('country_custom_event', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'location_note', 'use_default_coordinates']);
        });
    }
};
