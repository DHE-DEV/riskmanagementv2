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
        Schema::table('entry_conditions_logs', function (Blueprint $table) {
            // Erweitere nationality von VARCHAR(2) auf VARCHAR(255) für mehrere komma-separierte Codes
            $table->string('nationality', 255)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entry_conditions_logs', function (Blueprint $table) {
            // Zurück zu VARCHAR(2)
            $table->string('nationality', 2)->change();
        });
    }
};
