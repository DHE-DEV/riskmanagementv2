<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Protokoll der Stammdaten-Aenderungen im Admin: wer wann welchen Eintrag
 * angelegt, geaendert, geloescht oder wiederhergestellt hat – Grundlage fuer
 * die Statistik je Mitarbeiter.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_data_changes', function (Blueprint $table) {
            $table->id();
            $table->string('model_type', 80);
            $table->unsignedBigInteger('model_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 20);
            /** Geaenderte Felder bzw. Bereiche */
            $table->json('changes')->nullable();
            $table->timestamp('created_at');

            $table->index(['model_type', 'created_at']);
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_data_changes');
    }
};
