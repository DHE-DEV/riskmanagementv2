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
        Schema::table('airports', function (Blueprint $table) {
            if (!Schema::hasColumn('airports', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('type');
            }
            if (!Schema::hasColumn('airports', 'website')) {
                $table->string('website', 2048)->nullable()->after('country_id');
            }
            if (!Schema::hasColumn('airports', 'security_timeslot_url')) {
                $table->string('security_timeslot_url', 2048)->nullable()->after('website');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('airports', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'website', 'security_timeslot_url']);
        });
    }
};
