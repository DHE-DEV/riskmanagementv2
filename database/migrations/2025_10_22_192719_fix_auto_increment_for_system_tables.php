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
        // Fix: Add AUTO_INCREMENT to Laravel system tables
        // Note: PRIMARY KEY already exists, so we only add AUTO_INCREMENT

        // migrations table uses INT instead of BIGINT
        DB::statement('ALTER TABLE migrations MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT');

        // Standard system tables use BIGINT
        $bigintTables = ['users', 'sessions', 'jobs', 'failed_jobs'];

        foreach ($bigintTables as $table) {
            try {
                DB::statement("ALTER TABLE {$table} MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
            } catch (\Exception $e) {
                // Table might not exist in all environments
                \Log::info("Skipping {$table}: " . $e->getMessage());
            }
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
