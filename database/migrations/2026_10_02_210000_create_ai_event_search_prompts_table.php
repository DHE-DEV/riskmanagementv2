<?php

use App\Support\AiSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * KI-Vorlagen: die Auftraege (Prompts) fuer die KI-Suche nach Ereignissen,
     * beliebig viele, eine davon als Standard. Hinterlegte Suchen waehlen eine
     * Vorlage; ohne Auswahl gilt der Standard.
     *
     * Der bisherige Auftrag aus den Einstellungen wird zur Standard-Vorlage,
     * eigene Auftraege hinterlegter Suchen werden zu eigenen Vorlagen.
     */
    public function up(): void
    {
        Schema::create('ai_event_search_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('prompt');
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();
        $stored = DB::table('system_settings')->where('key', 'ai.event_search.prompt')->value('value');

        DB::table('ai_event_search_prompts')->insert([
            'name' => 'Standard',
            'prompt' => filled($stored) ? trim((string) $stored) : AiSettings::DEFAULT_EVENT_SEARCH_PROMPT,
            'is_default' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Schema::table('ai_event_search_profiles', function (Blueprint $table) {
            // Gewaehlte Vorlage; null = die Standard-Vorlage.
            $table->foreignId('prompt_id')->nullable()->after('name')->constrained('ai_event_search_prompts')->nullOnDelete();
        });

        // Eigene Auftraege bestehender Suchen als Vorlagen uebernehmen.
        foreach (DB::table('ai_event_search_profiles')->whereNotNull('prompt')->where('prompt', '!=', '')->get() as $profile) {
            $promptId = DB::table('ai_event_search_prompts')->insertGetId([
                'name' => 'Auftrag von „'.$profile->name.'“',
                'prompt' => $profile->prompt,
                'is_default' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('ai_event_search_profiles')->where('id', $profile->id)->update(['prompt_id' => $promptId]);
        }

        Schema::table('ai_event_search_profiles', function (Blueprint $table) {
            $table->dropColumn('prompt');
        });

        Schema::table('ai_event_searches', function (Blueprint $table) {
            // Name der Vorlage, mit der der Lauf gesucht hat (der Text steht in "prompt").
            $table->string('prompt_name')->nullable()->after('prompt');
        });

        // Gesucht wird nur noch ueber hinterlegte Suchen – eine allgemeine gibt es von Anfang an.
        if (DB::table('ai_event_search_profiles')->count() === 0) {
            DB::table('ai_event_search_profiles')->insert([
                'name' => 'Allgemeine Suche',
                'exclude_existing' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('ai_event_searches', function (Blueprint $table) {
            $table->dropColumn('prompt_name');
        });

        Schema::table('ai_event_search_profiles', function (Blueprint $table) {
            $table->text('prompt')->nullable()->after('name');
        });

        foreach (DB::table('ai_event_search_profiles')->whereNotNull('prompt_id')->get() as $profile) {
            DB::table('ai_event_search_profiles')->where('id', $profile->id)->update([
                'prompt' => DB::table('ai_event_search_prompts')->where('id', $profile->prompt_id)->value('prompt'),
            ]);
        }

        Schema::table('ai_event_search_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prompt_id');
        });

        Schema::dropIfExists('ai_event_search_prompts');
    }
};
