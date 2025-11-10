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
        Schema::table('airline_airport', function (Blueprint $table) {
            $table->string('terminal', 50)->nullable()->after('direction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('airline_airport', function (Blueprint $table) {
            $table->dropColumn('terminal');
        });
    }
};
