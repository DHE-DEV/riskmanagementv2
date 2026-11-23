<?php

namespace App\Services;

use App\Support\AdminV2\AiAreas;
use App\Support\AiProviders;
use App\Support\AiSettings;

/**
 * Laesst die KI die Felder eines Formular-Abschnitts pruefen – mit Websuche,
 * Texte fuer Reisende in eigenen Worten. Die Antwort ist je Feld: korrekt,
 * Aenderungsvorschlag (mit Wert, Begruendung und Quellen) oder nicht pruefbar – in fester JSON-Form, damit sie unter den Feldern stehen und
 * uebernommen werden kann.
 */
class AiFieldReviewService
{
    public const STATUS_OK = 'ok';

    public const STATUS_CHANGE = 'change';

    public const STATUS_UNKNOWN = 'unknown';

    /** Wartezeit auf die Websuche in Sekunden. */
    public const TIMEOUT = 300;

    /**
     * Die KI recherchiert im Internet und bewertet jedes Feld des Abschnitts.
     * Dauert oft ueber eine Minute – laeuft daher im Hintergrund (ReviewAiFieldsJob).
     *
     * @param  array<string, mixed>  $context  Angaben des Abschnitts: Schluessel => Wert
     * @param  array<string, string>  $labels  Schluessel => Bezeichnung
     * @param  array<string, mixed>  $extra  weitere Angaben des Eintrags zur Einordnung (z. B. Name, ISO-Code)
     * @param  array<string, string>  $extraLabels
     * @param  string|null  $hint  Hinweis des Formulars zu diesem Abschnitt, z. B. wie die Felder zusammenhaengen
     * @param  array<string, string>  $formats  erlaubte Werte je Feld, z. B. Stufen oder Listen
     * @return array{fields: array<string, array{status: string, value: ?string, note: ?string, sources: array<int, string>}>, summary: ?string, sources: array<int, array{url: string, title: ?string}>, prompt: string, usage: ?array}
     */
    public function review(string $area, string $section, array $context, array $labels, array $extra, array $extraLabels, ?string $model = null, ?string $hint = null, array $formats = []): array
    {
        $model = $model ?: AiSettings::model();
        $ai = AiProviders::webSearchFor($model);

        $prompt = $this->buildPrompt($area, $section, $context, $labels, $extra, $extraLabels, $hint, $formats);
        $result = $this->parse($ai->searchWeb($prompt, ['model' => $model, 'timeout' => self::TIMEOUT]), array_keys($labels));

        $usage = $ai->lastUsage();

        return $result + [
            // Belegte Seiten zuerst, je Adresse einmal.
            'sources' => collect($ai->lastSources())
                ->sortByDesc('cited')
                ->map(fn (array $source) => ['url' => $this->sourceUrl((string) $source['url']), 'title' => $source['title'] ?? null])
                ->unique('url')
                ->values()
                ->all(),
            'prompt' => $prompt,
            'usage' => $usage ? $usage + ['cost' => AiSettings::cost($usage['model'], $usage['input_tokens'], $usage['output_tokens'])] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, string>  $labels
     * @param  array<string, mixed>  $extra
     * @param  array<string, string>  $extraLabels
     * @param  array<string, string>  $formats
     */
    public function buildPrompt(string $area, string $section, array $context, array $labels, array $extra, array $extraLabels, ?string $hint = null, array $formats = []): string
    {
        $checks = app(AiCheckService::class);
        $areaLabel = AiAreas::label($area);
        $sectionLabel = AiAreas::sectionLabel($area, $section);

        $fields = [];
        foreach ($labels as $key => $label) {
            $fields[] = '- '.$key.' („'.$label.'“): '.$checks->format($context[$key] ?? null)
                .(isset($formats[$key]) ? "\n  Erlaubter Wert: ".$formats[$key] : '');
        }

        $identity = [];
        foreach ($extra as $key => $value) {
            // Zur Einordnung reichen die Kennzeichen des Eintrags – nicht die Angaben anderer Abschnitte.
            if (! in_array($key, ['risk_profile', 'lounges', 'mobility', 'hotels', 'airlines', 'airports', 'baggage', 'pets', 'countries', 'cities', 'names', 'contact'], true)
                && ! preg_match('/^(baggage|pets|lounge|lounges|hotel|hotels|mobility|risk)_/', $key)) {
                $identity[] = ($extraLabels[$key] ?? $key).': '.$checks->format($value);
            }
        }

        return implode("\n\n", array_filter([
            'Du pflegst Stammdaten einer Reise-Informationsplattform für Reisende und Reisebüros. Bereich: '.$areaLabel.', Abschnitt: '.$sectionLabel.'.',
            $identity ? "Der Eintrag:\n".implode("\n", $identity) : null,
            "Prüfe jedes der folgenden Felder (Schlüssel, Bezeichnung, aktueller Wert; „–“ heißt leer) und fülle leere Felder:\n".implode("\n", $fields),
            $hint,
            implode("\n", [
                'Recherche:',
                '- Suche im Internet nach dem aktuellen Stand, bevor du ein Feld bewertest – verlasse dich nicht auf dein Vorwissen.',
                '- Bevorzuge amtliche und fachliche Quellen (z. B. Auswärtiges Amt, Botschaften, Gesundheits- und Tourismusbehörden, WHO, Flughafen- bzw. Airline-Websites); gleiche bei wichtigen Angaben mehrere Quellen ab.',
                '- Findest du keine verlässliche Angabe, ist das Feld "unknown" – nichts erfinden oder schätzen.',
            ]),
            implode("\n", [
                'Texte (Beschreibungen, Hinweise, Bemerkungen):',
                '- Formuliere vollständig in eigenen Worten. Übernimm keine Sätze oder Satzteile wörtlich aus den Quellen.',
                '- Schreibe für Reisende: Was bedeutet die Information konkret für die Reise, worauf sollte man achten, was ist zu tun? Praktisch, sachlich und verständlich, ohne Werbesprache und ohne Füllsätze.',
                '- Keine Quellenangaben, Links oder Fußnoten im Wert selbst – Quellen gehören in "sources".',
                '- Felder in anderen Sprachen (EN, NL) bekommen dieselbe Aussage, natürlich in dieser Sprache formuliert.',
            ]),
            'Antworte ausschließlich mit JSON in genau dieser Form, ohne Erklärungen davor oder danach:',
            '{"summary": "ein Satz zum Gesamteindruck", "fields": {"<schlüssel>": {"status": "ok" | "change" | "unknown", "value": "<empfohlener Wert>", "note": "<kurze Begründung>", "sources": ["<URL der Quelle>"]}}}',
            $formats !== [] ? 'Steht bei einem Feld „Erlaubter Wert“, muss "value" genau diesem Format folgen – sonst lässt sich der Vorschlag nicht übernehmen. Begründungen und Einschränkungen gehören in "note", nicht in "value".' : null,
            'Regeln: "ok", wenn der aktuelle Wert richtig ist. "change", wenn er falsch, veraltet oder leer ist und du einen Wert empfehlen kannst – dann steht in "value" der vollständige empfohlene Wert. "unknown", wenn du es nicht verlässlich beurteilen kannst. In "sources" stehen die Adressen der Seiten, auf die sich die Bewertung stützt.',
            'Hat ein Feld mehrere Angaben (Listen, „alle Angaben“), steht in "value" je Angabe eine eigene Zeile in der Form „Bezeichnung: Wert“ – als Text mit Zeilenumbrüchen, nicht als JSON und nicht als Fließtext.',
            'Werte so schreiben, wie sie im Feld stehen sollen: Ja/Nein für Ja/Nein-Felder, Zahlen ohne Tausendertrennzeichen, Koordinaten dezimal mit Punkt, Codes in Großbuchstaben, Kontinente/Länder/Regionen/Städte/Typen mit ihrem deutschen Namen. Gib zu jedem Schlüssel genau einen Eintrag zurück.',
        ]));
    }

    /**
     * Verschachtelte Angaben als Zeilen "Bezeichnung › Unterpunkt: Wert".
     *
     * @param  array<int|string, mixed>  $value
     * @return array<int, string>
     */
    protected function lines(array $value, string $prefix = ''): array
    {
        $lines = [];

        foreach ($value as $key => $item) {
            $label = is_int($key) ? $prefix : ltrim($prefix.' › '.ucfirst(str_replace('_', ' ', $key)), ' ›');

            if (is_array($item)) {
                array_push($lines, ...$this->lines($item, $label));

                continue;
            }

            $text = is_bool($item) ? ($item ? 'Ja' : 'Nein') : trim((string) $item);

            if ($text !== '') {
                $lines[] = ($label !== '' ? $label.': ' : '').$text;
            }
        }

        return $lines;
    }

    /**
     * @param  array<int, string>  $keys
     * @return array{fields: array<string, array{status: string, value: ?string, note: ?string, sources: array<int, string>}>, summary: ?string}
     */
    public function parse(string $answer, array $keys): array
    {
        // Das Modell rahmt JSON gelegentlich mit ```json … ``` ein.
        $data = preg_match('/\{.*\}/s', $answer, $match) ? json_decode($match[0], true) : null;

        if (! is_array($data) || ! is_array($data['fields'] ?? null)) {
            throw new \RuntimeException('Die Antwort der KI ließ sich nicht auswerten.');
        }

        $fields = [];

        foreach ($keys as $key) {
            $field = $data['fields'][$key] ?? null;

            if (! is_array($field)) {
                $fields[$key] = ['status' => self::STATUS_UNKNOWN, 'value' => null, 'note' => 'Keine Angabe der KI.', 'sources' => []];

                continue;
            }

            $status = in_array($field['status'] ?? null, [self::STATUS_OK, self::STATUS_CHANGE, self::STATUS_UNKNOWN], true) ? $field['status'] : self::STATUS_UNKNOWN;
            // Strukturierte Antworten (Liste, Objekt) werden zu lesbaren Zeilen statt zu rohem JSON.
            $value = isset($field['value']) && $field['value'] !== '' && $field['value'] !== []
                ? trim(is_scalar($field['value']) ? (string) $field['value'] : implode("\n", $this->lines((array) $field['value'])))
                : null;
            $value = $value === '' ? null : $value;

            // Ein Vorschlag ohne Wert ist keiner.
            if ($status === self::STATUS_CHANGE && $value === null) {
                $status = self::STATUS_UNKNOWN;
            }

            $note = isset($field['note']) ? $this->withoutCitations((string) $field['note']) : '';

            $fields[$key] = [
                'status' => $status,
                'value' => $status === self::STATUS_CHANGE ? $this->withoutCitations($value) : null,
                'note' => $note !== '' ? $note : null,
                'sources' => array_values(array_unique(array_filter(
                    array_map(fn ($url) => is_string($url) ? $this->sourceUrl($url) : '', (array) ($field['sources'] ?? [])),
                    fn (string $url) => preg_match('#^https?://#i', $url) === 1,
                ))),
            ];
        }

        return [
            'fields' => $fields,
            'summary' => isset($data['summary']) && trim((string) $data['summary']) !== '' ? trim((string) $data['summary']) : null,
        ];
    }

    /**
     * Quellenverweise, die die Websuche in den Text setzt ("([seite.de](https://…))"), entfernen.
     */
    protected function withoutCitations(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = preg_replace('/\s*\(\[[^\]]*\]\(https?:[^)\s]+\)\)/u', '', $text) ?? $text;
        $text = preg_replace('/\[([^\]]+)\]\(https?:[^)\s]+\)/u', '$1', $text) ?? $text;
        $text = preg_replace('/[ \t]+([.,;:])/u', '$1', $text) ?? $text;

        return trim($text);
    }

    /**
     * Adresse ohne die Kennung, die die Websuche anhaengt (utm_source=openai).
     */
    protected function sourceUrl(string $url): string
    {
        $url = preg_replace('/([?&])utm_source=openai(&|$)/', '$1', trim($url)) ?? $url;

        return rtrim($url, '?&');
    }
}
