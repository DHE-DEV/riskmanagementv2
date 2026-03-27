<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('notification_templates')
            ->where('is_system', true)
            ->where('source', 'travel-alert')
            ->update([
                'subject' => 'Travel Alert: Neue Ereignisbenachrichtigung',
                'body_html' => '<div>Guten Tag,<br><br>gemäß Ihren Benachrichtigungseinstellungen im Travel Alert der Passolution Travel Information Platform informieren wir Sie über ein neues Ereignis, dass Reisen betrifft.:<br><br><b>Land / Region:</b>&nbsp;{country_name}<br><b>Datum:</b>&nbsp;{event_date}<br><b>Kategorie:</b>&nbsp;{category}<br><b>Risikostufe:</b>&nbsp;{risk_level}<br><b>Anzahl der betroffenen Reisen:</b> {affected_trips_count}<br><br><b>Titel:</b><br>{event_title}<br><br><b>Beschreibung:</b>&nbsp;<br>{description}<br><br><b>Betroffene Reisen:</b><br><br>{affected_trips}<br><br><hr><br><b>Warum erhalten Sie diese E-Mail?</b><br>Sie haben eine Benachrichtigungsregel erstellt, die auf dieses Ereignis zutrifft.<br><br>Weitere Details finden Sie in Ihrem Global Travel Monitor. (Link zum GTM)<br><br>Mit freundlichen Grüßen<br>Ihr Passolution Team</div>',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('notification_templates')
            ->where('is_system', true)
            ->where('source', 'travel-alert')
            ->update([
                'subject' => 'Neue Ereignisse im Travel Alert',
                'body_html' => '<div>Sie haben ein E-Mail Abo im Travel Alert abgeschlossen.</div><div><br></div><div>Es sind neue Informationen vorhanden.</div><div><br></div><div><b>Datum</b>: {event_date}</div><div><b>Risikostufe</b>: {risk_level}</div><div><b>Kategorie</b>: {category}</div><div><b>Betroffene Länder</b>: {country_name}</div><div><br></div><div>{event_title}<br><br></div><div>{description}</div>{affected_trips}',
                'updated_at' => now(),
            ]);
    }
};
