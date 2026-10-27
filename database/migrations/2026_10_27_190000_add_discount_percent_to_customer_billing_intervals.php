<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rabatt in Prozent fuer Jahres- (oder Halbjahres-)zahlung – je Zeitraum
     * des Abrechnungsintervalls (Admin v2 › Kunde › Abo).
     */
    public function up(): void
    {
        Schema::table('customer_billing_intervals', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->nullable()->after('interval');
        });
    }

    public function down(): void
    {
        Schema::table('customer_billing_intervals', function (Blueprint $table) {
            $table->dropColumn('discount_percent');
        });
    }
};
