<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->json('title_translations')->nullable()->after('title');
            $table->json('popup_content_translations')->nullable()->after('popup_content');
        });

        // Bestehende Inhalte als Ausgangssprache in die JSON-Spalten übernehmen.
        $source = config('app.event_source_language', 'de');

        DB::table('custom_events')
            ->select('id', 'title', 'popup_content')
            ->orderBy('id')
            ->chunkById(500, function ($events) use ($source) {
                foreach ($events as $event) {
                    DB::table('custom_events')
                        ->where('id', $event->id)
                        ->update([
                            'title_translations' => json_encode(
                                [$source => (string) $event->title],
                                JSON_UNESCAPED_UNICODE
                            ),
                            'popup_content_translations' => $event->popup_content !== null
                                ? json_encode([$source => $event->popup_content], JSON_UNESCAPED_UNICODE)
                                : null,
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->dropColumn(['title_translations', 'popup_content_translations']);
        });
    }
};
