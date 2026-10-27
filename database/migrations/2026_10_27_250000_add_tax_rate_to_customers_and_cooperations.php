<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Umsatzsteuer auf die netto erfassten Betraege – je Kunde und je
     * Kooperation, 0 % oder 19 % (Standard).
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(19)->after('invoice_emails');
        });

        Schema::table('cooperations', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(19)->after('kickback_value');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('tax_rate');
        });

        Schema::table('cooperations', function (Blueprint $table) {
            $table->dropColumn('tax_rate');
        });
    }
};
