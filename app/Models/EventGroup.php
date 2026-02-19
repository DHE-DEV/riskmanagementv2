<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'include_passolution_events',
        'is_active',
    ];

    protected $casts = [
        'include_passolution_events' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function apiClients(): BelongsToMany
    {
        return $this->belongsToMany(ApiClient::class, 'api_client_event_group');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
