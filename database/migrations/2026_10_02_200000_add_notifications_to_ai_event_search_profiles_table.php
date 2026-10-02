<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wer nach einem Lauf einer hinterlegten KI-Suche per E-Mail vom Ergebnis
     * erfaehrt: beliebig viele Benutzer und Teams. Dazu die Hoechstzahl der
     * Ergebnisse je Lauf.
     */
    public function up(): void
    {
        Schema::table('ai_event_search_profiles', function (Blueprint $table) {
            $table->json('notify_user_ids')->nullable()->after('times');
            $table->json('notify_team_ids')->nullable()->after('notify_user_ids');
            // Auch dann eine Mail, wenn der Lauf nichts Neues gefunden hat.
            $table->boolean('notify_when_empty')->default(false)->after('notify_team_ids');
            // Hoechstzahl der Ergebnisse je Lauf; null = der Standard aus System > KI.
            $table->unsignedTinyInteger('max_results')->nullable()->after('days_ahead');
        });

        Schema::table('ai_event_searches', function (Blueprint $table) {
            // Wann die Ergebnis-Mail verschickt wurde – verhindert doppelte Mails.
            $table->dateTime('notified_at')->nullable()->after('finished_at');
            // Mit welcher Hoechstzahl an Ergebnissen der Lauf gesucht hat.
            $table->unsignedTinyInteger('max_results')->nullable()->after('prompt');
        });
    }

    public function down(): void
    {
        Schema::table('ai_event_search_profiles', function (Blueprint $table) {
            $table->dropColumn(['notify_user_ids', 'notify_team_ids', 'notify_when_empty', 'max_results']);
        });

        Schema::table('ai_event_searches', function (Blueprint $table) {
            $table->dropColumn(['notified_at', 'max_results']);
        });
    }
};
