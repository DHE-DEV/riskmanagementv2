<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Account-uebergreifender Sync legt Trips nach pds_account_id ab (statt nur
 * customer_id), damit Links auch fuer Accounts gespeichert werden koennen, fuer
 * die es (noch) keinen lokalen Customer gibt. Beim Login wird ueber die
 * pds_account_id (aus Keycloak) der passende Bestand geladen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('td_trips', function (Blueprint $table) {
            $table->unsignedBigInteger('pds_account_id')->nullable()->after('customer_id');
            $table->index('pds_account_id', 'idx_pds_account_id');
            $table->index(['pds_account_id', 'computed_end_at'], 'idx_pds_account_end');
        });
    }

    public function down(): void
    {
        Schema::table('td_trips', function (Blueprint $table) {
            $table->dropIndex('idx_pds_account_id');
            $table->dropIndex('idx_pds_account_end');
            $table->dropColumn('pds_account_id');
        });
    }
};
