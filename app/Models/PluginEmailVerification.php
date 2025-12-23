<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PluginEmailVerification extends Model
{
    protected $fillable = [
        'token',
        'email',
        'code',
        'form_data',
        'attempts',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'form_data' => 'encrypted:array',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    protected const MAX_ATTEMPTS = 5;
    protected const EXPIRY_MINUTES = 15;

    public static function createForEmail(string $email, array $formData): self
    {
        return self::create([
            'token' => Str::random(64),
            'email' => $email,
            'code' => self::generateCode(),
            'form_data' => $formData,
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);
    }

    public static function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function hasExceededAttempts(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    public function markAsVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }

    public function regenerateCode(): string
    {
        $newCode = self::generateCode();
        $this->update([
            'code' => $newCode,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);
        return $newCode;
    }

    public function verifyCode(string $code): bool
    {
        return $this->code === $code;
    }

    public function getRemainingAttempts(): int
    {
        return max(0, self::MAX_ATTEMPTS - $this->attempts);
    }

    public function getExpiryMinutes(): int
    {
        return self::EXPIRY_MINUTES;
    }
}
