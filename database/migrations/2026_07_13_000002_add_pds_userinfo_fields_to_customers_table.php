<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rohwerte des SSO-userinfo-Response (Laravel Passport) 1:1 am Customer ablegen,
 * getrennt von den ggf. transformierten Feldern name/email/username:
 *   id -> pds_id, name -> pds_name, username -> pds_username, email -> pds_email
 * (account_id -> pds_account_id existiert bereits in einer eigenen Migration.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('pds_id')->nullable()->after('provider_id');
            $table->string('pds_name')->nullable()->after('pds_id');
            $table->string('pds_username')->nullable()->after('pds_name');
            $table->string('pds_email')->nullable()->after('pds_username');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['pds_id', 'pds_name', 'pds_username', 'pds_email']);
        });
    }
};
