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
        // Fix: Add AUTO_INCREMENT to id columns which were missing
        // This affects multiple core tables that were created without AUTO_INCREMENT
        // Note: PRIMARY KEY already exists, so we only add AUTO_INCREMENT
        $tables = ['regions', 'cities', 'countries', 'custom_events', 'event_types'];

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed - AUTO_INCREMENT should always be present on primary keys
    }
};
