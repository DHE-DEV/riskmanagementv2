<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * pds_account_id aus Keycloak (profile.attributes.pds_account_id) am Customer
 * ablegen, damit nach Login die lokal vorgehaltenen Travel-Detail-Links ueber
 * die Account-ID geladen werden koennen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('pds_account_id')->nullable()->after('id');
            $table->index('pds_account_id', 'idx_customers_pds_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_pds_account_id');
            $table->dropColumn('pds_account_id');
        });
    }
};
