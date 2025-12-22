<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluginKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'plugin_client_id',
        'public_key',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function pluginClient(): BelongsTo
    {
        return $this->belongsTo(PluginClient::class);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
