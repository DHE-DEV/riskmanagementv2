<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('td_trips', function (Blueprint $table) {
            $table->string('reference_id', 128)->nullable()->after('booking_reference');
            $table->string('trip_name', 255)->nullable()->after('reference_id');
            $table->string('cruise_compass_cruise_id', 128)->nullable()->after('trip_name');
            $table->json('nationalities')->nullable()->after('countries_visited');
            $table->json('travel_modes')->nullable()->after('nationalities');
            $table->boolean('with_minors')->default(false)->after('travel_modes');
            $table->json('tour_operators')->nullable()->after('with_minors');
            $table->json('individual_contents')->nullable()->after('tour_operators');
            $table->text('note')->nullable()->after('individual_contents');
            $table->string('cover_media', 512)->nullable()->after('note');
            $table->unsignedInteger('visits')->default(0)->after('cover_media');
            $table->timestamp('last_visited_at')->nullable()->after('visits');
            $table->timestamp('last_important_change_at')->nullable()->after('last_visited_at');
        });
    }

    public function down(): void
    {
        Schema::table('td_trips', function (Blueprint $table) {
            $table->dropColumn([
                'reference_id',
                'trip_name',
                'cruise_compass_cruise_id',
                'nationalities',
                'travel_modes',
                'with_minors',
                'tour_operators',
                'individual_contents',
                'note',
                'cover_media',
                'visits',
                'last_visited_at',
                'last_important_change_at',
            ]);
        });
    }
};
