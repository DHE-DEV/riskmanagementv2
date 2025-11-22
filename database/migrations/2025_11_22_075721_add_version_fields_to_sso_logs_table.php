<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds version tracking fields to sso_logs table to verify correct deployment
     * of both IdP (pds-homepage) and SP (riskmanagementv2) services.
     *
     * Fügt Versionsnummern-Felder zur sso_logs Tabelle hinzu, um korrektes Deployment
     * sowohl des IdP (pds-homepage) als auch des SP (riskmanagementv2) zu verifizieren.
     */
    public function up(): void
    {
        Schema::table('sso_logs', function (Blueprint $table) {
            // Version of the IdP (pds-homepage) that created/sent this log
            $table->string('version_idp', 20)
                  ->nullable()
                  ->after('step')
                  ->comment('SSO version of Identity Provider (pds-homepage)');

            // Version of the SP (riskmanagementv2) that processed this log
            $table->string('version_sp', 20)
                  ->nullable()
                  ->after('version_idp')
                  ->comment('SSO version of Service Provider (riskmanagementv2)');

            // Add index for version queries to quickly find logs by specific versions
            $table->index('version_idp', 'idx_version_idp');
            $table->index('version_sp', 'idx_version_sp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sso_logs', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_version_idp');
            $table->dropIndex('idx_version_sp');

            // Drop columns
            $table->dropColumn(['version_idp', 'version_sp']);
        });
    }
};
