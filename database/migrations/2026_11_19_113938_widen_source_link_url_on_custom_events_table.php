<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Die alte Einzelspalte der ersten Quelle war auf 255 Zeichen begrenzt, erlaubt
 * sind aber Adressen bis 2048 Zeichen. Kodierte Links (z. B. mit kyrillischen
 * Zeichen) liessen das Speichern eines Ereignisses scheitern.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->text('source_link_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->string('source_link_url', 255)->nullable()->change();
        });
    }
};
