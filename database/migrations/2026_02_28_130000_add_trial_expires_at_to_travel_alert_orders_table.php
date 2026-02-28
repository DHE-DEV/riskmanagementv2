<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_alert_orders', function (Blueprint $table) {
            $table->date('trial_expires_at')->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('travel_alert_orders', function (Blueprint $table) {
            $table->dropColumn('trial_expires_at');
        });
    }
};
