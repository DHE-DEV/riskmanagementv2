<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip in testing environment (SQLite doesn't support MODIFY COLUMN for ENUM)
        if (app()->environment('testing')) {
            return;
        }

        // MySQL specific: Modify ENUM to include 'info'
        DB::statement("ALTER TABLE custom_events MODIFY COLUMN priority ENUM('info', 'low', 'medium', 'high', 'critical') DEFAULT 'medium'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'info' from ENUM (revert to original)
        DB::statement("ALTER TABLE custom_events MODIFY COLUMN priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium'");
    }
};
