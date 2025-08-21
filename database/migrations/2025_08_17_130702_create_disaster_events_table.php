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
        // This migration is kept for compatibility with production
        // The actual table creation is handled by 2025_08_17_130720_create_disaster_events_table
        // This just checks if the table exists to prevent errors
        if (!Schema::hasTable('disaster_events')) {
            // If table doesn't exist, it will be created by the later migration
            // This is just a placeholder to prevent migration errors
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Do nothing - handled by the actual migration
    }
};
