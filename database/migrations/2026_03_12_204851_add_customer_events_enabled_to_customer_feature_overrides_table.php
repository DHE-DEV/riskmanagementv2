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
        Schema::table('customer_feature_overrides', function (Blueprint $table) {
            $table->boolean('navigation_customer_events_enabled')->nullable()->after('navigation_visumpoint_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('customer_feature_overrides', function (Blueprint $table) {
            $table->dropColumn('navigation_customer_events_enabled');
        });
    }
};
