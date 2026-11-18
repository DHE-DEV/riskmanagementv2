<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Dünner Wrapper um die DeepL-Übersetzungs-API (Pro).
 *
 * Wird genutzt, um Event-Titel und -Beschreibungen per Knopfdruck aus der
 * Ausgangssprache in die konfigurierten Zielsprachen zu übersetzen. Die
 * Ergebnisse können danach jederzeit manuell überschrieben werden.
 */
class DeepLTranslationService
{
    /**
     * DeepL erwartet teils spezifischere Zielsprach-Codes als reine ISO-2.
     * Hier nur die Sonderfälle; alles andere wird zu Großbuchstaben normalisiert.
     */
    protected array $targetLangMap = [
        'en' => 'EN-GB',
        'pt' => 'PT-PT',
    ];

    public function isConfigured(): bool
    {
        return filled(config('services.deepl.api_key'));
    }

    /**
     * Einzelnen Text übersetzen. Liefert den übersetzten Text zurück.
     *
     * @param  bool  $html  true für HTML-Inhalte (RichEditor), damit Tags erhalten bleiben.
     *
     * @throws RuntimeException bei Konfigurations- oder API-Fehlern.
     */
    public function translate(string $text, string $targetLocale, ?string $sourceLocale = null, bool $html = false): string
    {
        if (trim($text) === '') {
            return $text;
        }

        if (! $this->isConfigured()) {
            throw new RuntimeException('DeepL ist nicht konfiguriert (DEEPL_API_KEY fehlt).');
        }

        $payload = [
            'text' => $text,
            'target_lang' => $this->mapTargetLang($targetLocale),
        ];

        if ($sourceLocale) {
            $payload['source_lang'] = strtoupper($sourceLocale);
        }

        if ($html) {
            $payload['tag_handling'] = 'html';
        }

        try {
            $response = Http::asForm()
                ->timeout(30)
                ->withHeaders([
                    'Authorization' => 'DeepL-Auth-Key '.config('services.deepl.api_key'),
                ])
                ->post(config('services.deepl.api_url'), $payload);
        } catch (\Throwable $e) {
            Log::error('DeepL request failed', ['error' => $e->getMessage()]);
            throw new RuntimeException('DeepL-Anfrage fehlgeschlagen: '.$e->getMessage());
        }

        if ($response->failed()) {
            Log::error('DeepL API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('DeepL-API-Fehler (HTTP '.$response->status().').');
        }

        $translated = $response->json('translations.0.text');

        if ($translated === null) {
            throw new RuntimeException('DeepL lieferte keine Übersetzung zurück.');
        }

        return $translated;
    }

    /**
     * Convenience-Wrapper für HTML-Inhalte (z. B. RichEditor / popup_content).
     */
    public function translateHtml(string $html, string $targetLocale, ?string $sourceLocale = null): string
    {
        return $this->translate($html, $targetLocale, $sourceLocale, true);
    }

    /**
     * Mehrere kurze Texte (z. B. Ortsnamen) in einer Anfrage übersetzen – höchstens 50 je Aufruf.
     * Der optionale Kontext hilft DeepL bei Eigennamen (z. B. "Städte in Frankreich"), wird
     * aber selbst nicht übersetzt. Liefert die Übersetzungen in derselben Reihenfolge.
     *
     * @param  array<int, string>  $texts
     * @return array<int, string>
     *
     * @throws RuntimeException bei Konfigurations- oder API-Fehlern.
     */
    public function translateBatch(array $texts, string $targetLocale, ?string $sourceLocale = null, ?string $context = null): array
    {
        $texts = array_values($texts);

        if ($texts === []) {
            return [];
        }

        if (count($texts) > 50) {
            throw new RuntimeException('DeepL nimmt höchstens 50 Texte je Anfrage an.');
        }

        if (! $this->isConfigured()) {
            throw new RuntimeException('DeepL ist nicht konfiguriert (DEEPL_API_KEY fehlt).');
        }

        $payload = array_filter([
            'text' => $texts,
            'target_lang' => $this->mapTargetLang($targetLocale),
            'source_lang' => $sourceLocale ? strtoupper($sourceLocale) : null,
            'context' => $context,
        ]);

        try {
            $response = Http::asJson()
                ->timeout(60)
                ->withHeaders(['Authorization' => 'DeepL-Auth-Key '.config('services.deepl.api_key')])
                ->post(config('services.deepl.api_url'), $payload);
        } catch (\Throwable $e) {
            Log::error('DeepL request failed', ['error' => $e->getMessage()]);
            throw new RuntimeException('DeepL-Anfrage fehlgeschlagen: '.$e->getMessage());
        }

        if ($response->failed()) {
            Log::error('DeepL API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('DeepL-API-Fehler (HTTP '.$response->status().').');
        }

        $translated = array_map(fn (array $item) => (string) ($item['text'] ?? ''), (array) $response->json('translations', []));

        if (count($translated) !== count($texts)) {
            throw new RuntimeException('DeepL lieferte nicht für jeden Text eine Übersetzung.');
        }

        return $translated;
    }

    protected function mapTargetLang(string $locale): string
    {
        $locale = strtolower(trim($locale));

        return $this->targetLangMap[$locale] ?? strtoupper($locale);
    }
}
