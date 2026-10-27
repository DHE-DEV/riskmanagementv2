<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ein Punkt des Leistungsumfangs eines Produkts, z. B. „Einreisebestimmungen
 * als PDF“ – je Auspraegung enthalten oder nicht (ProductFeatureValue).
 */
class ProductFeature extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'description',
        'sort_order',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductFeatureValue::class);
    }
}
