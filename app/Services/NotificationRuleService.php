<?php

namespace App\Services;

use App\Mail\RiskEventMail;
use App\Models\CustomEvent;
use App\Models\Customer;
use App\Models\DisasterEvent;
use App\Models\NotificationLog;
use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use App\Models\NotificationUnsubscribeToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationRuleService
{
    /**
     * Maximum number of emails per customer per hour.
     */
    private const RATE_LIMIT_PER_HOUR = 50;

    /**
     * Versende Benachrichtigungen für ein neues CustomEvent.
     */
    public function processCustomEvent(CustomEvent $event): int
    {
        $event->loadMissing(['countries', 'eventType']);

        $countryIds = $event->countries->pluck('id')->toArray();
        if (empty($countryIds) && $event->country_id) {
            $countryIds = [$event->country_id];
        }

        $countryName = $event->countries->pluck('name')->implode(', ')
            ?: ($event->country?->name ?? '');

        $placeholders = [
            '{event_title}' => $event->title,
            '{country_name}' => $countryName,
            '{risk_level}' => NotificationRule::RISK_LEVELS[$event->priority] ?? $event->priority,
            '{category}' => NotificationRule::CATEGORIES[$event->category] ?? ($event->category ?? ''),
            '{description}' => $event->description ?? '',
            '{event_date}' => $event->start_date?->format('d.m.Y') ?? now()->format('d.m.Y'),
        ];

        return $this->sendMatchingNotifications(
            event: $event,
            riskLevel: $event->priority,
            category: $event->category,
            countryIds: $countryIds,
            placeholders: $placeholders,
        );
    }

    /**
     * Versende Benachrichtigungen für ein neues DisasterEvent.
     */
    public function processDisasterEvent(DisasterEvent $event): int
    {
        $event->loadMissing(['country']);

        $countryIds = $event->country_id ? [$event->country_id] : [];

        // DisasterEvent severity mapping: critical→high für NotificationRule
        $riskLevel = $event->severity === 'critical' ? 'high' : $event->severity;

        $placeholders = [
            '{event_title}' => $event->title,
            '{country_name}' => $event->country?->name ?? ($event->gdacs_country ?? ''),
            '{risk_level}' => NotificationRule::RISK_LEVELS[$riskLevel] ?? $riskLevel,
            '{category}' => NotificationRule::CATEGORIES['environment'] ?? 'Umweltereignisse',
            '{description}' => $event->description ?? '',
            '{event_date}' => $event->event_date?->format('d.m.Y') ?? now()->format('d.m.Y'),
        ];

        return $this->sendMatchingNotifications(
            event: $event,
            riskLevel: $riskLevel,
            category: 'environment',
            countryIds: $countryIds,
            placeholders: $placeholders,
        );
    }

    /**
     * Finde passende Regeln und sende Benachrichtigungen.
     * Dedupliziert Empfänger-E-Mails pro Event.
     */
    private function sendMatchingNotifications(
        CustomEvent|DisasterEvent $event,
        string $riskLevel,
        ?string $category,
        array $countryIds,
        array $placeholders,
    ): int {
        $sentCount = 0;
        $sentEmails = []; // Recipient deduplication per event

        // Alle Kunden mit aktivierten Benachrichtigungen
        $customers = Customer::where('notifications_enabled', true)->pluck('id');

        $rules = NotificationRule::with(['recipients', 'template'])
            ->where('is_active', true)
            ->whereIn('customer_id', $customers)
            ->get();

        $eventId = $event->id;
        $eventType = get_class($event);

        foreach ($rules as $rule) {
            if (!$this->ruleMatches($rule, $riskLevel, $category, $countryIds)) {
                continue;
            }

            // Duplicate prevention: skip if already sent for this rule + event
            if ($this->alreadySentForEvent($rule->id, $eventId, $eventType)) {
                Log::debug('Notification bereits versendet, überspringe', [
                    'rule_id' => $rule->id,
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                ]);
                continue;
            }

            // Rate limiting: check per customer per hour
            if ($this->isRateLimited($rule->customer_id)) {
                Log::warning('Rate-Limit erreicht, überspringe Benachrichtigung', [
                    'rule_id' => $rule->id,
                    'customer_id' => $rule->customer_id,
                ]);
                continue;
            }

            if ($this->sendNotification($rule, $placeholders, $eventId, $eventType, $sentEmails)) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    /**
     * Prüfe ob eine Regel zum Event passt.
     * Leere Filter = alles matcht (kein Filter gesetzt).
     */
    private function ruleMatches(
        NotificationRule $rule,
        string $riskLevel,
        ?string $category,
        array $countryIds,
    ): bool {
        // Risk Level Filter
        if (!empty($rule->risk_levels) && !in_array($riskLevel, $rule->risk_levels)) {
            return false;
        }

        // Category Filter
        if (!empty($rule->categories) && $category && !in_array($category, $rule->categories)) {
            return false;
        }

        // Country Filter
        if (!empty($rule->country_ids) && !empty($countryIds)) {
            if (empty(array_intersect($rule->country_ids, $countryIds))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Prüfe ob für diese Regel + Event bereits eine Benachrichtigung versendet wurde.
     */
    private function alreadySentForEvent(int $ruleId, int $eventId, string $eventType): bool
    {
        return NotificationLog::where('notification_rule_id', $ruleId)
            ->forEvent($eventId, $eventType)
            ->byStatus('sent')
            ->exists();
    }

    /**
     * Prüfe ob der Kunde das Rate-Limit (Emails pro Stunde) überschritten hat.
     */
    private function isRateLimited(int $customerId): bool
    {
        $recentCount = NotificationLog::where('customer_id', $customerId)
            ->where('status', 'sent')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        return $recentCount >= self::RATE_LIMIT_PER_HOUR;
    }

    /**
     * Prüfe ob eine E-Mail-Adresse sich abgemeldet hat.
     */
    private function isUnsubscribed(string $email, int $customerId): bool
    {
        return NotificationUnsubscribeToken::where('email', $email)
            ->where('customer_id', $customerId)
            ->whereNotNull('unsubscribed_at')
            ->exists();
    }

    /**
     * Sende die Benachrichtigung für eine Regel.
     * Deduplication: $sentEmails wird per Referenz übergeben und aktualisiert.
     */
    private function sendNotification(
        NotificationRule $rule,
        array $placeholders,
        int $eventId,
        string $eventType,
        array &$sentEmails,
    ): bool {
        $template = $rule->template ?? NotificationTemplate::system()->first();

        if (!$template) {
            Log::warning('Kein Template gefunden für Notification Rule', ['rule_id' => $rule->id]);
            return false;
        }

        $toRecipient = $rule->recipients->where('recipient_type', 'to')->first();

        if (!$toRecipient) {
            Log::warning('Kein TO-Empfänger für Notification Rule', ['rule_id' => $rule->id]);
            return false;
        }

        $recipientEmail = $toRecipient->email;

        // Recipient deduplication: skip if this email already received a notification for this event
        $deduplicationKey = $recipientEmail . '|' . $eventId . '|' . $eventType;
        if (in_array($deduplicationKey, $sentEmails, true)) {
            Log::debug('Empfänger bereits benachrichtigt, überspringe', [
                'rule_id' => $rule->id,
                'email' => $recipientEmail,
                'event_id' => $eventId,
            ]);
            return false;
        }

        // Unsubscribe check: skip if recipient has unsubscribed
        if ($this->isUnsubscribed($recipientEmail, $rule->customer_id)) {
            Log::info('Empfänger hat sich abgemeldet, überspringe', [
                'rule_id' => $rule->id,
                'email' => $recipientEmail,
            ]);
            return false;
        }

        // Generate unsubscribe token and URL
        $unsubscribeToken = NotificationUnsubscribeToken::generateFor(
            $recipientEmail,
            $rule->customer_id,
            $rule->id,
        );
        $placeholders['{unsubscribe_url}'] = url("/notifications/unsubscribe/{$unsubscribeToken->token}");

        // Resolve subject for logging
        $subject = str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $template->subject ?? '',
        );

        try {
            Mail::to($recipientEmail)
                ->send(new RiskEventMail($template, $placeholders, $rule));

            Log::info('Risk-Event Benachrichtigung versendet', [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'to' => $recipientEmail,
            ]);

            // Log successful send
            NotificationLog::create([
                'notification_rule_id' => $rule->id,
                'customer_id' => $rule->customer_id,
                'event_id' => $eventId,
                'event_type' => $eventType,
                'recipient_email' => $recipientEmail,
                'subject' => $subject,
                'status' => 'sent',
                'error_message' => null,
            ]);

            // Track for deduplication
            $sentEmails[] = $deduplicationKey;

            return true;
        } catch (\Exception $e) {
            Log::error('Fehler beim Versenden der Risk-Event Benachrichtigung', [
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
            ]);

            // Log failed send
            NotificationLog::create([
                'notification_rule_id' => $rule->id,
                'customer_id' => $rule->customer_id,
                'event_id' => $eventId,
                'event_type' => $eventType,
                'recipient_email' => $recipientEmail,
                'subject' => $subject,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
