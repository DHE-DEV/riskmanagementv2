<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reiseverlauf einer geteilten Reise: Aufenthaltsorte (wann man wo ist, Seetage, Zeiten, Notiz) und Notizen je Tag,
 * je Eintrag zusammengefuehrt wie die Reisekasse (id bzw. Datum, updated_at, deleted). Alle Teilnehmer duerfen
 * bearbeiten. Dazu je Geraet der Schalter fuer sichtbare Pushs bei Aenderungen am Reiseverlauf.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_shared_trips', function (Blueprint $table) {
            $table->json('stays')->nullable()->after('expenses');
            $table->json('day_notes')->nullable()->after('stays');
        });
        Schema::table('app_devices', function (Blueprint $table) {
            $table->boolean('notify_itinerary')->default(true)->after('notifications_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('app_shared_trips', function (Blueprint $table) {
            $table->dropColumn(['stays', 'day_notes']);
        });
        Schema::table('app_devices', function (Blueprint $table) {
            $table->dropColumn('notify_itinerary');
        });
    }
};
