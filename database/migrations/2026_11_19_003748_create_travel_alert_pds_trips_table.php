<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lokaler Spiegel der pds-Reisen für den Travel Alert: Konten, die Travel Alert nutzen,
 * werden einmal vollständig geladen und danach über den Delta-Feed aktuell gehalten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_alert_pds_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('pds_account_id')->primary();
            $table->timestamp('last_requested_at')->index();
            $table->timestamp('full_synced_at')->nullable();
            $table->date('synced_from')->nullable();
            $table->unsignedInteger('trips_count')->default(0);
            $table->timestamps();
        });

        Schema::create('travel_alert_pds_trips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pds_account_id');
            $table->string('tid', 64)->unique();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('payload');
            $table->timestamp('last_change_at')->nullable();
            $table->timestamps();

            $table->index(['pds_account_id', 'end_date', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_alert_pds_trips');
        Schema::dropIfExists('travel_alert_pds_accounts');
    }
};
