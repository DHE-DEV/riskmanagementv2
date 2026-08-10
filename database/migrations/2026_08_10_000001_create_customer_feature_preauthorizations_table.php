<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vormerkungen fuer Feature-Freischaltungen, die noch keinen Kunden haben.
 *
 * customer_feature_overrides haengt an customer_id und setzt damit voraus, dass
 * sich der Account schon einmal eingeloggt hat. Diese Tabelle merkt die
 * Freischaltung stattdessen an der pds_account_id vor; beim ersten Login wird
 * sie in ein Override uebersetzt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_feature_preauthorizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pds_account_id');
            $table->string('feature_key', 64);

            // false merkt eine Sperre vor - dieselbe Semantik wie im Override.
            $table->boolean('enabled')->default(true);
            $table->string('note')->nullable();

            // Wann die Vormerkung erstmals in ein Override uebersetzt wurde.
            // Bleibt gesetzt, auch wenn der Kunde spaeter wieder geloescht wird.
            $table->timestamp('applied_at')->nullable();
            $table->unsignedBigInteger('applied_customer_id')->nullable();

            $table->timestamps();

            $table->unique(['pds_account_id', 'feature_key'], 'uq_preauth_account_feature');
            $table->index('feature_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_feature_preauthorizations');
    }
};
