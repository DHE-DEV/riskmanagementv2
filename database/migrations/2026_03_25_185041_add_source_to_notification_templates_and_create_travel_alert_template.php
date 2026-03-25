<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->string('source', 50)->nullable()->after('customer_id');
            $table->index('source');
        });

        // Bestehende System-Vorlage als GTM markieren
        DB::table('notification_templates')
            ->where('is_system', true)
            ->update(['source' => 'global-travel-monitor']);

        // Neue System-Vorlage für Travel Alert
        DB::table('notification_templates')->insert([
            'customer_id' => null,
            'source' => 'travel-alert',
            'name' => 'Standard-Vorlage (Travel Alert)',
            'subject' => 'Neue Ereignisse im Travel Alert',
            'body_html' => '<div>Sie haben ein E-Mail Abo im Travel Alert abgeschlossen.</div><div><br></div><div>Es sind neue Informationen vorhanden.</div><div><br></div><div><b>Datum</b>: {event_date}</div><div><b>Risikostufe</b>: {risk_level}</div><div><b>Kategorie</b>: {category}</div><div><b>Betroffene Länder</b>: {country_name}</div><div><br></div><div>{event_title}<br><br></div><div>{description}</div>{affected_trips}',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('notification_templates')
            ->where('is_system', true)
            ->where('source', 'travel-alert')
            ->delete();

        DB::table('notification_templates')
            ->where('is_system', true)
            ->update(['source' => null]);

        Schema::table('notification_templates', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn('source');
        });
    }
};
