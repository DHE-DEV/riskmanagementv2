<?php

namespace App\Services\Push;

/**
 * Texte der Push-Benachrichtigungen je Sprache der App (de, en, nl).
 */
class PushTexts
{
    public const TRAVEL_IMPORTANT_CHANGE_TITLE = [
        'de' => 'Wichtige Änderung für deine Reise',
        'en' => 'Important update for your trip',
        'nl' => 'Belangrijke wijziging voor je reis',
    ];

    public const TRAVEL_IMPORTANT_CHANGE_BODY = [
        'de' => 'Für „{trip}“ gibt es neue Einreise- oder Gesundheitsinformationen. Tippe, um sie anzusehen.',
        'en' => 'There is new entry or health information for “{trip}”. Tap to view it.',
        'nl' => 'Er is nieuwe informatie over inreis- of gezondheidsregels voor “{trip}”. Tik om te bekijken.',
    ];

    private const FALLBACK_TRIP = [
        'de' => 'deine Reise',
        'en' => 'your trip',
        'nl' => 'je reis',
    ];

    public static function fallbackTrip(string $locale): string
    {
        return self::FALLBACK_TRIP[$locale] ?? self::FALLBACK_TRIP['de'];
    }
}
