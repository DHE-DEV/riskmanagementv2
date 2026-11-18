<?php

use App\Models\CustomEvent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Übersetzungsstand je Ereignis:
 * - translation_meta: je Sprache, aus welchem Stand der Ausgangssprache übersetzt wurde
 *   und wie das Ergebnis aussah ({"en": {"source": "<sha1>", "result": "<sha1>", "at": "…"}}),
 * - translation_status: complete | missing | stale – für Filter und Markierungen im Admin.
 *
 * Vorhandene Übersetzungen gelten als aktuell (Stand: sie wurden gerade per events:translate erstellt).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->json('translation_meta')->nullable()->after('popup_content_translations');
            $table->string('translation_status', 20)->nullable()->after('translation_meta')->index();
        });

        CustomEvent::query()->withoutGlobalScopes()->chunkById(200, function ($events) {
            foreach ($events as $event) {
                $event->translation_meta = $event->baselineTranslationMeta();
                $event->translation_status = $event->computeTranslationStatus();
                $event->timestamps = false;
                $event->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->dropIndex(['translation_status']);
            $table->dropColumn(['translation_meta', 'translation_status']);
        });
    }
};
