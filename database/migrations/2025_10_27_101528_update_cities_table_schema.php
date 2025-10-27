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
        Schema::table('cities', function (Blueprint $table) {
            // Drop the old name column if it exists
            if (Schema::hasColumn('cities', 'name')) {
                $table->dropColumn('name');
            }

            // Add the name_translations JSON column if it doesn't exist
            if (!Schema::hasColumn('cities', 'name_translations')) {
                $table->json('name_translations')->after('id');
            }

            // Add population if it doesn't exist
            if (!Schema::hasColumn('cities', 'population')) {
                $table->integer('population')->nullable()->after('region_id');
            }

            // Rename latitude/longitude to lat/lng if needed
            if (Schema::hasColumn('cities', 'latitude') && !Schema::hasColumn('cities', 'lat')) {
                $table->renameColumn('latitude', 'lat');
            }
            if (Schema::hasColumn('cities', 'longitude') && !Schema::hasColumn('cities', 'lng')) {
                $table->renameColumn('longitude', 'lng');
            }

            // Drop is_active if it exists (not in the model)
            if (Schema::hasColumn('cities', 'is_active')) {
                $table->dropColumn('is_active');
            }

            // Add soft deletes if not exists
            if (!Schema::hasColumn('cities', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            // Reverse the changes
            if (Schema::hasColumn('cities', 'name_translations')) {
                $table->dropColumn('name_translations');
            }

            if (!Schema::hasColumn('cities', 'name')) {
                $table->string('name')->after('id');
            }

            if (Schema::hasColumn('cities', 'population')) {
                $table->dropColumn('population');
            }

            if (Schema::hasColumn('cities', 'lat') && !Schema::hasColumn('cities', 'latitude')) {
                $table->renameColumn('lat', 'latitude');
            }
            if (Schema::hasColumn('cities', 'lng') && !Schema::hasColumn('cities', 'longitude')) {
                $table->renameColumn('lng', 'longitude');
            }

            if (!Schema::hasColumn('cities', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }

            if (Schema::hasColumn('cities', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
