<?php

namespace App\Support\AdminV2;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Bereinigt HTML aus dem Texteditor, bevor es gespeichert wird.
 *
 * Die Beschreibung der Ereignisse wird in Karte, Feeds und E-Mails
 * ungefiltert ausgegeben – Skripte und Event-Handler duerfen deshalb gar
 * nicht erst in die Datenbank gelangen.
 */
class RichText
{
    public static function sanitize(?string $html): ?string
    {
        if (blank($html)) {
            return null;
        }

        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            // Der Standard (20.000 Zeichen) wuerde lange Beschreibungen abschneiden.
            ->withMaxInputLength(500_000);

        $clean = trim((new HtmlSanitizer($config))->sanitize($html));

        // Ein leerer Editor liefert "<p></p>" bzw. "<p><br></p>".
        return trim(strip_tags($clean, '<img><hr>')) === '' ? null : $clean;
    }
}
