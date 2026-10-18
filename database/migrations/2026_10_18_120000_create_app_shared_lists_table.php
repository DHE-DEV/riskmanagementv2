<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Geteilte Packliste einer geteilten Reise (Teilen in der TripData-App): eine gemeinsame Liste fuer alle,
 * die die Reise sehen. Der Ersteller legt fest, wer nur ansehen, abhaken, bearbeiten oder verwalten darf.
 * Koffer und Gegenstaende liegen als JSON vor; jeder Eintrag traegt eigene Zeitstempel (Aenderung, Haken)
 * und ein Loeschkennzeichen, damit Aenderungen mehrerer Geraete zusammengefuehrt werden koennen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_shared_lists', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('app_shared_trip_id')->unique()->constrained('app_shared_trips')->cascadeOnDelete();
            // ID der Packliste in der App des Erstellers (UUID).
            $table->string('client_list_id', 64);
            $table->string('name', 120);
            $table->json('suitcases');
            $table->json('items');
            // view | check | edit | manage – fuer alle, die keine eigene Rolle haben
            $table->string('default_role', 8)->default('check');
            // {device_id: role}
            $table->json('roles')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_shared_lists');
    }
};
