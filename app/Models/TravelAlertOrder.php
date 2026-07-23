<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class TravelAlertOrder extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING_CONFIRMATION = 'pending_confirmation';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'customer_id',
        'customer_type',
        'business_type',
        'company',
        'first_name',
        'last_name',
        'email',
        'phone',
        'street',
        'postal_code',
        'city',
        'country',
        'existing_billing',
        'remarks',
        'trial_expires_at',
        'confirmation_token',
        'confirmed_at',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
    ];

    protected $casts = [
        'business_type' => 'array',
        'trial_expires_at' => 'date',
        'confirmed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Der Status ergibt sich aus den Zeitstempeln, damit es keine zweite
     * Wahrheit gibt, die auseinanderlaufen kann.
     */
    public function getStatusAttribute(): string
    {
        return match (true) {
            $this->rejected_at !== null => self::STATUS_REJECTED,
            $this->approved_at !== null => self::STATUS_ACTIVE,
            $this->confirmed_at !== null => self::STATUS_PENDING_APPROVAL,
            default => self::STATUS_PENDING_CONFIRMATION,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_REJECTED => 'Abgelehnt',
            self::STATUS_ACTIVE => 'Freigeschaltet',
            self::STATUS_PENDING_APPROVAL => 'Wartet auf Freischaltung',
            default => 'Wartet auf Bestätigung',
        };
    }

    /**
     * Wann der Bestaetigungslink aus der Mail ungueltig wird.
     */
    public function confirmationExpiresAt(): Carbon
    {
        $days = (int) config('app.travel_alert_confirmation_expire_days', 7);

        return $this->created_at->copy()->addDays(max($days, 1));
    }

    public function confirmationHasExpired(): bool
    {
        return $this->confirmed_at === null && $this->confirmationExpiresAt()->isPast();
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function isRejected(): bool
    {
        return $this->rejected_at !== null;
    }

    /**
     * Wartet auf die Entscheidung eines Mitarbeiters.
     */
    public function awaitsApproval(): bool
    {
        return $this->isConfirmed() && ! $this->isApproved() && ! $this->isRejected();
    }
}
