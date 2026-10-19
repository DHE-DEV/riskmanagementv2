<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\LanguagePhrase;
use App\Services\ChatGptService;
use App\Support\Phrases\PhraseCatalog;
use Illuminate\Console\Command;

/**
 * Uebersetzt die Saetze aus PhraseCatalog per KI in alle Sprachen der Laender (countries.languages) bzw. die
 * angegebenen. Ergebnis als KI-Entwurf in language_phrases; vorhandene Saetze bleiben (--force ersetzt Entwuerfe).
 * Mit --export schreibt es alles nach database/data/phrases.json (fuer die Migration).
 */
class GeneratePhrases extends Command
{
    protected $signature = 'phrases:generate {--lang=* : Sprachcodes, sonst alle der Laender} {--force : vorhandene KI-Entwuerfe neu erzeugen} {--export : nur nach database/data/phrases.json schreiben}';

    protected $description = 'Wichtige Saetze fuer Reisende je Landessprache per KI erzeugen';

    public function handle(ChatGptService $ai): int
    {
        if ($this->option('export')) {
            $data = LanguagePhrase::orderBy('language')->orderBy('id')->get()->groupBy('language')
                ->map(fn ($rows) => $rows->mapWithKeys(fn ($p) => [$p->key => array_filter(['text' => $p->text, 'romanization' => $p->romanization])]))->all();
            file_put_contents(database_path('data/phrases.json'), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");
            $this->info(count($data).' Sprachen exportiert.');

            return self::SUCCESS;
        }

        $languages = $this->option('lang') ?: Country::whereNotNull('languages')->pluck('languages')->flatten()->unique()->sort()->values()->all();
        $todo = collect($languages)->filter(function ($lang) {
            $count = LanguagePhrase::where('language', $lang)->count();

            return $count < count(PhraseCatalog::PHRASES) || $this->option('force');
        })->values();
        $this->info($todo->count().' Sprachen zu erzeugen.');

        foreach ($todo->chunk(6) as $chunk) {
            $prompts = $chunk->mapWithKeys(fn ($lang) => [$lang => $this->prompt($lang)])->all();
            foreach ($ai->sendPrompts($prompts, ['timeout' => 180]) as $lang => $answer) {
                $json = json_decode(trim(preg_replace('/^```(json)?|```$/m', '', $answer)), true);
                if (! is_array($json)) {
                    $this->warn("{$lang}: keine gueltige Antwort");

                    continue;
                }
                $saved = 0;
                foreach (PhraseCatalog::PHRASES as $key => $_) {
                    $entry = $json[$key] ?? null;
                    if (! is_array($entry) || blank($entry['text'] ?? null)) {
                        continue;
                    }
                    $phrase = LanguagePhrase::firstOrNew(['language' => $lang, 'key' => $key]);
                    if ($phrase->exists && $phrase->status === LanguagePhrase::STATUS_REVIEWED) {
                        continue;
                    }
                    $phrase->fill([
                        'text' => mb_substr(trim($entry['text']), 0, 255),
                        'romanization' => filled($entry['romanization'] ?? null) ? mb_substr(trim($entry['romanization']), 0, 255) : null,
                        'status' => LanguagePhrase::STATUS_AI_DRAFT,
                    ])->save();
                    $saved++;
                }
                $this->line("{$lang}: {$saved} Saetze");
            }
        }

        return self::SUCCESS;
    }

    private function prompt(string $lang): string
    {
        $name = class_exists(\Locale::class) ? \Locale::getDisplayLanguage($lang, 'en') : $lang;
        $phrases = collect(PhraseCatalog::PHRASES)->map(fn ($p) => $p[2])->all();

        return "Translate the following short phrases for tourists from English into {$name} (ISO 639 code \"{$lang}\").\n"
            ."Use the natural, polite form a traveller would say to a local (e.g. formal 'you' where the language has one). "
            ."Keep the placeholder \"…\" exactly where the English has it. Numbers: write the number word.\n"
            ."If {$name} is not normally written in Latin script, add \"romanization\": a simple Latin pronunciation help "
            ."(standard romanization, e.g. Pinyin with tone marks, Hepburn, ALA-LC simplified); otherwise romanization is null.\n"
            ."Answer ONLY with a JSON object, keys exactly as given: {\"key\": {\"text\": \"…\", \"romanization\": null|\"…\"}}.\n\n"
            .json_encode($phrases, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
