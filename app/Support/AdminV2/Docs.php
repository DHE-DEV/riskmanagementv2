<?php

namespace App\Support\AdminV2;

use App\Services\ApiDocRenderer;
use Illuminate\Support\Facades\Cache;

/**
 * Interne Anleitungen in Admin v2 (/adminv2/docs): Markdown aus docs/admin/*.md,
 * gerendert wie die API-Anleitungen (GitHub-Markdown, Inhaltsverzeichnis aus den
 * Ueberschriften). HTML im Markdown ist erlaubt – die Dateien stammen aus dem Repo.
 */
class Docs extends ApiDocRenderer
{
    /**
     * @var array<string, array{file: string, title: string, description: string, icon: string, category: string}>
     */
    public const DOCS = [
        'push-notifications' => [
            'file' => 'push-notifications.md',
            'title' => 'Push-Benachrichtigungen',
            'description' => 'Wie die TripData-App bei wichtigen Änderungen an Reise-Links Push-Benachrichtigungen bekommt – Ablauf, API, Skalierung, Einrichtung und Betrieb.',
            'icon' => 'bell-alert',
            'category' => 'Apps',
        ],
    ];

    /**
     * @return array{file: string, title: string, description: string, icon: string, category: string}|null
     */
    public static function find(string $key): ?array
    {
        return self::DOCS[$key] ?? null;
    }

    /**
     * @return array{html: string, toc: array<int, array{level: int, id: string, text: string}>, updated_at: ?int}
     */
    public function renderDoc(string $key): array
    {
        $doc = self::find($key);
        $path = $doc ? base_path('docs/admin/'.$doc['file']) : null;

        if (! $path || ! file_exists($path)) {
            return ['html' => '', 'toc' => [], 'updated_at' => null];
        }

        $cacheKey = sprintf('adminv2-docs:%s:%d:%d', $key, filemtime($path), filemtime(__FILE__));

        return Cache::remember($cacheKey, now()->addDay(), fn () => array_merge(
            $this->convert((string) file_get_contents($path), false),
            ['updated_at' => filemtime($path)],
        ));
    }
}
