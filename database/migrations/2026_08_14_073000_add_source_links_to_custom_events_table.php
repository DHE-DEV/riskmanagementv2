<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mehrere Quellen-Links je Event: source_show_frontend/source_link_text/source_link_url
     * werden zu einer Liste. Die alten Einzelspalten bleiben erhalten und werden vom
     * Model aus dem ersten Eintrag weitergepflegt (Rueckwaertskompatibilitaet fuer die API).
     */
    public function up(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->json('source_links')->nullable()->after('source_link_url');
        });

        // Bestehende Einzelangaben als ersten Listeneintrag uebernehmen.
        DB::table('custom_events')
            ->select('id', 'source_show_frontend', 'source_link_text', 'source_link_url')
            ->where(function ($query) {
                $query->whereNotNull('source_link_text')
                    ->orWhereNotNull('source_link_url');
            })
            ->orderBy('id')
            ->chunkById(500, function ($events) {
                foreach ($events as $event) {
                    DB::table('custom_events')
                        ->where('id', $event->id)
                        ->update([
                            'source_links' => json_encode([[
                                'show_frontend' => (bool) ($event->source_show_frontend ?? true),
                                'link_text' => $event->source_link_text,
                                'link_url' => $event->source_link_url,
                            ]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->dropColumn('source_links');
        });
    }
};
