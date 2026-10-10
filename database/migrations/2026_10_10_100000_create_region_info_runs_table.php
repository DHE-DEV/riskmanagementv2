<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KI-Laeufe der Regionsinfos: ein Vorschlag fuer eine Region im Editor
 * (kind "suggest") oder die Vorbefuellung vieler Regionen aus der Liste
 * (kind "fill"). Bewusst eine eigene Tabelle statt des Caches, damit der
 * Stand zuverlaessig zwischen Web-Anfragen und Queue-Jobs geteilt wird.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('region_info_runs', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);
            $table->foreignId('region_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('running');
            $table->json('pending')->nullable();
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('done')->default(0);
            $table->json('failed')->nullable();
            $table->boolean('overwrite')->default(false);
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['kind', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('region_info_runs');
    }
};
