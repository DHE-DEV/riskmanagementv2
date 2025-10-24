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
            // Firmenanschrift
            $table->string('company_name')->nullable()->after('business_type');
            $table->string('company_street')->nullable()->after('company_name');
            $table->string('company_postal_code')->nullable()->after('company_street');
            $table->string('company_city')->nullable()->after('company_postal_code');
            $table->string('company_country')->nullable()->after('company_city');

            // Rechnungsadresse
            $table->string('billing_company_name')->nullable()->after('company_country');
            $table->string('billing_street')->nullable()->after('billing_company_name');
            $table->string('billing_postal_code')->nullable()->after('billing_street');
            $table->string('billing_city')->nullable()->after('billing_postal_code');
            $table->string('billing_country')->nullable()->after('billing_city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'company_street',
                'company_postal_code',
                'company_city',
                'company_country',
                'billing_company_name',
                'billing_street',
                'billing_postal_code',
                'billing_city',
                'billing_country',
            ]);
        });
    }
};
