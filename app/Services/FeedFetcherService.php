<?php

namespace App\Services;

use App\Models\InfoSource;
use App\Models\InfoSourceItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class FeedFetcherService
{
    protected array $stats = [
        'fetched' => 0,
        'new' => 0,
        'updated' => 0,
        'errors' => 0,
    ];

    /**
     * Fetch all active sources that need refresh.
     */
    public function fetchAll(): array
    {
        $sources = InfoSource::active()->needsRefresh()->get();

        foreach ($sources as $source) {
            $this->fetch($source);
        }

        return $this->stats;
    }

    /**
     * Fetch a single source.
     */
    public function fetch(InfoSource $source): array
    {
        $this->stats = ['fetched' => 0, 'new' => 0, 'updated' => 0, 'errors' => 0];

        try {
            Log::info("Fetching source: {$source->name}", ['source_id' => $source->id]);

            $items = match ($source->type) {
                'rss' => $this->fetchRss($source),
                'api' => $this->fetchApi($source),
                'rss_api' => array_merge($this->fetchRss($source), $this->fetchApi($source)),
                default => [],
            };

            foreach ($items as $item) {
                $this->saveItem($source, $item);
            }

            $source->markAsFetched();
            $this->stats['fetched'] = count($items);

            Log::info("Source fetched successfully", [
                'source_id' => $source->id,
                'stats' => $this->stats,
            ]);

        } catch (\Exception $e) {
            $source->markAsError($e->getMessage());
            $this->stats['errors']++;

            Log::error("Error fetching source: {$source->name}", [
                'source_id' => $source->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->stats;
    }

    /**
     * Fetch RSS feed.
     */
    protected function fetchRss(InfoSource $source): array
    {
        if (empty($source->url)) {
            return [];
        }

        $response = Http::timeout(30)->get($source->url);

        if (!$response->successful()) {
            throw new \Exception("HTTP error: {$response->status()}");
        }

        $xml = new SimpleXMLElement($response->body());
        $items = [];

        // Handle RSS 2.0
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $items[] = $this->parseRssItem($item, $source);
            }
        }
        // Handle Atom
        elseif (isset($xml->entry)) {
            foreach ($xml->entry as $entry) {
                $items[] = $this->parseAtomEntry($entry, $source);
            }
        }

        return $items;
    }

    /**
     * Parse RSS 2.0 item.
     */
    protected function parseRssItem(SimpleXMLElement $item, InfoSource $source): array
    {
        $guid = (string) ($item->guid ?? $item->link ?? md5((string) $item->title));

        return [
            'external_id' => $guid,
            'title' => (string) $item->title,
            'description' => $this->cleanHtml((string) $item->description),
            'content' => $this->cleanHtml((string) ($item->children('content', true)->encoded ?? $item->description)),
            'link' => (string) $item->link,
            'author' => (string) ($item->author ?? $item->children('dc', true)->creator ?? ''),
            'categories' => $this->extractCategories($item),
            'published_at' => $this->parseDate((string) $item->pubDate),
            'raw_data' => json_decode(json_encode($item), true),
        ];
    }

    /**
     * Parse Atom entry.
     */
    protected function parseAtomEntry(SimpleXMLElement $entry, InfoSource $source): array
    {
        $id = (string) ($entry->id ?? '');
        $link = '';

        foreach ($entry->link as $linkEl) {
            $rel = (string) $linkEl['rel'];
            if ($rel === 'alternate' || empty($rel)) {
                $link = (string) $linkEl['href'];
                break;
            }
        }

        return [
            'external_id' => $id ?: md5((string) $entry->title),
            'title' => (string) $entry->title,
            'description' => $this->cleanHtml((string) $entry->summary),
            'content' => $this->cleanHtml((string) ($entry->content ?? $entry->summary)),
            'link' => $link,
            'author' => (string) ($entry->author->name ?? ''),
            'categories' => $this->extractAtomCategories($entry),
            'published_at' => $this->parseDate((string) ($entry->published ?? $entry->updated)),
            'raw_data' => json_decode(json_encode($entry), true),
        ];
    }

    /**
     * Fetch API data.
     */
    protected function fetchApi(InfoSource $source): array
    {
        if (empty($source->api_endpoint)) {
            return [];
        }

        $headers = [];
        $query = [];

        // Parse API config
        if ($source->api_config) {
            foreach ($source->api_config as $key => $value) {
                if (str_starts_with($key, 'header_')) {
                    $headers[substr($key, 7)] = $value;
                } elseif (str_starts_with($key, 'query_')) {
                    $query[substr($key, 6)] = $value;
                }
            }
        }

        // Add API key if present
        if ($source->api_key) {
            $headers['Authorization'] = "Bearer {$source->api_key}";
        }

        $response = Http::timeout(30)
            ->withHeaders($headers)
            ->get($source->api_endpoint, $query);

        if (!$response->successful()) {
            throw new \Exception("API error: {$response->status()}");
        }

        $data = $response->json();

        return $this->parseApiResponse($data, $source);
    }

    /**
     * Parse API response - can be overridden for specific APIs.
     */
    protected function parseApiResponse(array $data, InfoSource $source): array
    {
        $items = [];

        // Try common API structures
        $entries = $data['data'] ?? $data['items'] ?? $data['results'] ?? $data['entries'] ?? $data;

        if (!is_array($entries)) {
            return [];
        }

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $items[] = [
                'external_id' => $entry['id'] ?? $entry['guid'] ?? md5(json_encode($entry)),
                'title' => $entry['title'] ?? $entry['headline'] ?? $entry['name'] ?? 'Untitled',
                'description' => $entry['description'] ?? $entry['summary'] ?? $entry['excerpt'] ?? null,
                'content' => $entry['content'] ?? $entry['body'] ?? $entry['text'] ?? null,
                'link' => $entry['url'] ?? $entry['link'] ?? $entry['href'] ?? null,
                'author' => $entry['author'] ?? $entry['source'] ?? null,
                'categories' => $entry['categories'] ?? $entry['tags'] ?? [],
                'published_at' => $this->parseDate($entry['published_at'] ?? $entry['date'] ?? $entry['pubDate'] ?? null),
                'raw_data' => $entry,
            ];
        }

        return $items;
    }

    /**
     * Save item to database.
     */
    protected function saveItem(InfoSource $source, array $data): void
    {
        $existing = InfoSourceItem::where('info_source_id', $source->id)
            ->where('external_id', $data['external_id'])
            ->first();

        // Create content hash for comparison
        $newContentHash = md5(($data['title'] ?? '') . ($data['description'] ?? '') . ($data['content'] ?? ''));

        if ($existing) {
            $oldContentHash = md5(($existing->title ?? '') . ($existing->description ?? '') . ($existing->content ?? ''));

            // Update if content changed
            if ($oldContentHash !== $newContentHash) {
                $existing->update([
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'content' => $data['content'],
                    'link' => $data['link'],
                    'author' => $data['author'],
                    'categories' => $data['categories'],
                    'updated_at_source' => now(),
                    'raw_data' => $data['raw_data'],
                    'status' => $existing->status === 'ignored' ? 'ignored' : 'new', // Mark as new again if content changed
                ]);
                $this->stats['updated']++;

                Log::info("Item updated: {$data['title']}", [
                    'source_id' => $source->id,
                    'item_id' => $existing->id,
                ]);
            }
        } else {
            // Create new
            InfoSourceItem::create([
                'info_source_id' => $source->id,
                'external_id' => $data['external_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'content' => $data['content'],
                'link' => $data['link'],
                'author' => $data['author'],
                'categories' => $data['categories'],
                'published_at' => $data['published_at'],
                'raw_data' => $data['raw_data'],
                'status' => $source->auto_import ? 'imported' : 'new',
            ]);
            $this->stats['new']++;
        }
    }

    /**
     * Extract categories from RSS item.
     */
    protected function extractCategories(SimpleXMLElement $item): array
    {
        $categories = [];

        if (isset($item->category)) {
            foreach ($item->category as $cat) {
                $categories[] = (string) $cat;
            }
        }

        return $categories;
    }

    /**
     * Extract categories from Atom entry.
     */
    protected function extractAtomCategories(SimpleXMLElement $entry): array
    {
        $categories = [];

        if (isset($entry->category)) {
            foreach ($entry->category as $cat) {
                $term = (string) ($cat['term'] ?? $cat['label'] ?? $cat);
                if ($term) {
                    $categories[] = $term;
                }
            }
        }

        return $categories;
    }

    /**
     * Parse date string.
     */
    protected function parseDate(?string $date): ?\DateTime
    {
        if (empty($date)) {
            return null;
        }

        try {
            return new \DateTime($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Clean HTML content.
     */
    protected function cleanHtml(?string $html): ?string
    {
        if (empty($html)) {
            return null;
        }

        // Decode HTML entities
        $text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove scripts and styles
        $text = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $text);
        $text = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $text);

        // Keep some HTML for rich content, but clean it
        $text = strip_tags($text, '<p><br><a><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6>');

        return trim($text);
    }

    /**
     * Get stats from last fetch.
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}
