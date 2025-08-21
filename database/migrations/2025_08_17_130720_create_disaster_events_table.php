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
        if (!Schema::hasTable('disaster_events')) {
            Schema::create('disaster_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->string('event_type');
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->decimal('radius_km', 8, 2)->nullable();
            $table->foreignId('country_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('region_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('city_id')->nullable()->constrained()->onDelete('cascade');
            $table->json('affected_areas')->nullable();
            $table->date('event_date')->nullable();
            $table->datetime('start_time')->nullable();
            $table->datetime('end_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('impact_assessment')->nullable();
            $table->json('travel_recommendations')->nullable();
            $table->json('official_sources')->nullable();
            $table->text('media_coverage')->nullable();
            $table->json('tourism_impact')->nullable();
            $table->json('external_sources')->nullable();
            $table->datetime('last_updated')->nullable();
            $table->decimal('confidence_score', 3, 2)->nullable();
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('ai_summary')->nullable();
            $table->text('ai_recommendations')->nullable();
            $table->text('crisis_communication')->nullable();
            $table->json('keywords')->nullable();
            $table->decimal('magnitude', 5, 2)->nullable();
            $table->integer('casualties')->nullable();
            $table->text('economic_impact')->nullable();
            $table->text('infrastructure_damage')->nullable();
            $table->text('emergency_response')->nullable();
            $table->string('recovery_status')->nullable();
            $table->string('external_id')->nullable();
            $table->string('gdacs_event_id')->nullable();
            $table->string('gdacs_episode_id')->nullable();
            $table->string('gdacs_alert_level')->nullable();
            $table->decimal('gdacs_alert_score', 5, 2)->nullable();
            $table->string('gdacs_episode_alert_level')->nullable();
            $table->decimal('gdacs_episode_alert_score', 5, 2)->nullable();
            $table->string('gdacs_event_name')->nullable();
            $table->string('gdacs_calculation_type')->nullable();
            $table->decimal('gdacs_severity_value', 10, 2)->nullable();
            $table->string('gdacs_severity_unit')->nullable();
            $table->string('gdacs_severity_text')->nullable();
            $table->decimal('gdacs_population_value', 15, 2)->nullable();
            $table->string('gdacs_population_unit')->nullable();
            $table->string('gdacs_population_text')->nullable();
            $table->string('gdacs_vulnerability')->nullable();
            $table->string('gdacs_iso3')->nullable();
            $table->string('gdacs_country')->nullable();
            $table->string('gdacs_glide')->nullable();
            $table->json('gdacs_bbox')->nullable();
            $table->string('gdacs_cap_url')->nullable();
            $table->string('gdacs_icon_url')->nullable();
            $table->string('gdacs_version')->nullable();
            $table->boolean('gdacs_temporary')->default(false);
            $table->boolean('gdacs_is_current')->default(false);
            $table->integer('gdacs_duration_weeks')->nullable();
            $table->json('gdacs_resources')->nullable();
            $table->string('gdacs_map_image')->nullable();
            $table->string('gdacs_map_link')->nullable();
            $table->datetime('gdacs_date_added')->nullable();
            $table->datetime('gdacs_date_modified')->nullable();
            $table->json('weather_conditions')->nullable();
            $table->json('evacuation_info')->nullable();
            $table->json('transportation_impact')->nullable();
            $table->json('accommodation_impact')->nullable();
            $table->json('communication_status')->nullable();
            $table->json('health_services_status')->nullable();
            $table->json('utility_services_status')->nullable();
            $table->json('border_crossings_status')->nullable();
            $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disaster_events');
    }
};
