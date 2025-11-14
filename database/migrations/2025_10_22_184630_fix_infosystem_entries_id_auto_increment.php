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
        // Fix: Add AUTO_INCREMENT to id column which was missing
        // Note: PRIMARY KEY already exists, so we only add AUTO_INCREMENT
        // SQLite doesn't support MODIFY syntax, but INTEGER PRIMARY KEY is automatically AUTOINCREMENT
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE infosystem_entries MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        }
        // For SQLite, INTEGER PRIMARY KEY already has AUTOINCREMENT behavior by default
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed - AUTO_INCREMENT should always be present
    }
};
