<?php

namespace App\Services;

use App\Models\CustomEvent;
use App\Models\Region;
use App\Models\Subregion;
use App\Services\Concerns\AsksChatGpt;
use App\Support\AdminV2\RegionInfo;
use App\Support\AdminV2\SubregionInfo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Regionsinfos per KI vorbefuellen: ein Prompt je Region, Antwort als JSON
 * mit allen Textfeldern in allen Sprachen. Gefuellt wird nur, was fuer die
 * Region eigen ist – Landesweites bleibt am Land. Teilgebiete (Inseln,
 * Reisegebiete) bekommen dieselben Felder, bezogen auf das Teilgebiet.
 */
class RegionInfoGenerator
{
    use AsksChatGpt;

    /** So viele Regionen fragt ein Durchgang gleichzeitig an. */
    public const PARALLEL = 4;

    public function __construct(protected ?ChatGptService $chat = null) {}

    protected function chat(): ChatGptService
    {
        return $this->chat ??= app(ChatGptService::class);
    }

    /**
     * Vorschlag fuer eine Region (noch nicht gespeichert).
     *
     * @return array<string, mixed>
     */
    public function suggest(Region|Subregion $region): array
    {
        return $this->parse($this->chat()->sendPrompt($this->prompt($region), ['timeout' => 180, 'max_tokens' => 6000]), $region);
    }

    /**
     * Mehrere Regionen vorbefuellen und speichern. Faellt die gemeinsame
     * Anfrage aus, wird jede Region einzeln versucht, damit ein Fehler nicht
     * alle anderen mitreisst.
     *
     * @param  Collection<int, Region|Subregion>  $regions
     * @return array{done: array<int, int>, failed: array<int, string>, usage: ?array}
     */
    public function fill(Collection $regions, bool $overwrite = false): array
    {
        $done = [];
        ['answers' => $answers, 'failed' => $failed, 'usage' => $usage] = $this->ask(
            $regions->mapWithKeys(fn (Region|Subregion $region) => [$region->id => $this->prompt($region)])->all(),
        );

        foreach ($regions as $region) {
            if (! isset($answers[$region->id])) {
                continue;
            }

            try {
                $this->store($region, $this->parse($answers[$region->id], $region), $overwrite);
                $done[] = $region->id;
            } catch (Throwable $e) {
                $failed[$region->id] = $this->shortError($e);
            }
        }

        return ['done' => $done, 'failed' => $failed, 'usage' => $usage];
    }

    /**
     * Nur die Luecken fuellen: Die KI bekommt das Vorhandene als Zusammenhang
     * und liefert nur fehlende Felder bzw. fehlende Sprachen. Was sie nicht
     * sicher weiss, laesst sie weg – das ist kein Fehler. Mit $web darf sie
     * dafuer im Internet suchen (langsamer, Anfragen nacheinander).
     *
     * @param  Collection<int, Region|Subregion>  $targets  alle vom selben Typ
     * @return array{done: array<int, int>, changed: array<int, int>, failed: array<int, string>, usage: ?array}
     */
    public function fillMissing(Collection $targets, bool $web = false): array
    {
        $done = [];
        $changed = [];
        ['answers' => $answers, 'failed' => $failed, 'usage' => $usage, 'sources' => $sources] = $this->ask(
            $targets->mapWithKeys(fn (Region|Subregion $target) => [$target->id => $this->prompt($target, $this->missingFor($target), $web)])->all(),
            $web,
        );

        foreach ($targets as $target) {
            if (! isset($answers[$target->id])) {
                continue;
            }

            try {
                $suggestion = $this->parse($this->stripCitations($answers[$target->id]), $target, allowEmpty: true);
                unset($suggestion['timezone']);

                if ($this->store($target, $suggestion)) {
                    $changed[] = $target->id;
                    $this->storeSources($target, $sources[$target->id] ?? [], $answers[$target->id]);
                }
                $done[] = $target->id;
            } catch (Throwable $e) {
                $failed[$target->id] = $this->shortError($e);
            }
        }

        return ['done' => $done, 'changed' => $changed, 'failed' => $failed, 'usage' => $usage];
    }

    /**
     * Luecken einer Region bzw. eines Teilgebiets (RegionInfo::missing).
     *
     * @return array{texts: array<string, array<int, string>>, facts: array<int, string>}
     */
    public function missingFor(Region|Subregion $target): array
    {
        $isArea = $target instanceof Subregion && SubregionInfo::kind($target->kind) === 'area';

        return RegionInfo::missing($target->info, ! $isArea);
    }

    /**
     * Vorschlag in die gespeicherten Angaben uebernehmen und als
     * ungepruefter KI-Entwurf kennzeichnen – aber nur, wenn wirklich etwas
     * dazukommt. Ergaenzt die KI vorhandene Angaben, merkt sich meta, welche
     * Felder neu sind (ai_filled) und wann zuvor geprueft wurde.
     *
     * @param  array<string, mixed>  $suggestion
     * @return bool ob sich etwas geaendert hat
     */
    public function store(Region|Subregion $region, array $suggestion, bool $overwrite = false): bool
    {
        $info = RegionInfo::applyAi($region->info, $suggestion, (string) ($this->chat()->lastUsage()['model'] ?? ''), $overwrite, $region->country?->timezone);

        if ($info === null) {
            return false;
        }

        $region->forceFill(['info' => $info])->save();

        return true;
    }

    /**
     * @param  array{texts: array<string, array<int, string>>, facts: array<int, string>}|null  $missing  nur diese Luecken anfordern (RegionInfo::missing)
     * @param  bool  $web  die KI darf im Internet recherchieren
     */
    public function prompt(Region|Subregion $region, ?array $missing = null, bool $web = false): string
    {
        if ($region instanceof Subregion) {
            return $this->subregionPrompt($region, $missing, $web);
        }

        $region->loadMissing('country');
        $country = $region->country;
        $source = CustomEvent::sourceLocale();
        $locales = RegionInfo::locales();

        $cities = $region->cities()
            ->orderByDesc('is_regional_capital')->orderByDesc('is_major')->orderByDesc('population')
            ->limit(15)->get()
            ->map(fn ($city) => $city->getName('de').($city->is_regional_capital ? ' (Hauptstadt der Region)' : ''))
            ->implode(', ');

        $fields = $this->fieldLines($missing, fn (array $field) => $field[2]);

        $languages = implode(', ', array_map(fn ($locale) => $locale.' = '.CustomEvent::localeLabel($locale, false), $locales));

        $countryText = trim((string) ($country?->travel_info['texts']['short_description'][$source] ?? ''));

        $data = array_filter([
            'Region' => $region->getName('de').(($en = $region->name_translations['en'] ?? '') && $en !== $region->getName('de') ? " (englisch: {$en})" : ''),
            'Code' => $region->code,
            'Land' => $country?->getName('de'),
            'Kurzbeschreibung des Landes (steht bereits am Land, nicht wiederholen)' => $countryText,
            'Zeitzone des Landes' => $country?->timezone,
            'Mittelpunkt' => $region->lat !== null && $region->lng !== null ? $region->lat.', '.$region->lng : null,
            'Städte der Region' => $cities,
        ]);

        $context = collect($data)->map(fn ($value, $label) => "{$label}: {$value}")->implode("\n");
        $example = json_encode([
            'texts' => [
                'short_description' => array_fill_keys($locales, '…'),
                'known_for' => array_fill_keys($locales, ['…', '…']),
            ],
            'best_months' => [5, 6, 9],
            'population' => 1234567,
            'area_km2' => 12345,
            'timezone' => null,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $facts = $this->factLines([
            'best_months' => '- best_months: die besten Reisemonate als Zahlen 1–12 (leer lassen, wenn sie dem ganzen Land entsprechen oder unklar sind)',
            'population' => '- population: Einwohnerzahl der Region als ganze Zahl (nur wenn bekannt)',
            'area_km2' => '- area_km2: Fläche in km² als ganze Zahl (nur wenn bekannt)',
            'timezone' => '- timezone: IANA-Zeitzone der Region, NUR wenn sie von der des Landes abweicht, sonst null',
        ], $missing);
        $existing = $this->existingBlock($region, $missing);
        $research = $this->researchNote($web);

        return <<<PROMPT
Du schreibst Reiseinformationen für eine Region in einer Reise-App. Die App zeigt die Region unterhalb ihres Landes an; alles, was für das ganze Land gilt (Währung, Visum, Einreise, Steckdosen, Strom, Notrufnummern, Landessprache, allgemeine Landeskunde), steht dort bereits und darf hier NICHT wiederholt werden.

{$context}
{$existing}
Schreibe nur, was für diese Region eigen ist. Wenn es zu einem Feld nichts Regionsspezifisches gibt (z. B. kein abweichendes Klima, keine Besonderheiten), lass das Feld weg. Erfinde keine Fakten, Zahlen, Namen oder Verbindungen – wenn du dir nicht sicher bist, lass es weg. Kleine, touristisch wenig bekannte Regionen bekommen kurze, sachliche Texte. Sachlicher, freundlicher Ton, keine Werbesprache, keine Aufzählungszeichen, keine Überschriften, kein Markdown. Absätze in der Beschreibung durch eine Leerzeile trennen.{$research}

Felder (jeweils in allen Sprachen: {$languages}):
{$fields}
{$facts}
Antworte ausschließlich mit einem JSON-Objekt in genau dieser Form (ohne Erklärungen, ohne Codeblock):
{$example}
PROMPT;
    }

    /**
     * Antwort der KI -> Angaben im Format von regions.info (ohne meta).
     *
     * @return array<string, mixed>
     */
    public function parse(string $answer, Region|Subregion|null $region = null, bool $allowEmpty = false): array
    {
        $json = trim($answer);
        $json = (string) preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $json);
        $start = strpos($json, '{');
        $end = strrpos($json, '}');

        $data = $start !== false && $end !== false ? json_decode(substr($json, $start, $end - $start + 1), true) : null;

        if (! is_array($data)) {
            throw new RuntimeException('Die KI hat kein gültiges JSON geliefert.');
        }

        $form = RegionInfo::toForm(null);

        foreach (RegionInfo::allTexts() as $field => [, $kind]) {
            foreach (RegionInfo::locales() as $locale) {
                $value = $data['texts'][$field][$locale] ?? null;
                $form['texts'][$field][$locale] = $kind === 'tags'
                    ? (is_array($value) ? implode(', ', array_map('strval', $value)) : (string) $value)
                    : (is_string($value) ? mb_substr($value, 0, RegionInfo::limit($field)) : '');
            }
        }

        $form['best_months'] = (array) ($data['best_months'] ?? []);
        $form['population'] = $data['population'] ?? '';
        $form['area_km2'] = $data['area_km2'] ?? '';
        $timezone = is_string($data['timezone'] ?? null) ? trim($data['timezone']) : '';
        $form['timezone'] = in_array($timezone, \DateTimeZone::listIdentifiers(), true) ? $timezone : '';

        $info = RegionInfo::fromForm($form, $region?->country) ?? [];

        if (! RegionInfo::hasContent($info)) {
            if ($allowEmpty) {
                return [];
            }

            throw new RuntimeException('Die KI hat keine Angaben zur Region geliefert.');
        }

        return $info;
    }

    /**
     * Prompt fuer ein Teilgebiet: dieselben Felder wie bei einer Region,
     * bezogen auf die Insel bzw. das Reisegebiet. Was fuer die ganze Region
     * gilt, steht an der Region.
     */
    public function subregionPrompt(Subregion $subregion, ?array $missing = null, bool $web = false): string
    {
        $subregion->loadMissing(['region', 'country']);
        $region = $subregion->region;
        $country = $subregion->country;
        $source = CustomEvent::sourceLocale();
        $locales = RegionInfo::locales();
        $kind = SubregionInfo::kindLabel($subregion->kind);
        $isArea = SubregionInfo::kind($subregion->kind) === 'area';

        $cities = $subregion->cities()->orderByDesc('population')->limit(15)->get()->map(fn ($city) => $city->getName('de'))->implode(', ');
        $siblings = Subregion::query()->where('region_id', $subregion->region_id)->whereKeyNot($subregion->id)->get()->map(fn (Subregion $other) => $other->getName('de'))->implode(', ');

        $fields = $this->fieldLines($missing, fn (array $field) => str_replace('Region', $kind, $field[2]));

        $languages = implode(', ', array_map(fn ($locale) => $locale.' = '.CustomEvent::localeLabel($locale, false), $locales));

        $data = array_filter([
            $kind => $subregion->getName('de').(($en = $subregion->name_translations['en'] ?? '') && $en !== $subregion->getName('de') ? " (englisch: {$en})" : ''),
            'Gehört zur Region' => $region?->getName('de'),
            'Kurzbeschreibung der Region (steht bereits an der Region, nicht wiederholen)' => trim((string) ($region?->info['texts']['short_description'][$source] ?? '')),
            'Land' => $country?->getName('de'),
            'Zeitzone des Landes' => $country?->timezone,
            'Mittelpunkt' => $subregion->lat !== null && $subregion->lng !== null ? $subregion->lat.', '.$subregion->lng : null,
            'Weitere Teilgebiete der Region' => $siblings,
            'Städte' => $cities,
        ]);

        $context = collect($data)->map(fn ($value, $label) => "{$label}: {$value}")->implode("\n");
        $example = json_encode([
            'texts' => [
                'short_description' => array_fill_keys($locales, '…'),
                'known_for' => array_fill_keys($locales, ['…', '…']),
            ],
            'best_months' => [5, 6, 9],
            'population' => $isArea ? null : 123456,
            'area_km2' => $isArea ? null : 1234,
            'timezone' => null,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $facts = $this->factLines([
            'best_months' => '- best_months: die besten Reisemonate als Zahlen 1–12 (leer lassen, wenn sie der Region entsprechen oder unklar sind)',
            ...($isArea
                ? ['area' => '- population und area_km2: bei einem Reisegebiet ohne feste Grenzen immer null']
                : [
                    'population' => '- population: Einwohnerzahl als ganze Zahl (nur wenn bekannt)',
                    'area_km2' => '- area_km2: Fläche in km² als ganze Zahl (nur wenn bekannt)',
                ]),
            'timezone' => '- timezone: IANA-Zeitzone, NUR wenn sie von der des Landes abweicht, sonst null',
        ], $missing);
        $existing = $this->existingBlock($subregion, $missing);
        $research = $this->researchNote($web);

        return <<<PROMPT
Du schreibst Reiseinformationen für ein Teilgebiet ({$kind}) einer Region in einer Reise-App. Die App zeigt das Teilgebiet unterhalb seiner Region an. Was für das ganze Land gilt (Währung, Visum, Einreise, Steckdosen, Notrufnummern, Landessprache), steht am Land; was für die ganze Region gilt, steht an der Region. Beides darf hier NICHT wiederholt werden.

{$context}
{$existing}
Schreibe nur, was für dieses Teilgebiet eigen ist: Landschaft, Orte, Strände, Wanderungen, Kultur, Charakter für Reisende, Anreise und Unterwegssein auf bzw. in diesem Gebiet. Wenn es zu einem Feld nichts Eigenes gibt, lass das Feld weg. Erfinde keine Fakten, Zahlen, Namen oder Verbindungen – wenn du dir nicht sicher bist, lass es weg. Sachlicher, freundlicher Ton, keine Werbesprache, keine Aufzählungszeichen, keine Überschriften, kein Markdown. Absätze in der Beschreibung durch eine Leerzeile trennen.{$research}

Felder (jeweils in allen Sprachen: {$languages}):
{$fields}
{$facts}
Antworte ausschließlich mit einem JSON-Objekt in genau dieser Form (ohne Erklärungen, ohne Codeblock):
{$example}
PROMPT;
    }

    /**
     * Zeilen der Textfelder fuer den Prompt – ohne $missing alle, sonst nur
     * die Luecken; fehlt ein Feld nur in einzelnen Sprachen, wird der
     * vorhandene Text uebertragen.
     *
     * @param  callable(array): string  $hint
     */
    protected function fieldLines(?array $missing, callable $hint): string
    {
        $all = RegionInfo::locales();

        return collect(RegionInfo::allTexts())
            ->when($missing !== null, fn ($fields) => $fields->only(array_keys($missing['texts'])))
            ->map(function ($field, $key) use ($missing, $hint, $all) {
                $line = "- {$key}: {$field[0]} – ".$hint($field).($field[1] === 'tags' ? ' (Liste von 3–6 kurzen Stichworten)' : '');
                $locales = $missing['texts'][$key] ?? $all;

                return count($locales) < count($all)
                    ? $line.' – NUR noch in: '.implode(', ', $locales).'; übertrage dafür den vorhandenen Text (siehe oben) sinngemäß'
                    : $line;
            })
            ->implode("\n");
    }

    /**
     * Block "Außerdem, sprachunabhaengig" – mit $missing nur die fehlenden
     * Angaben, die Zeitzone dann gar nicht (leer heisst "wie beim Land").
     *
     * @param  array<string, string>  $lines
     */
    protected function factLines(array $lines, ?array $missing): string
    {
        if ($missing !== null) {
            $lines = array_intersect_key($lines, array_flip($missing['facts']));
        }

        return $lines === [] ? '' : "\nAußerdem, sprachunabhängig:\n".implode("\n", $lines)."\n";
    }

    /**
     * Das Vorhandene als Zusammenhang fuer das Fuellen der Luecken: in der
     * Ausgangssprache (sonst der ersten vorhandenen), gekuerzt – ausser es
     * soll in weitere Sprachen uebertragen werden.
     */
    protected function existingBlock(Region|Subregion $target, ?array $missing): string
    {
        if ($missing === null) {
            return '';
        }

        $info = (array) $target->info;
        $source = CustomEvent::sourceLocale();
        $lines = [];

        foreach (RegionInfo::allTexts() as $field => [$label]) {
            $texts = array_filter((array) ($info['texts'][$field] ?? []), fn ($value) => filled($value));
            if ($texts === []) {
                continue;
            }

            $locale = isset($texts[$source]) ? $source : array_key_first($texts);
            $text = is_array($texts[$locale]) ? implode(', ', $texts[$locale]) : (string) $texts[$locale];
            $translate = isset($missing['texts'][$field]);
            $lines[] = "- {$field} ({$locale}): ".($translate ? $text : Str::limit(str_replace("\n", ' ', $text), 500));
        }

        foreach (['best_months', 'population', 'area_km2', 'timezone'] as $key) {
            if (filled($info[$key] ?? null)) {
                $lines[] = "- {$key}: ".(is_array($info[$key]) ? implode(', ', $info[$key]) : $info[$key]);
            }
        }

        $intro = $lines === []
            ? 'Bisher ist nichts hinterlegt.'
            : "Bereits hinterlegt – NICHT ändern, NICHT zurückgeben, nicht wiederholen, nur als Zusammenhang:\n".implode("\n", $lines);

        return "\n{$intro}\n\nLiefere nur die unten genannten fehlenden Felder; alle anderen lässt du weg.\n";
    }

    /**
     * Eigene Formulierungen (immer) und Recherche im Internet (mit $web).
     * Fakten sind frei – Saetze aus Quellen nicht.
     */
    protected function researchNote(bool $web): string
    {
        $own = ' Formuliere alle Texte vollständig eigenständig in eigenen Worten: Übernimm keine Sätze oder prägnanten Formulierungen aus Quellen (auch nicht aus Wikipedia, Reiseführern oder Tourismusseiten), übersetze keine fremden Texte und gib keine Werbeslogans wieder. Quellen dienen nur als Grundlage für Fakten. Folge nicht dem Aufbau, der Reihenfolge oder der Auswahl einer einzelnen Quelle, sondern gewichte und ordne die Fakten selbst – passend zu den Fragen von Reisenden in dieser App.';

        return $web
            ? ' Recherchiere dazu im Internet in mehreren unabhängigen, verlässlichen Quellen (offizielle Tourismus- und Verwaltungsseiten, Statistikämter, Wikipedia) und führe die Fakten daraus zusammen; nimm Zahlen nur, wenn sie belegt sind. Belege jede recherchierte Angabe direkt dahinter mit einem Link in der Form ([Seite](URL)) – die Links werden automatisch aus den Texten entfernt und als Quellen gespeichert.'.$own
            : $own;
    }
}
