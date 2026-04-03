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
        Schema::table('customers', function (Blueprint $table) {
            $tours = [
                'has_seen_trs_tour',
                'has_seen_entry_conditions_tour',
                'has_seen_travel_data_tour',
                'has_seen_travel_links_tour',
                'has_seen_booking_tour',
                'has_seen_airports_tour',
                'has_seen_branches_tour',
                'has_seen_my_travelers_tour',
                'has_seen_customer_events_tour',
                'has_seen_cruise_tour',
                'has_seen_business_visa_tour',
                'has_seen_visumpoint_tour',
            ];
            foreach ($tours as $tour) {
                $table->boolean($tour)->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'has_seen_trs_tour',
                'has_seen_entry_conditions_tour',
                'has_seen_travel_data_tour',
                'has_seen_travel_links_tour',
                'has_seen_booking_tour',
                'has_seen_airports_tour',
                'has_seen_branches_tour',
                'has_seen_my_travelers_tour',
                'has_seen_customer_events_tour',
                'has_seen_cruise_tour',
                'has_seen_business_visa_tour',
                'has_seen_visumpoint_tour',
            ]);
        });
    }
};
