<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Status "erledigt" im Travel Alert je Kunde – für Ereignisse (custom_event_id) und
     * Reisen (folder_id der Liste: lokale Ordner-ID oder "api-<tid>"). Fehlt die Zeile,
     * ist das Element offen.
     */
    public function up(): void
    {
        Schema::create('travel_alert_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('subject_type', 16);
            $table->string('subject_key', 128);
            $table->timestamp('done_at');
            $table->timestamps();

            $table->unique(['customer_id', 'subject_type', 'subject_key'], 'ta_statuses_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_alert_statuses');
    }
};
