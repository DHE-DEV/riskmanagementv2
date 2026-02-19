<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class ApiClient extends Model
{
    use HasFactory, SoftDeletes, HasApiTokens;

    protected $fillable = [
        'name',
        'company_name',
        'contact_email',
        'logo_path',
        'status',
        'auto_approve_events',
        'rate_limit',
        'description',
    ];

    protected $casts = [
        'auto_approve_events' => 'boolean',
        'rate_limit' => 'integer',
    ];

    protected $attributes = [
        'status' => 'active',
        'auto_approve_events' => false,
        'rate_limit' => 60,
    ];

    public function customEvents(): HasMany
    {
        return $this->hasMany(CustomEvent::class);
    }

    public function requestLogs(): HasMany
    {
        return $this->hasMany(ApiClientRequestLog::class);
    }

    public function eventGroups(): BelongsToMany
    {
        return $this->belongsToMany(EventGroup::class, 'api_client_event_group');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getLogoUrl(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }
}
