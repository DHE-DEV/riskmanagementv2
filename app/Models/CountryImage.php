<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Ein Bild zu einem Land: Titelbild (hero) oder Galerie. Alt-Text und
 * Bildunterschrift je Sprache, dazu Urheber, Lizenz und Quelle.
 */
class CountryImage extends Model
{
    public const KIND_HERO = 'hero';

    public const KIND_GALLERY = 'gallery';

    protected $fillable = [
        'country_id',
        'kind',
        'disk',
        'path',
        'thumb_path',
        'original_name',
        'mime_type',
        'width',
        'height',
        'size',
        'focal_x',
        'focal_y',
        'alt_translations',
        'caption_translations',
        'credit',
        'license',
        'source_url',
        'sort_order',
        'is_published',
        'created_by',
    ];

    protected $casts = [
        'alt_translations' => 'array',
        'caption_translations' => 'array',
        'width' => 'integer',
        'height' => 'integer',
        'size' => 'integer',
        'focal_x' => 'float',
        'focal_y' => 'float',
        'sort_order' => 'integer',
        'is_published' => 'boolean',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isHero(): bool
    {
        return $this->kind === self::KIND_HERO;
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Verkleinerte Fassung – oder das Original, wenn keine erzeugt wurde (SVG).
     */
    public function thumbUrl(): string
    {
        return $this->thumb_path ? Storage::disk($this->disk)->url($this->thumb_path) : $this->url();
    }

    public function alt(string $locale = 'de'): ?string
    {
        return $this->alt_translations[$locale] ?? $this->alt_translations['en'] ?? null;
    }

    public function caption(string $locale = 'de'): ?string
    {
        return $this->caption_translations[$locale] ?? $this->caption_translations['en'] ?? null;
    }

    /**
     * Darstellung fuer eine API: Adressen, Masse, Texte je Sprache.
     *
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'url' => $this->url(),
            'thumb_url' => $this->thumbUrl(),
            'width' => $this->width,
            'height' => $this->height,
            'focal' => ['x' => $this->focal_x, 'y' => $this->focal_y],
            'alt' => $this->alt_translations ?: (object) [],
            'caption' => $this->caption_translations ?: (object) [],
            'credit' => $this->credit,
            'license' => $this->license,
            'source_url' => $this->source_url,
        ];
    }
}
