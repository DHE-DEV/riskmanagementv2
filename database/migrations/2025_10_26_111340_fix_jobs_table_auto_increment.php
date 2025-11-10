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
        // Fix jobs table id column to have AUTO_INCREMENT
        try {
            // Check if AUTO_INCREMENT is already set
            $result = DB::select("SHOW COLUMNS FROM jobs WHERE Field = 'id'");
            if (!empty($result) && strpos($result[0]->Extra, 'auto_increment') === false) {
                // Ensure PRIMARY KEY exists and add AUTO_INCREMENT
                DB::statement('ALTER TABLE jobs MODIFY id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY');
            }
        } catch (\Exception $e) {
            // If migration fails (e.g., already has AUTO_INCREMENT), just skip it
            // This allows the migration to succeed even if the table is already correct
            \Log::info('Jobs table AUTO_INCREMENT migration skipped: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this fix
    }
};
