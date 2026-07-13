<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persistenter Wasserstand fuer den account-uebergreifenden Delta-Sync.
 * Haelt den Keyset-Cursor (last_change_at, id), ab dem beim naechsten Lauf
 * weitergezogen wird.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pds_global_sync_states', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->dateTime('cursor_last_change_at')->nullable();
            $table->unsignedBigInteger('cursor_id')->nullable();
            $table->dateTime('last_run_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pds_global_sync_states');
    }
};
