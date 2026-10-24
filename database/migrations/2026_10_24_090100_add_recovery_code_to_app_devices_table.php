<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wiederherstellungscode je Geraet (POST /v1/devices/{id}/recovery-code): Mit App-ID und Code uebernimmt eine
 * neue Installation das Geraet samt Sicherung, auch ohne das alte Geraet. Gespeichert wird nur der Hash.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_devices', function (Blueprint $table) {
            $table->string('recovery_hash', 64)->nullable()->after('secret_hash');
            $table->timestamp('recovery_created_at')->nullable()->after('recovery_hash');
        });
    }

    public function down(): void
    {
        Schema::table('app_devices', function (Blueprint $table) {
            $table->dropColumn(['recovery_hash', 'recovery_created_at']);
        });
    }
};
