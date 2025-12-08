<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShareLink extends Model
{
    protected $fillable = [
        'token',
        'type',
        'data',
        'title',
        'expires_at',
        'views',
        'created_by_ip',
    ];

    protected $casts = [
        'data' => 'array',
        'expires_at' => 'datetime',
        'views' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->token)) {
                $model->token = static::generateUniqueToken();
            }
        });
    }

    public static function generateUniqueToken(): string
    {
        do {
            $token = Str::random(32);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    public function incrementViews(): void
    {
        $this->increment('views');
    }

    public function getShareUrl(): string
    {
        return url("/share/{$this->token}");
    }
}
