<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('notification_templates')
            ->where('is_system', true)
            ->update([
                'subject' => 'Neue Ereignisse im Global Travel Monitor',
                'body_html' => '<div>Sie haben Ein E-Mail Abo im Global Travel Monitor abgeschlossen.</div><div><br></div><div>Es sind neue Informationen vorhanden.</div><div><br></div><div><b>Datum</b>: {event_date}</div><div><b>Risikostufe</b>: {risk_level}</div><div><b>Kategorie</b>: {category}</div><div><b>Betroffene Länder</b>: {country_name}</div><div><br></div><div>{event_title}<br><br></div><div>{description}</div>',
            ]);
    }

    public function down(): void
    {
        DB::table('notification_templates')
            ->where('is_system', true)
            ->update([
                'subject' => 'Reisewarnung: {event_title} - {country_name}',
                'body_html' => '<h2>Reisewarnung: {event_title}</h2><p><strong>Land:</strong> {country_name}</p><p><strong>Risikostufe:</strong> {risk_level}</p><p><strong>Kategorie:</strong> {category}</p><p><strong>Datum:</strong> {event_date}</p><hr><p>{description}</p><hr><p style="color: #666; font-size: 12px;">Diese Benachrichtigung wurde automatisch vom Global Travel Monitor gesendet.</p>',
            ]);
    }
};
