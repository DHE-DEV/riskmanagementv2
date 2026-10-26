<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Koordinaten der Firmenadresse (OpenStreetMap/Nominatim), z. B. fuer die
     * Agenturen auf der Karte "Filialen & Standorte". company_geocoded_at:
     * wann zuletzt ermittelt – auch ohne Treffer, damit nicht endlos gesucht wird.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('company_latitude', 10, 7)->nullable()->after('company_country');
            $table->decimal('company_longitude', 10, 7)->nullable()->after('company_latitude');
            $table->timestamp('company_geocoded_at')->nullable()->after('company_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['company_latitude', 'company_longitude', 'company_geocoded_at']);
        });
    }
};
