<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Set default to NULL for salutation column
        DB::statement("ALTER TABLE `folder_participants` MODIFY COLUMN `salutation` ENUM('mr', 'mrs', 'child', 'infant', 'diverse') NULL DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous state
        DB::statement("ALTER TABLE `folder_participants` MODIFY COLUMN `salutation` ENUM('mr', 'mrs', 'child', 'infant', 'diverse') NULL");
    }
};
