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
            $table->unsignedInteger('legacy_client_account_id')->nullable()->unique()->after('pds_customer_number');
            $table->unsignedTinyInteger('legacy_passolution_company_id')->nullable()->after('legacy_client_account_id');
            $table->unsignedInteger('legacy_account_id')->nullable()->after('legacy_passolution_company_id');
            $table->unsignedBigInteger('legacy_organization_id')->nullable()->after('legacy_account_id');
            $table->unsignedTinyInteger('legacy_language_id')->nullable()->after('legacy_organization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'legacy_client_account_id',
                'legacy_passolution_company_id',
                'legacy_account_id',
                'legacy_organization_id',
                'legacy_language_id',
            ]);
        });
    }
};
