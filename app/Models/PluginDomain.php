<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluginDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'plugin_client_id',
        'domain',
    ];

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
