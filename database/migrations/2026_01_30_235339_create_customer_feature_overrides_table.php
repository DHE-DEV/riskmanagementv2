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
        Schema::create('customer_feature_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            // Navigation overrides (null = use .env default, true = always show, false = always hide)
            $table->boolean('navigation_events_enabled')->nullable();
            $table->boolean('navigation_entry_conditions_enabled')->nullable();
            $table->boolean('navigation_booking_enabled')->nullable();
            $table->boolean('navigation_airports_enabled')->nullable();
            $table->boolean('navigation_branches_enabled')->nullable();
            $table->boolean('navigation_my_travelers_enabled')->nullable();
            $table->boolean('navigation_risk_overview_enabled')->nullable();
            $table->boolean('navigation_cruise_enabled')->nullable();
            $table->boolean('navigation_business_visa_enabled')->nullable();
            $table->boolean('navigation_center_map_enabled')->nullable();

            $table->timestamps();

            $table->unique('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_feature_overrides');
    }
};
