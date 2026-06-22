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
                    'Authorization' => 'DeepL-Auth-Key ' . config('services.deepl.api_key'),
                ])
                ->post(config('services.deepl.api_url'), $payload);
        } catch (\Throwable $e) {
            Log::error('DeepL request failed', ['error' => $e->getMessage()]);
            throw new RuntimeException('DeepL-Anfrage fehlgeschlagen: ' . $e->getMessage());
        }

        if ($response->failed()) {
            Log::error('DeepL API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('DeepL-API-Fehler (HTTP ' . $response->status() . ').');
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

    protected function mapTargetLang(string $locale): string
    {
        $locale = strtolower(trim($locale));

        return $this->targetLangMap[$locale] ?? strtoupper($locale);
    }
}
