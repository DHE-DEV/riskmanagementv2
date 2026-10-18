<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Einladungslinks der TripData-App (https://platform.passolution.de/einladung/{code}): zu einer geteilten Reise
 * oder einer Gruppe. Wer den Link oeffnet und annimmt, wird Empfaenger bzw. Mitglied – ohne App-ID vorher zu kennen.
 * Je Reise bzw. Gruppe gibt es einen gueltigen Link (30 Tage); der Ersteller kann ihn erneuern.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_invites', function (Blueprint $table) {
            $table->id();
            // 8 Zeichen ohne Verwechsler, angezeigt als XXXX-XXXX
            $table->char('code', 8)->unique();
            $table->foreignUlid('created_by_device_id')->constrained('app_devices')->cascadeOnDelete();
            $table->foreignUlid('app_shared_trip_id')->nullable()->constrained('app_shared_trips')->cascadeOnDelete();
            $table->foreignUlid('app_group_id')->nullable()->constrained('app_groups')->cascadeOnDelete();
            $table->unsignedInteger('uses')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_invites');
    }
};
