<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ergebnisse der KI-Pruefung von Quellen: Stimmt der erfasste Stand eines
     * Ereignisses noch mit dem ueberein, was die Quelle heute sagt?
     *
     * Jede Pruefung ist eine eigene Zeile – so bleibt nachvollziehbar, wann
     * eine Quelle zuletzt geprueft wurde und was dabei herauskam.
     */
    public function up(): void
    {
        Schema::create('custom_event_source_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_event_id')->constrained('custom_events')->cascadeOnDelete();
            $table->text('url');
            // sha256 der URL: zum Wiederfinden der letzten Pruefung einer Quelle.
            $table->char('url_hash', 64);
            // unchanged | changed | unclear | error
            $table->string('status', 20);
            $table->text('summary')->nullable();
            $table->json('changes')->nullable();
            $table->text('suggestion')->nullable();
            // Verbesserungsvorschlaege je Feld: [{field, label, display, value, reason}, ...]
            $table->json('proposals')->nullable();
            // Verbrauch der KI-Anfrage; Kosten in US-Dollar, sofern ein Preis hinterlegt war.
            $table->string('model', 100)->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->decimal('cost', 12, 6)->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['custom_event_id', 'url_hash', 'created_at'], 'event_source_checks_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_event_source_checks');
    }
};
