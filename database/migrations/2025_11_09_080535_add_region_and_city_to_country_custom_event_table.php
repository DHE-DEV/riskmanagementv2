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
        Schema::table('country_custom_event', function (Blueprint $table) {
            $table->foreignId('region_id')->nullable()->after('country_id')->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->after('region_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('country_custom_event', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropForeign(['city_id']);
            $table->dropColumn(['region_id', 'city_id']);
        });
    }
};
