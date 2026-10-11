<?php

namespace App\Services;

use App\Models\CustomEvent;
use App\Models\Region;
use App\Models\Subregion;
use App\Support\AdminV2\RegionInfo;
use App\Support\AdminV2\SubregionInfo;
use Illuminate\Support\Collection;
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
        $failed = [];
        $answers = [];

        try {
            $answers = $this->chat()->sendPrompts(
                $regions->mapWithKeys(fn (Region|Subregion $region) => ['r'.$region->id => $this->prompt($region)])->all(),
                ['timeout' => 180, 'max_tokens' => 6000],
            );
        } catch (Throwable) {
            foreach ($regions as $region) {
                try {
                    $answers['r'.$region->id] = $this->chat()->sendPrompt($this->prompt($region), ['timeout' => 180, 'max_tokens' => 6000]);
                } catch (Throwable $e) {
                    $failed[$region->id] = $this->shortError($e);
                }
            }
        }

        foreach ($regions as $region) {
            if (! isset($answers['r'.$region->id])) {
                continue;
            }

            try {
                $this->store($region, $this->parse($answers['r'.$region->id], $region), $overwrite);
                $done[] = $region->id;
            } catch (Throwable $e) {
                $failed[$region->id] = $this->shortError($e);
            }
        }

        return ['done' => $done, 'failed' => $failed, 'usage' => $this->chat()->lastUsage()];
    }

    /**
     * Vorschlag in die gespeicherten Angaben uebernehmen und als
     * ungepruefter KI-Entwurf kennzeichnen.
     *
     * @param  array<string, mixed>  $suggestion
     */
    public function store(Region|Subregion $region, array $suggestion, bool $overwrite = false): void
    {
        $info = RegionInfo::merge($region->info, $suggestion, $overwrite);

        if (($info['timezone'] ?? null) === $region->country?->timezone) {
            unset($info['timezone']);
        }

        $info['meta'] = [
            'ai_generated_at' => now()->toIso8601String(),
            'ai_model' => (string) ($this->chat()->lastUsage()['model'] ?? ''),
        ];

        $region->forceFill(['info' => $info])->save();
    }

    public function prompt(Region|Subregion $region): string
    {
        if ($region instanceof Subregion) {
            return $this->subregionPrompt($region);
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

        $fields = collect(RegionInfo::allTexts())
            ->map(fn ($field, $key) => "- {$key}: {$field[0]} – {$field[2]}".($field[1] === 'tags' ? ' (Liste von 3–6 kurzen Stichworten)' : ''))
            ->implode("\n");

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

        return <<<PROMPT
Du schreibst Reiseinformationen für eine Region in einer Reise-App. Die App zeigt die Region unterhalb ihres Landes an; alles, was für das ganze Land gilt (Währung, Visum, Einreise, Steckdosen, Strom, Notrufnummern, Landessprache, allgemeine Landeskunde), steht dort bereits und darf hier NICHT wiederholt werden.

{$context}

Schreibe nur, was für diese Region eigen ist. Wenn es zu einem Feld nichts Regionsspezifisches gibt (z. B. kein abweichendes Klima, keine Besonderheiten), lass das Feld weg. Erfinde keine Fakten, Zahlen, Namen oder Verbindungen – wenn du dir nicht sicher bist, lass es weg. Kleine, touristisch wenig bekannte Regionen bekommen kurze, sachliche Texte. Sachlicher, freundlicher Ton, keine Werbesprache, keine Aufzählungszeichen, keine Überschriften, kein Markdown. Absätze in der Beschreibung durch eine Leerzeile trennen.

Felder (jeweils in allen Sprachen: {$languages}):
{$fields}

Außerdem, sprachunabhängig:
- best_months: die besten Reisemonate als Zahlen 1–12 (leer lassen, wenn sie dem ganzen Land entsprechen oder unklar sind)
- population: Einwohnerzahl der Region als ganze Zahl (nur wenn bekannt)
- area_km2: Fläche in km² als ganze Zahl (nur wenn bekannt)
- timezone: IANA-Zeitzone der Region, NUR wenn sie von der des Landes abweicht, sonst null

Antworte ausschließlich mit einem JSON-Objekt in genau dieser Form (ohne Erklärungen, ohne Codeblock):
{$example}
PROMPT;
    }

    /**
     * Antwort der KI -> Angaben im Format von regions.info (ohne meta).
     *
     * @return array<string, mixed>
     */
    public function parse(string $answer, Region|Subregion|null $region = null): array
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
            throw new RuntimeException('Die KI hat keine Angaben zur Region geliefert.');
        }

        return $info;
    }

    /**
     * Prompt fuer ein Teilgebiet: dieselben Felder wie bei einer Region,
     * bezogen auf die Insel bzw. das Reisegebiet. Was fuer die ganze Region
     * gilt, steht an der Region.
     */
    public function subregionPrompt(Subregion $subregion): string
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

        $fields = collect(RegionInfo::allTexts())
            ->map(fn ($field, $key) => "- {$key}: {$field[0]} – ".str_replace('Region', $kind, $field[2]).($field[1] === 'tags' ? ' (Liste von 3–6 kurzen Stichworten)' : ''))
            ->implode("\n");

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

        $numbers = $isArea
            ? '- population und area_km2: bei einem Reisegebiet ohne feste Grenzen immer null'
            : "- population: Einwohnerzahl als ganze Zahl (nur wenn bekannt)\n- area_km2: Fläche in km² als ganze Zahl (nur wenn bekannt)";

        return <<<PROMPT
Du schreibst Reiseinformationen für ein Teilgebiet ({$kind}) einer Region in einer Reise-App. Die App zeigt das Teilgebiet unterhalb seiner Region an. Was für das ganze Land gilt (Währung, Visum, Einreise, Steckdosen, Notrufnummern, Landessprache), steht am Land; was für die ganze Region gilt, steht an der Region. Beides darf hier NICHT wiederholt werden.

{$context}

Schreibe nur, was für dieses Teilgebiet eigen ist: Landschaft, Orte, Strände, Wanderungen, Kultur, Charakter für Reisende, Anreise und Unterwegssein auf bzw. in diesem Gebiet. Wenn es zu einem Feld nichts Eigenes gibt, lass das Feld weg. Erfinde keine Fakten, Zahlen, Namen oder Verbindungen – wenn du dir nicht sicher bist, lass es weg. Sachlicher, freundlicher Ton, keine Werbesprache, keine Aufzählungszeichen, keine Überschriften, kein Markdown. Absätze in der Beschreibung durch eine Leerzeile trennen.

Felder (jeweils in allen Sprachen: {$languages}):
{$fields}

Außerdem, sprachunabhängig:
- best_months: die besten Reisemonate als Zahlen 1–12 (leer lassen, wenn sie der Region entsprechen oder unklar sind)
{$numbers}
- timezone: IANA-Zeitzone, NUR wenn sie von der des Landes abweicht, sonst null

Antworte ausschließlich mit einem JSON-Objekt in genau dieser Form (ohne Erklärungen, ohne Codeblock):
{$example}
PROMPT;
    }

    protected function shortError(Throwable $e): string
    {
        return mb_substr(str_replace('Fehler bei der Kommunikation mit ChatGPT: ', '', $e->getMessage()), 0, 200);
    }
}
