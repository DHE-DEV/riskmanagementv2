<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PluginDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'plugin_client_id',
        'domain',
        'is_active',
        'uuid',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (PluginDomain $domain) {
            if (empty($domain->uuid)) {
                $domain->uuid = Str::uuid()->toString();
            }
        });
    }

    public function pluginClient(): BelongsTo
    {
        return $this->belongsTo(PluginClient::class);
    }

    public function setDomainAttribute(string $value): void
    {
        // Normalize domain before saving
        $value = preg_replace('#^https?://#', '', $value);
        $value = preg_replace('#^www\.#', '', $value);
        $value = explode('/', $value)[0];
        $value = explode(':', $value)[0];

        $this->attributes['domain'] = strtolower(trim($value));
    }
}
