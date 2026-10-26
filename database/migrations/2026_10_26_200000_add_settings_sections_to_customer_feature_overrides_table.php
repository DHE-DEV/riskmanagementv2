<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Einstellungsbereiche unter "Organisation" je Kunde ein-/ausblenden
     * (sonst global per CUSTOMER_SETTINGS_*_ENABLED).
     */
    public function up(): void
    {
        Schema::table('customer_feature_overrides', function (Blueprint $table) {
            $table->boolean('settings_master_data_enabled')->nullable()->after('navigation_doctors_network_enabled');
            $table->boolean('settings_organization_enabled')->nullable()->after('settings_master_data_enabled');
            $table->boolean('settings_users_enabled')->nullable()->after('settings_organization_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('customer_feature_overrides', function (Blueprint $table) {
            $table->dropColumn(['settings_master_data_enabled', 'settings_organization_enabled', 'settings_users_enabled']);
        });
    }
};
