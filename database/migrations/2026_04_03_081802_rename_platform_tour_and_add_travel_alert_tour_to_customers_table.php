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
        Schema::table('customers', function (Blueprint $table) {
            $table->renameColumn('has_seen_travel_alert_tour', 'has_seen_platform_tour');
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('has_seen_travel_alert_tour')->default(false)->after('has_seen_platform_tour');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('has_seen_travel_alert_tour');
            $table->renameColumn('has_seen_platform_tour', 'has_seen_travel_alert_tour');
        });
    }
};
