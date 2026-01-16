<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class PluginClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'company_name',
        'contact_name',
        'email',
        'street',
        'house_number',
        'postal_code',
        'city',
        'country',
        'status',
        'allow_app_access',
    ];

    protected $casts = [
        'status' => 'string',
        'allow_app_access' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (PluginClient $client) {
            // Delete all related records
            $client->usageEvents()->delete();
            $client->domains()->delete();
            $client->keys()->delete();
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function keys(): HasMany
    {
        return $this->hasMany(PluginKey::class);
    }

    public function activeKey(): HasOne
    {
        return $this->hasOne(PluginKey::class)->where('is_active', true)->latestOfMany();
    }

    public function domains(): HasMany
    {
        return $this->hasMany(PluginDomain::class);
    }

    public function usageEvents(): HasMany
    {
        return $this->hasMany(PluginUsageEvent::class);
    }

    public function generateKey(): PluginKey
    {
        // Deactivate existing keys
        $this->keys()->update(['is_active' => false]);

        // Generate new key: pk_live_ + 32 random chars
        $publicKey = 'pk_live_' . Str::random(32);

        // Ensure uniqueness
        while (PluginKey::where('public_key', $publicKey)->exists()) {
            $publicKey = 'pk_live_' . Str::random(32);
        }

        return $this->keys()->create([
            'public_key' => $publicKey,
            'is_active' => true,
        ]);
    }

    public function hasDomain(string $domain): bool
    {
        // Normalize domain (remove protocol, www, trailing slash)
        $domain = $this->normalizeDomain($domain);

        return $this->domains()
            ->whereRaw('LOWER(domain) = ?', [strtolower($domain)])
            ->exists();
    }

    public function addDomain(string $domain): PluginDomain
    {
        $domain = $this->normalizeDomain($domain);

        return $this->domains()->firstOrCreate(['domain' => $domain]);
    }

    protected function normalizeDomain(string $domain): string
    {
        // Remove protocol
        $domain = preg_replace('#^https?://#', '', $domain);
        // Remove www.
        $domain = preg_replace('#^www\.#', '', $domain);
        // Remove trailing slash and path
        $domain = explode('/', $domain)[0];
        // Remove port
        $domain = explode(':', $domain)[0];

        return strtolower(trim($domain));
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getEmbedSnippet(): string
    {
        $key = $this->activeKey?->public_key ?? 'YOUR_API_KEY';
        $appUrl = config('app.url');

        return <<<HTML
<!-- Global Travel Monitor Plugin - Embed Options (use one): -->
<iframe src="{$appUrl}/embed/events?key={$key}" width="100%" height="600" frameborder="0"></iframe>
<iframe src="{$appUrl}/embed/map?key={$key}" width="100%" height="600" frameborder="0"></iframe>
<iframe src="{$appUrl}/embed/dashboard?key={$key}" width="100%" height="800" frameborder="0"></iframe>
HTML;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
