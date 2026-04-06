<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_alert_orders', function (Blueprint $table) {
            $table->string('customer_type')->default('business')->after('id');
            $table->json('business_type')->nullable()->after('customer_type');
            $table->string('company')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('travel_alert_orders', function (Blueprint $table) {
            $table->dropColumn(['customer_type', 'business_type']);
            $table->string('company')->nullable(false)->change();
        });
    }
};
