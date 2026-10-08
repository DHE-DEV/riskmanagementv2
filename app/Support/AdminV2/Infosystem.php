<?php

namespace App\Support\AdminV2;

use App\Models\EventType;
use App\Models\InfosystemEntry;
use Illuminate\Support\Str;

/**
 * Eintraege aus dem Passolution Infosystem (api.passolution.eu) – und wie
 * aus einem Eintrag ein Ereignis wird. Die Zuordnungen entsprechen dem
 * bisherigen Admin (CreateCustomEvent).
 */
class Infosystem
{
    /** Sprachen der Eintraege: Code => Bezeichnung */
    public const LANGUAGES = [
        'de' => 'Deutsch',
        'en' => 'Englisch',
        'fr' => 'Französisch',
        'it' => 'Italienisch',
    ];

    /** Rubrik des Infosystems => Code des Event-Typs */
    public const CATEGORY_CODES = [
        'Allgemein' => 'general',
        'Reiseverkehr' => 'travel',
        'Sicherheit' => 'safety',
        'Einreisebestimmungen' => 'entry',
        'Umweltereignisse' => 'environment',
        'Gesundheit' => 'health',
    ];

    /** Aelterer "tagtype" des Infosystems => Code des Event-Typs */
    public const TAGTYPE_CODES = [
        1 => 'environment',
        2 => 'travel',
        3 => 'safety',
        4 => 'entry',
        5 => 'general',
        6 => 'health',
    ];

    /**
     * Der Titel eines Ereignisses: Die Kopfzeile im Infosystem beginnt mit
     * dem Land ("Italien - Streik im Nahverkehr") – alles bis zum ersten
     * Bindestrich entfaellt.
     */
    public static function title(?string $header): string
    {
        $header = trim((string) $header);

        if (str_contains($header, '-')) {
            $rest = trim(Str::after($header, '-'));

            return $rest !== '' ? $rest : $header;
        }

        return $header;
    }

    /**
     * Die Beschreibung als HTML fuer den Texteditor. Reiner Text wird
     * absatzweise umbrochen; HTML bleibt, wie es ist.
     */
    public static function contentHtml(?string $content): string
    {
        $content = trim((string) $content);

        if ($content === '') {
            return '';
        }

        if ($content !== strip_tags($content)) {
            return $content;
        }

        return collect(preg_split('/\R{2,}/', $content) ?: [])
            ->map(fn (string $paragraph) => trim($paragraph))
            ->filter()
            ->map(fn (string $paragraph) => '<p>'.nl2br(e($paragraph), false).'</p>')
            ->implode('');
    }

    /**
     * Die Event-Typen eines Eintrags: aus seinen Rubriken, ersatzweise aus
     * dem aelteren "tagtype"; passt nichts, gilt "Allgemein".
     *
     * @return array<int, int>
     */
    public static function eventTypeIds(InfosystemEntry $entry): array
    {
        $codes = collect($entry->categories ?? [])
            ->map(fn ($category) => is_array($category) ? ($category['name'] ?? null) : $category)
            ->filter()
            ->map(fn ($name) => self::CATEGORY_CODES[$name] ?? null)
            ->filter()
            ->unique()
            ->values();

        if ($codes->isEmpty() && $entry->tagtype !== null && isset(self::TAGTYPE_CODES[(int) $entry->tagtype])) {
            $codes = collect([self::TAGTYPE_CODES[(int) $entry->tagtype]]);
        }

        $ids = $codes->isEmpty()
            ? collect()
            : EventType::query()->whereIn('code', $codes->all())->pluck('id', 'code');

        // In der Reihenfolge der Rubriken – die erste ist der Haupttyp.
        $ordered = $codes->map(fn (string $code) => $ids[$code] ?? null)->filter()->values();

        if ($ordered->isEmpty()) {
            $ordered = collect(array_filter([EventType::query()->where('code', 'general')->value('id')]));
        }

        return $ordered->map(fn ($id) => (int) $id)->all();
    }
}
