<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ist ein Punkt des Leistungsumfangs in einer Auspraegung enthalten – optional
 * mit Hinweis (z. B. „bis 5 Nutzer“).
 */
class ProductFeatureValue extends Model
{
    protected $fillable = [
        'product_feature_id',
        'product_version_id',
        'included',
        'note',
    ];

    protected $casts = [
        'included' => 'boolean',
    ];

    public function feature(): BelongsTo
    {
        return $this->belongsTo(ProductFeature::class, 'product_feature_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ProductVersion::class, 'product_version_id');
    }
}
