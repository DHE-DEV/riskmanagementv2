<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Manche Kooperationen haben einen Festbetrag pro Buero und Monat – abgerechnet
     * gesammelt ueber die Kooperation oder einzeln, je nach Zeitraum des Kunden.
     */
    public function up(): void
    {
        Schema::table('cooperations', function (Blueprint $table) {
            $table->decimal('fixed_price_per_office', 10, 2)->nullable()->after('billing_interval');
        });
    }

    public function down(): void
    {
        Schema::table('cooperations', function (Blueprint $table) {
            $table->dropColumn('fixed_price_per_office');
        });
    }
};
