<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Festbetrag der Kooperation: je Buero (per_office) oder als Pauschale fuer
     * alle Mitglieder zusammen (flat), jeweils pro Monat.
     */
    public function up(): void
    {
        Schema::table('cooperations', function (Blueprint $table) {
            $table->string('fixed_price_mode', 20)->default('per_office')->after('fixed_price_per_office');
        });
    }

    public function down(): void
    {
        Schema::table('cooperations', function (Blueprint $table) {
            $table->dropColumn('fixed_price_mode');
        });
    }
};
