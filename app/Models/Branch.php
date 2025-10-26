<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Branch extends Model
{
    protected $fillable = [
        'customer_id',
        'app_code',
        'name',
        'additional',
        'street',
        'house_number',
        'postal_code',
        'city',
        'country',
        'latitude',
        'longitude',
        'is_headquarters',
    ];

    protected $casts = [
        'is_headquarters' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($branch) {
            if (empty($branch->app_code)) {
                $branch->app_code = self::generateUniqueAppCode();
            }
        });
    }

    /**
     * Generiert einen einzigartigen 4-stelligen alphanumerischen App-Code
     */
    private static function generateUniqueAppCode(): string
    {
        do {
            // Generiere 4-stelligen alphanumerischen Code (A-Z, 0-9)
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));
        } while (self::where('app_code', $code)->exists());

        return $code;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
