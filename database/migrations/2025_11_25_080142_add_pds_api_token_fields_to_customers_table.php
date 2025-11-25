<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add PDS API token fields for SSO integration.
     * These tokens allow riskmanagementv2 to make API calls to pds-api on behalf of the customer.
     *
     * PDS API Token-Felder für SSO-Integration hinzufügen.
     * Diese Tokens ermöglichen riskmanagementv2, API-Aufrufe an pds-api im Namen des Kunden zu machen.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->text('pds_api_token')->nullable()->after('passolution_roles')
                ->comment('PDS API access token for pds-api calls');
            $table->timestamp('pds_api_token_expires_at')->nullable()->after('pds_api_token')
                ->comment('Expiration timestamp for the PDS API token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['pds_api_token', 'pds_api_token_expires_at']);
        });
    }
};
