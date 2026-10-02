<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "archived" war bei neu angelegten Ereignissen leer (NULL), wenn es nicht
     * ausdruecklich gesetzt wurde. Die Kunden-Ansicht fragt aber nach
     * archived = false – solche Ereignisse blieben dort unsichtbar.
     *
     * Leere Werte werden nachgezogen; kuenftig ist die Spalte nie mehr leer.
     */
    public function up(): void
    {
        DB::table('custom_events')->whereNull('archived')->update(['archived' => false]);

        Schema::table('custom_events', function (Blueprint $table) {
            $table->boolean('archived')->default(false)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->boolean('archived')->nullable()->default(null)->change();
        });
    }
};
