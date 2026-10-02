<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * KI-Suche nach aktuellen Ereignissen: jeder Suchlauf und die Themen, die
     * er vorgeschlagen hat. Aus einem Vorschlag kann ein Ereignis als Entwurf
     * entstehen.
     */
    public function up(): void
    {
        Schema::create('ai_event_searches', function (Blueprint $table) {
            $table->id();
            // running | done | failed
            $table->string('status', 20)->default('running')->index();
            $table->boolean('exclude_existing')->default(true);
            // Gezielte Suche: die Filter der Ereignisliste, mit denen gesucht wurde (null = allgemeine Suche).
            $table->json('filters')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('found_count')->default(0);
            $table->unsignedInteger('new_count')->default(0);
            // Verbrauch der KI-Anfrage; Kosten in US-Dollar, sofern ein Preis hinterlegt war.
            $table->string('model', 100)->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->decimal('cost', 12, 6)->nullable();
            $table->text('error')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_event_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('search_id')->nullable()->constrained('ai_event_searches')->nullOnDelete();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->string('priority', 20)->default('medium');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            // Codes der Event-Typen und ISO-Codes der Laender, wie von der KI vorgeschlagen.
            $table->json('event_type_codes')->nullable();
            $table->json('country_codes')->nullable();
            $table->string('location')->nullable();
            // [{title, url}, …] – alle Quellen zum selben Ereignis stehen an EINEM Vorschlag.
            $table->json('sources')->nullable();
            // new | dismissed | converted
            $table->string('status', 20)->default('new')->index();
            $table->foreignId('custom_event_id')->nullable()->constrained('custom_events')->nullOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_event_suggestions');
        Schema::dropIfExists('ai_event_searches');
    }
};
