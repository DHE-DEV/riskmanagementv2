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
        Schema::table('folder_flight_segments', function (Blueprint $table) {
            // Add foreign keys to airport_codes_1 table
            $table->unsignedBigInteger('departure_airport_id')->nullable()->after('departure_airport_code');
            $table->unsignedBigInteger('arrival_airport_id')->nullable()->after('arrival_airport_code');

            // Add foreign keys to countries table
            $table->unsignedBigInteger('departure_country_id')->nullable()->after('departure_country_code');
            $table->unsignedBigInteger('arrival_country_id')->nullable()->after('arrival_country_code');

            // Add foreign key constraints
            $table->foreign('departure_airport_id')
                ->references('id')
                ->on('airport_codes_1')
                ->onDelete('set null');

            $table->foreign('arrival_airport_id')
                ->references('id')
                ->on('airport_codes_1')
                ->onDelete('set null');

            $table->foreign('departure_country_id')
                ->references('id')
                ->on('countries')
                ->onDelete('set null');

            $table->foreign('arrival_country_id')
                ->references('id')
                ->on('countries')
                ->onDelete('set null');

            // Add indexes for performance
            $table->index('departure_airport_id');
            $table->index('arrival_airport_id');
            $table->index('departure_country_id');
            $table->index('arrival_country_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('folder_flight_segments', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['departure_airport_id']);
            $table->dropForeign(['arrival_airport_id']);
            $table->dropForeign(['departure_country_id']);
            $table->dropForeign(['arrival_country_id']);

            // Drop indexes
            $table->dropIndex(['departure_airport_id']);
            $table->dropIndex(['arrival_airport_id']);
            $table->dropIndex(['departure_country_id']);
            $table->dropIndex(['arrival_country_id']);

            // Drop columns
            $table->dropColumn([
                'departure_airport_id',
                'arrival_airport_id',
                'departure_country_id',
                'arrival_country_id',
            ]);
        });
    }
};
