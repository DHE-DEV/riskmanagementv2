<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Welche Reisen eine Travel-Alert-Mail genannt hat (Kennung, Name,
     * Zeitraum) – bisher wurde nur ihre Anzahl festgehalten.
     */
    public function up(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->json('affected_trips')->nullable()->after('affected_trips_count');
        });
    }

    public function down(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropColumn('affected_trips');
        });
    }
};
