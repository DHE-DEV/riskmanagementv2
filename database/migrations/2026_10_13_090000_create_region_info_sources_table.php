<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quellen der KI-Recherche (Websuche) zu Regionen und Teilgebieten – nur zum
 * Nachsehen in der Admin v2, nie in der App oder API geladen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('region_info_sources', function (Blueprint $table) {
            $table->id();
            $table->morphs('sourceable');
            $table->string('field', 64)->nullable();
            $table->string('url', 2048);
            $table->string('domain')->nullable();
            $table->string('title')->nullable();
            $table->boolean('cited')->default(false);
            $table->foreignId('region_info_run_id')->nullable()->constrained()->nullOnDelete();
            $table->string('model')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('region_info_sources');
    }
};
