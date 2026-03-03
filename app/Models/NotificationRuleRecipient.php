<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRuleRecipient extends Model
{
    protected $fillable = [
        'notification_rule_id',
        'email',
        'recipient_type',
    ];

    public const RECIPIENT_TYPES = [
        'to' => 'TO',
        'cc' => 'CC',
        'bcc' => 'BCC',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(NotificationRule::class, 'notification_rule_id');
    }
}
