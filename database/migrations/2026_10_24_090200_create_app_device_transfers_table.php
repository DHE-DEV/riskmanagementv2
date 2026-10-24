<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uebertragung auf ein neues Geraet: die neue Installation fragt an, das alte Geraet bestaetigt, danach uebernimmt
 * die neue Installation Identitaet und Sicherung des alten Geraets (POST /v1/devices/{id}/transfers).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_device_transfers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // das ALTE Geraet, dessen Daten uebernommen werden
            $table->foreignUlid('from_device_id')->constrained('app_devices')->cascadeOnDelete();
            // das NEUE, anfragende Geraet – ohne Fremdschluessel: der Eintrag wird bei der Uebernahme geloescht,
            // die Anfrage muss danach fuer den erneuten Abruf erhalten bleiben.
            $table->ulid('to_device_id')->index();
            // Hash des Secrets der neuen Installation (fuer den erneuten Abruf nach der Uebernahme)
            $table->string('to_secret_hash', 64);
            $table->string('to_platform', 16);
            $table->string('to_label', 80)->nullable();
            // pending | approved | declined | completed | expired | cancelled
            $table->string('status', 16)->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            // neues Secret, verschluesselt, nur kurz nach dem Abschluss (verlorene Antwort)
            $table->text('secret_encrypted')->nullable();
            $table->timestamps();

            $table->index(['from_device_id', 'status']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_device_transfers');
    }
};
