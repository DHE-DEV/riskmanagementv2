<?php

namespace App\Support\AdminV2;

/**
 * Arten von Teilgebieten einer Region und ihre Bezeichnungen. Die Infos
 * eines Teilgebiets haben denselben Aufbau wie die Regionsinfos (RegionInfo).
 */
class SubregionInfo
{
    /** Art => [Bezeichnung, Gruppe (Mehrzahl fuer Ueberschriften), Symbol] */
    public const KINDS = [
        'island' => ['Insel', 'Inseln', 'sun'],
        'archipelago' => ['Inselgruppe', 'Inseln', 'squares-2x2'],
        'area' => ['Reisegebiet', 'Reisegebiete', 'map-pin'],
    ];

    /** Bezeichnungen in den weiteren Sprachen: Art => [Sprache => [Bezeichnung, Gruppe]] */
    public const KIND_TRANSLATIONS = [
        'island' => ['en' => ['Island', 'Islands'], 'nl' => ['Eiland', 'Eilanden']],
        'archipelago' => ['en' => ['Island group', 'Islands'], 'nl' => ['Eilandengroep', 'Eilanden']],
        'area' => ['en' => ['Travel area', 'Travel areas'], 'nl' => ['Reisgebied', 'Reisgebieden']],
    ];

    public static function kind(?string $kind): string
    {
        return isset(self::KINDS[$kind ?? '']) ? $kind : 'area';
    }

    public static function kindLabel(?string $kind): string
    {
        return self::KINDS[self::kind($kind)][0];
    }

    public static function groupLabel(?string $kind): string
    {
        return self::KINDS[self::kind($kind)][1];
    }

    public static function kindIcon(?string $kind): string
    {
        return self::KINDS[self::kind($kind)][2];
    }

    /**
     * Bezeichnung der Art je Sprache: ['de' => 'Insel', 'en' => 'Island', …].
     *
     * @return array<string, string>
     */
    public static function kindNames(?string $kind): array
    {
        $kind = self::kind($kind);

        return ['de' => self::KINDS[$kind][0]] + array_map(fn ($names) => $names[0], self::KIND_TRANSLATIONS[$kind]);
    }

    /**
     * Ueberschrift der Gruppe je Sprache: ['de' => 'Inseln', …].
     *
     * @return array<string, string>
     */
    public static function groupNames(?string $kind): array
    {
        $kind = self::kind($kind);

        return ['de' => self::KINDS[$kind][1]] + array_map(fn ($names) => $names[1], self::KIND_TRANSLATIONS[$kind]);
    }
}
