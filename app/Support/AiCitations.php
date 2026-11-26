<?php

namespace App\Support;

/**
 * Quellenverweise, die die Websuche der KI selbst in den Antworttext setzt –
 * etwa "([auswaertiges-amt.de](https://…?utm_source=openai))". Fuer Werte,
 * die ins Formular uebernommen werden, gehoeren sie entfernt; die Quellen
 * stehen gesondert an der Anfrage.
 */
class AiCitations
{
    /**
     * Verweise entfernen; ein verlinkter Text bleibt als Text stehen.
     */
    public static function strip(?string $text): ?string
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
    public static function cleanUrl(string $url): string
    {
        $url = preg_replace('/([?&])utm_source=openai(&|$)/', '$1', trim($url)) ?? $url;

        return rtrim($url, '?&');
    }
}
