<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verschachteltes "account"-Objekt aus dem SSO-userinfo-Response (Laravel
 * Passport) flach in dedizierten pds_account_*-Spalten am Customer ablegen.
 * account.id == top-level account_id -> bleibt in pds_account_id (keine Dublette).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('pds_account_type')->nullable()->after('pds_account_id');
            $table->string('pds_account_name')->nullable()->after('pds_account_type');
            $table->string('pds_account_first_name')->nullable()->after('pds_account_name');
            $table->string('pds_account_last_name')->nullable()->after('pds_account_first_name');
            $table->string('pds_account_email')->nullable()->after('pds_account_last_name');
            $table->string('pds_account_phone')->nullable()->after('pds_account_email');
            $table->string('pds_account_address_line_1')->nullable()->after('pds_account_phone');
            $table->string('pds_account_zip_code')->nullable()->after('pds_account_address_line_1');
            $table->string('pds_account_city')->nullable()->after('pds_account_zip_code');
            $table->string('pds_account_state')->nullable()->after('pds_account_city');
            $table->string('pds_account_country')->nullable()->after('pds_account_state');
            $table->string('pds_account_subscription_type')->nullable()->after('pds_account_country');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'pds_account_type',
                'pds_account_name',
                'pds_account_first_name',
                'pds_account_last_name',
                'pds_account_email',
                'pds_account_phone',
                'pds_account_address_line_1',
                'pds_account_zip_code',
                'pds_account_city',
                'pds_account_state',
                'pds_account_country',
                'pds_account_subscription_type',
            ]);
        });
    }
};
