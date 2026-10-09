<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Rendert die Kunden-Anleitungen aus docs/*.md als HTML.
 *
 * Die Markdown-Dateien sind die einzige Quelle: Sie werden zum Download angeboten,
 * auf der API-Startseite (api.global-travel-monitor.de bzw. /api/v1/documentation)
 * eingebettet und als einzelne Seiten unter /docs/api/{guide} gezeigt.
 */
class ApiDocRenderer
{
    /**
     * Anleitungen je Kurzname: Markdown-Datei, Titel, Farbe der Doku-Seite,
     * zugehoerige OpenAPI-Datei und Anker auf der API-Startseite.
     *
     * @var array<string, array{file: string, title: string, color: string, openapi: ?string, anchor: string}>
     */
    public const GUIDES = [
        'gtm' => ['file' => 'gtm-api-guide.md', 'title' => 'Events API (GTM)', 'color' => '#ef4444', 'openapi' => 'gtm-api-openapi.yaml', 'anchor' => 'events-api-guide'],
        'events' => ['file' => 'event-api-guide.md', 'title' => 'Custom Event API', 'color' => '#f97316', 'openapi' => 'event-api-openapi.yaml', 'anchor' => 'event-api-guide'],
        'folders' => ['file' => 'folder-import-api-guide.md', 'title' => 'Folder Import API', 'color' => '#3b82f6', 'openapi' => 'folder-import-api-openapi.yaml', 'anchor' => 'folder-import-api-guide'],
        'feeds' => ['file' => 'feed-api-guide.md', 'title' => 'Feed API', 'color' => '#22c55e', 'openapi' => 'feed-api-openapi.yaml', 'anchor' => 'feed-api-guide'],
        'plugin' => ['file' => 'plugin-domain-api-guide.md', 'title' => 'Plugin Domain API', 'color' => '#14b8a6', 'openapi' => 'plugin-domain-api-openapi.yaml', 'anchor' => 'plugin-domain-api-guide'],
        'organisation' => ['file' => 'customer-settings-api-guide.md', 'title' => 'Customer Settings API', 'color' => '#a855f7', 'openapi' => null, 'anchor' => 'customer-settings-api-guide'],
    ];

    /** Anleitungen, die auf der API-Startseite eingebettet werden (in dieser Reihenfolge). */
    public const LANDING = ['gtm', 'events', 'folders', 'feeds', 'plugin'];

    /** Dateien aus docs/, die zum Download freigegeben sind. */
    public const DOWNLOADS = [
        'event-api-openapi.yaml',
        'event-api-guide.md',
        'gtm-api-openapi.yaml',
        'gtm-api-guide.md',
        'feed-api-openapi.yaml',
        'feed-api-guide.md',
        'folder-import-api-openapi.yaml',
        'folder-import-api-guide.md',
        'plugin-domain-api-openapi.yaml',
        'plugin-domain-api-guide.md',
        'customer-settings-api-guide.md',
    ];

    /**
     * @return array{file: string, title: string, color: string, openapi: ?string, anchor: string}|null
     */
    public function guide(string $key): ?array
    {
        return self::GUIDES[$key] ?? null;
    }

    /**
     * Absoluter Pfad einer freigegebenen Doku-Datei, sonst null.
     */
    public function downloadPath(string $file): ?string
    {
        if (! in_array($file, self::DOWNLOADS, true)) {
            return null;
        }

        $path = base_path('docs/'.$file);

        return file_exists($path) ? $path : null;
    }

    /**
     * Anleitung als HTML samt Inhaltsverzeichnis.
     *
     * @param  bool  $richCode  Codebloecke mit Kopieren-Knopf und Sprachlabel (Layout der Doku-Seiten)
     * @return array{html: string, toc: array<int, array{level: int, id: string, text: string}>}
     */
    public function render(string $key, bool $richCode = false): array
    {
        $guide = self::GUIDES[$key] ?? null;
        if ($guide === null) {
            return ['html' => '', 'toc' => []];
        }

        $path = base_path('docs/'.$guide['file']);
        if (! file_exists($path)) {
            return ['html' => '', 'toc' => []];
        }

        $cacheKey = sprintf('api-docs:%s:%d:%d', $key, (int) $richCode, filemtime($path));

        return Cache::remember($cacheKey, now()->addDay(), function () use ($path, $richCode) {
            return $this->convert((string) file_get_contents($path), $richCode);
        });
    }

    /**
     * Abschnitte fuer die API-Startseite: Anker und HTML je Anleitung.
     *
     * @return array<int, array{key: string, anchor: string, title: string, html: string}>
     */
    public function landingSections(): array
    {
        return array_map(fn (string $key) => [
            'key' => $key,
            'anchor' => self::GUIDES[$key]['anchor'],
            'title' => self::GUIDES[$key]['title'],
            'html' => $this->render($key)['html'],
        ], self::LANDING);
    }

    /**
     * @return array{html: string, toc: array<int, array{level: int, id: string, text: string}>}
     */
    protected function convert(string $markdown, bool $richCode): array
    {
        $environment = new Environment([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 50,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());

        $html = (string) (new MarkdownConverter($environment))->convert($markdown);

        $toc = [];
        $used = [];
        $html = preg_replace_callback('/<h([1-4])>(.*?)<\/h\1>/s', function (array $m) use (&$toc, &$used) {
            $level = (int) $m[1];
            $text = trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $id = Str::slug($text) ?: 'abschnitt';
            if (isset($used[$id])) {
                $id .= '-'.(++$used[$id]);
            } else {
                $used[$id] = 1;
            }
            $toc[] = ['level' => $level, 'id' => $id, 'text' => $text];

            return sprintf('<h%d id="%s">%s</h%d>', $level, $id, $m[2], $level);
        }, $html) ?? $html;

        // Tabellen scrollbar und im Stil der Doku-Seiten.
        $html = str_replace(['<table>', '</table>'], ['<div class="table-responsive"><table class="field-table">', '</table></div>'], $html);

        if ($richCode) {
            $html = preg_replace_callback('/<pre><code(?: class="language-([\w-]+)")?>(.*?)<\/code><\/pre>/s', function (array $m) {
                $language = $m[1] ?? '';
                $label = $language !== '' ? '<span class="code-label">'.e($language).'</span>' : '';

                return '<div class="code-block">'.$label
                    .'<button class="copy-btn" type="button"><i class="fas fa-copy"></i> Kopieren</button>'
                    .'<pre><code'.($language !== '' ? ' class="language-'.e($language).'"' : '').'>'.$m[2].'</code></pre></div>';
            }, $html) ?? $html;
        }

        return ['html' => $html, 'toc' => $toc];
    }
}
