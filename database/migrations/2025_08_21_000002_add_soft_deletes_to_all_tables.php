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
        // Add soft deletes to cities table
        if (Schema::hasTable('cities') && !Schema::hasColumn('cities', 'deleted_at')) {
            Schema::table('cities', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add soft deletes to regions table
        if (Schema::hasTable('regions') && !Schema::hasColumn('regions', 'deleted_at')) {
            Schema::table('regions', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add soft deletes to countries table
        if (Schema::hasTable('countries') && !Schema::hasColumn('countries', 'deleted_at')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add soft deletes to continents table
        if (Schema::hasTable('continents') && !Schema::hasColumn('continents', 'deleted_at')) {
            Schema::table('continents', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove soft deletes from cities table
        if (Schema::hasTable('cities') && Schema::hasColumn('cities', 'deleted_at')) {
            Schema::table('cities', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        // Remove soft deletes from regions table
        if (Schema::hasTable('regions') && Schema::hasColumn('regions', 'deleted_at')) {
            Schema::table('regions', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        // Remove soft deletes from countries table
        if (Schema::hasTable('countries') && Schema::hasColumn('countries', 'deleted_at')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        // Remove soft deletes from continents table
        if (Schema::hasTable('continents') && Schema::hasColumn('continents', 'deleted_at')) {
            Schema::table('continents', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
