<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Revisionssichere Versionierung der Ereignisse.
 *
 * Ein Ereignis wird nie ueberschrieben: statt dessen entsteht ueber "Duplizieren"
 * eine neue Zeile mit derselben version_group_uuid und der naechsten Versionsnummer.
 * Sobald die neue Version aktiviert wird, wird die vorherige deaktiviert und mit
 * superseded_by_id/superseded_at als abgeloest markiert - sie bleibt aber dauerhaft
 * lesbar (Admin und Kunden-Versionshistorie).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            // Fortlaufende Nummer innerhalb einer Versionsgruppe (1, 2, 3, ...).
            $table->unsignedInteger('version')->default(1)->after('uuid');

            // Klammert alle Versionen desselben Ereignisses zusammen.
            $table->char('version_group_uuid', 36)->nullable()->after('version');

            // Aus welcher Version wurde dupliziert.
            $table->unsignedBigInteger('version_parent_id')->nullable()->after('version_group_uuid');

            // Welche Version hat diese hier abgeloest (NULL = aktuellste Version).
            $table->unsignedBigInteger('superseded_by_id')->nullable()->after('version_parent_id');
            $table->timestamp('superseded_at')->nullable()->after('superseded_by_id');

            // Zeitpunkt der (erneuten) Aktivierung - Grundlage fuer den
            // Benachrichtigungslauf, damit auch spaet freigeschaltete Versionen
            // noch versendet werden.
            $table->timestamp('activated_at')->nullable()->after('superseded_at');

            // Freitext: Was wurde gegenueber der Vorversion geaendert.
            $table->text('version_note')->nullable()->after('activated_at');

            $table->index('version_group_uuid');
            $table->index('version_parent_id');
            $table->index('superseded_by_id');
            $table->index('activated_at');
        });

        // Bestandsdaten: jedes vorhandene Ereignis ist Version 1 seiner eigenen Gruppe.
        DB::statement('UPDATE custom_events SET version_group_uuid = COALESCE(uuid, UUID()) WHERE version_group_uuid IS NULL');
        DB::statement('UPDATE custom_events SET activated_at = created_at WHERE is_active = 1 AND activated_at IS NULL');
    }

    public function down(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->dropIndex(['version_group_uuid']);
            $table->dropIndex(['version_parent_id']);
            $table->dropIndex(['superseded_by_id']);
            $table->dropIndex(['activated_at']);

            $table->dropColumn([
                'version',
                'version_group_uuid',
                'version_parent_id',
                'superseded_by_id',
                'superseded_at',
                'activated_at',
                'version_note',
            ]);
        });
    }
};
