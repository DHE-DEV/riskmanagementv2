<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('airport_codes_1', function (Blueprint $table) {
            // Foreign keys für City und Country
            $table->foreignId('city_id')->nullable()->after('iso_region');
            $table->foreignId('country_id')->nullable()->after('city_id');

            // Website (zusätzlich zu home_link)
            $table->text('website')->nullable()->after('home_link');

            // Zeitzone
            $table->string('timezone')->nullable()->after('elevation_ft');
            $table->string('dst_timezone')->nullable()->after('timezone');

            // Status-Felder
            $table->boolean('is_active')->default(true)->after('scheduled_service');
            $table->boolean('operates_24h')->default(false)->after('is_active');

            // JSON-Felder für erweiterte Informationen
            $table->json('lounges')->nullable()->after('operates_24h');
            $table->json('nearby_hotels')->nullable()->after('lounges');
            $table->json('mobility_options')->nullable()->after('nearby_hotels');

            // Zusätzliche URLs
            $table->text('security_timeslot_url')->nullable()->after('wikipedia_link');

            // Source-Tracking
            $table->string('source', 50)->nullable()->after('keywords');

            // SoftDeletes
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('airport_codes_1', function (Blueprint $table) {
            $table->dropColumn([
                'city_id',
                'country_id',
                'website',
                'timezone',
                'dst_timezone',
                'is_active',
                'operates_24h',
                'lounges',
                'nearby_hotels',
                'mobility_options',
                'security_timeslot_url',
                'source',
                'deleted_at',
            ]);
        });
    }
};
