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
        // Check if AUTO_INCREMENT is already set
        $result = DB::select("SHOW COLUMNS FROM jobs WHERE Field = 'id'");
        if (!empty($result) && strpos($result[0]->Extra, 'auto_increment') === false) {
            DB::statement('ALTER TABLE jobs MODIFY id BIGINT UNSIGNED AUTO_INCREMENT');
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
