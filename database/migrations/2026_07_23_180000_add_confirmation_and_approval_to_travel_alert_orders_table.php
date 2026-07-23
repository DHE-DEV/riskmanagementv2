<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Double-Opt-in fuer Travel Alert-Bestellungen.
 *
 * Bisher wurde der Zugang direkt beim Absenden des Formulars freigeschaltet.
 * Jetzt bestaetigt der Kunde die Bestellung erst per Mail; ob danach sofort
 * freigeschaltet wird oder ein Mitarbeiter zustimmen muss, entscheidet
 * TRAVEL_ALERT_AUTO_ACTIVATION.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_alert_orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('id')
                ->constrained('customers')->nullOnDelete();
            $table->string('confirmation_token', 64)->nullable()->unique()->after('remarks');
            $table->timestamp('confirmed_at')->nullable()->after('confirmation_token');
            $table->timestamp('approved_at')->nullable()->after('confirmed_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->after('rejected_at')
                ->constrained('users')->nullOnDelete();
        });

        // Bestandsdaten: alles, was vor dieser Umstellung bestellt wurde, war
        // bereits freigeschaltet. Diese Bestellungen gelten als bestaetigt und
        // genehmigt, damit sie nicht ploetzlich als offen in der Liste stehen.
        DB::table('travel_alert_orders')->update([
            'confirmed_at' => DB::raw('created_at'),
            'approved_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('travel_alert_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['confirmation_token', 'confirmed_at', 'approved_at', 'rejected_at']);
        });
    }
};
