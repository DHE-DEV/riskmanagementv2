<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class PluginUsageEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'plugin_client_id',
        'public_key',
        'domain',
        'path',
        'event_type',
        'meta',
        'ip_hash',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function pluginClient(): BelongsTo
    {
        return $this->belongsTo(PluginClient::class);
    }

    public static function hashIp(string $ip): string
    {
        return hash('sha256', $ip . config('app.key'));
    }

    public static function log(
        PluginClient $client,
        string $publicKey,
        string $domain,
        ?string $path = null,
        string $eventType = 'page_load',
        ?array $meta = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): self {
        return self::create([
            'plugin_client_id' => $client->id,
            'public_key' => $publicKey,
            'domain' => $domain,
            'path' => $path,
            'event_type' => $eventType,
            'meta' => $meta,
            'ip_hash' => $ip ? self::hashIp($ip) : null,
            'user_agent' => $userAgent ? substr($userAgent, 0, 255) : null,
            'created_at' => now(),
        ]);
    }

    public function scopeForClient($query, PluginClient $client)
    {
        return $query->where('plugin_client_id', $client->id);
    }

    public function scopeLastDays($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }
}
