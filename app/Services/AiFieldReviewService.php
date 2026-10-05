<?php

namespace App\Services;

use App\Support\AdminV2\AiAreas;
use App\Support\AiSettings;

/**
 * Laesst die KI die Felder eines Formular-Abschnitts pruefen. Die Antwort ist
 * je Feld: korrekt, Aenderungsvorschlag (mit Wert und Begruendung) oder nicht
 * pruefbar – in fester JSON-Form, damit sie unter den Feldern stehen und
 * uebernommen werden kann.
 */
class AiFieldReviewService
{
    public const STATUS_OK = 'ok';

    public const STATUS_CHANGE = 'change';

    public const STATUS_UNKNOWN = 'unknown';

    /**
     * @param  array<string, mixed>  $context  Angaben des Abschnitts: Schluessel => Wert
     * @param  array<string, string>  $labels  Schluessel => Bezeichnung
     * @param  array<string, mixed>  $extra  weitere Angaben des Eintrags zur Einordnung (z. B. Name, ISO-Code)
     * @param  array<string, string>  $extraLabels
     * @param  string|null  $hint  Hinweis des Formulars zu diesem Abschnitt, z. B. wie die Felder zusammenhaengen
     * @return array{fields: array<string, array{status: string, value: ?string, note: ?string}>, summary: ?string, prompt: string, usage: ?array}
     */
    public function review(string $area, string $section, array $context, array $labels, array $extra, array $extraLabels, ?string $model = null, ?string $hint = null): array
    {
        // Unter dem Zeitlimit des Webservers bleiben, damit ein Fehler im Fenster ankommt.
        $options = ['model' => $model ?: AiSettings::model(), 'timeout' => 50];
        $ai = app(ChatGptService::class);

        // Bezeichnungen wie "Sicherheit › Kriminalitätsniveau": je Bereich eine
        // eigene Anfrage, alle gleichzeitig – ein grosser Abschnitt dauert sonst zu lange.
        $groups = [];
        foreach ($labels as $key => $label) {
            $groups[str_contains($label, ' › ') ? explode(' › ', $label, 2)[0] : ''][$key] = $label;
        }

        // Haengen die Felder voneinander ab (Hinweis des Formulars), bleiben sie in einer Anfrage beisammen.
        if (count($groups) > 1 && $hint === null) {
            $prompts = array_map(fn (array $group) => $this->buildPrompt($area, $section, $context, $group, $extra, $extraLabels), $groups);
            $answers = $ai->sendPrompts($prompts, $options);

            $result = ['fields' => [], 'summary' => null];
            foreach ($groups as $name => $group) {
                $result['fields'] += $this->parse($answers[$name], array_keys($group))['fields'];
            }
            $prompt = implode("\n\n", $prompts);
        } else {
            $prompt = $this->buildPrompt($area, $section, $context, $labels, $extra, $extraLabels, $hint);
            $result = $this->parse($ai->sendPrompt($prompt, $options), array_keys($labels));
        }

        $usage = $ai->lastUsage();

        return $result + [
            'prompt' => $prompt,
            'usage' => $usage ? $usage + ['cost' => AiSettings::cost($usage['model'], $usage['input_tokens'], $usage['output_tokens'])] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, string>  $labels
     * @param  array<string, mixed>  $extra
     * @param  array<string, string>  $extraLabels
     */
    public function buildPrompt(string $area, string $section, array $context, array $labels, array $extra, array $extraLabels, ?string $hint = null): string
    {
        $checks = app(AiCheckService::class);
        $areaLabel = AiAreas::label($area);
        $sectionLabel = AiAreas::sectionLabel($area, $section);

        $fields = [];
        foreach ($labels as $key => $label) {
            $fields[] = '- '.$key.' („'.$label.'“): '.$checks->format($context[$key] ?? null);
        }

        $identity = [];
        foreach ($extra as $key => $value) {
            // Zur Einordnung reichen die Kennzeichen des Eintrags – nicht die Angaben anderer Abschnitte.
            if (! in_array($key, ['risk_profile', 'lounges', 'mobility', 'hotels', 'airlines', 'airports', 'baggage', 'pets', 'countries', 'cities', 'names', 'contact'], true)
                && ! str_starts_with($key, 'baggage_') && ! str_starts_with($key, 'pets_')) {
                $identity[] = ($extraLabels[$key] ?? $key).': '.$checks->format($value);
            }
        }

        return implode("\n", array_filter([
            'Du prüfst Stammdaten einer Reise-Informationsplattform. Bereich: '.$areaLabel.', Abschnitt: '.$sectionLabel.'.',
            $identity ? "Der Eintrag:\n".implode("\n", $identity) : null,
            "Prüfe jedes der folgenden Felder (Schlüssel, Bezeichnung, aktueller Wert; „–“ heißt leer):\n".implode("\n", $fields),
            $hint,
            'Antworte ausschließlich mit JSON in genau dieser Form, ohne Erklärungen davor oder danach:',
            '{"summary": "ein Satz zum Gesamteindruck", "fields": {"<schlüssel>": {"status": "ok" | "change" | "unknown", "value": "<empfohlener Wert>", "note": "<kurze Begründung>"}}}',
            'Regeln: "ok", wenn der aktuelle Wert richtig ist. "change", wenn er falsch, veraltet oder leer ist und du einen Wert empfehlen kannst – dann steht in "value" der vollständige empfohlene Wert. "unknown", wenn du es nicht verlässlich beurteilen kannst.',
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
     * @return array{fields: array<string, array{status: string, value: ?string, note: ?string}>, summary: ?string}
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
                $fields[$key] = ['status' => self::STATUS_UNKNOWN, 'value' => null, 'note' => 'Keine Angabe der KI.'];

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

            $fields[$key] = [
                'status' => $status,
                'value' => $status === self::STATUS_CHANGE ? $value : null,
                'note' => isset($field['note']) && trim((string) $field['note']) !== '' ? trim((string) $field['note']) : null,
            ];
        }

        return [
            'fields' => $fields,
            'summary' => isset($data['summary']) && trim((string) $data['summary']) !== '' ? trim((string) $data['summary']) : null,
        ];
    }
}
