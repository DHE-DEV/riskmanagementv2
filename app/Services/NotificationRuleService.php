<?php

namespace App\Services;

use App\Mail\RiskEventMail;
use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\Customer;
use App\Models\DisasterEvent;
use App\Models\NotificationLog;
use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use App\Models\NotificationUnsubscribeToken;
use App\Models\TravelDetail\TdTrip;
use Illuminate\Support\Collection;
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
    public function processCustomEvent(CustomEvent $event, bool $force = false, ?string $sourceFilter = null): int
    {
        $event->loadMissing(['countries', 'eventType', 'eventTypes']);

        $countryIds = $event->countries->pluck('id')->toArray();
        if (empty($countryIds) && $event->country_id) {
            $countryIds = [$event->country_id];
        }

        $countryIsoCodes = $event->countries->pluck('iso_code')->toArray();
        if (empty($countryIsoCodes) && $event->country) {
            $countryIsoCodes = [$event->country->iso_code];
        }

        $countryName = $event->countries->map(fn ($c) => $c->getName('de'))->implode(', ')
            ?: ($event->country?->getName('de') ?? '');

        // Kategorien aus eventTypes ableiten (category-Feld ist oft NULL)
        // Mapping EventType code → NotificationRule category key
        $codeToRuleCategory = [
            'environment' => 'environment',
            'travel' => 'traffic',
            'safety' => 'security',
            'entry' => 'entry',
            'health' => 'health',
            'general' => 'general',
        ];

        $categories = [];
        $categoryLabel = '';
        if ($event->eventTypes->isNotEmpty()) {
            $categoryLabel = $event->eventTypes->pluck('name')->implode(', ');
            $categories = $event->eventTypes
                ->map(fn ($et) => $codeToRuleCategory[$et->code] ?? $et->code)
                ->unique()
                ->values()
                ->toArray();
        }
        // Fallback auf das category-Feld des Events
        if (empty($categories) && $event->category) {
            $categories = [$event->category];
        }
        if (!$categoryLabel) {
            $categoryLabel = collect($categories)
                ->map(fn ($c) => NotificationRule::CATEGORIES[$c] ?? $c)
                ->implode(', ');
        }

        $placeholders = [
            '{event_title}' => $event->title,
            '{country_name}' => $countryName,
            '{risk_level}' => NotificationRule::RISK_LEVELS[$event->priority] ?? $event->priority,
            '{category}' => $categoryLabel,
            '{description}' => $event->description ?? $event->popup_content ?? '',
            '{event_date}' => $event->start_date?->format('d.m.Y') ?? now()->format('d.m.Y'),
        ];

        return $this->sendMatchingNotifications(
            event: $event,
            riskLevel: $event->priority,
            categories: $categories,
            countryIds: $countryIds,
            placeholders: $placeholders,
            force: $force,
            sourceFilter: $sourceFilter,
            countryIsoCodes: $countryIsoCodes,
        );
    }

    /**
     * Versende Benachrichtigungen für ein neues DisasterEvent.
     */
    public function processDisasterEvent(DisasterEvent $event, bool $force = false, ?string $sourceFilter = null): int
    {
        $event->loadMissing(['country']);

        $countryIds = $event->country_id ? [$event->country_id] : [];
        $countryIsoCodes = $event->country ? [$event->country->iso_code] : [];

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
            categories: ['environment'],
            countryIds: $countryIds,
            placeholders: $placeholders,
            force: $force,
            sourceFilter: $sourceFilter,
            countryIsoCodes: $countryIsoCodes,
        );
    }

    /**
     * Batch-Verarbeitung: Finde unverarbeitete Events und sende Benachrichtigungen
     * für eine bestimmte Source (Queue).
     *
     * @return array{events_processed: int, notifications_sent: int, errors: int}
     */
    public function processUnnotifiedEvents(string $source): array
    {
        $lookbackHours = config('notifications.lookback_hours', 24);
        $since = now()->subHours($lookbackHours);

        $eventsProcessed = 0;
        $notificationsSent = 0;
        $errors = 0;

        // CustomEvents verarbeiten (für beide Quellen: Travel Alert und GTM)
        $customEvents = CustomEvent::where('is_active', true)
            ->where('review_status', 'approved')
            ->whereNull('customer_id')
            ->where('created_at', '>=', $since)
            ->get();

        foreach ($customEvents as $event) {
            try {
                $sent = $this->processCustomEvent($event, sourceFilter: $source);
                if ($sent > 0) {
                    $eventsProcessed++;
                    $notificationsSent += $sent;
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error('Fehler bei Batch-Verarbeitung CustomEvent', [
                    'source' => $source,
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // DisasterEvents verarbeiten (für beide Quellen)
        $disasterEvents = DisasterEvent::where('created_at', '>=', $since)->get();

        foreach ($disasterEvents as $event) {
            try {
                $sent = $this->processDisasterEvent($event, sourceFilter: $source);
                if ($sent > 0) {
                    $eventsProcessed++;
                    $notificationsSent += $sent;
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error('Fehler bei Batch-Verarbeitung DisasterEvent', [
                    'source' => $source,
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'events_processed' => $eventsProcessed,
            'notifications_sent' => $notificationsSent,
            'errors' => $errors,
        ];
    }

    /**
     * Finde passende Regeln und sende Benachrichtigungen.
     * Dedupliziert Empfänger-E-Mails pro Event.
     */
    private function sendMatchingNotifications(
        CustomEvent|DisasterEvent $event,
        string $riskLevel,
        array $categories,
        array $countryIds,
        array $placeholders,
        bool $force = false,
        ?string $sourceFilter = null,
        array $countryIsoCodes = [],
    ): int {
        $sentCount = 0;
        $sentEmails = []; // Recipient deduplication per event

        // Alle Kunden mit aktivierten Benachrichtigungen
        $customers = Customer::where('notifications_enabled', true)->pluck('id');

        $query = NotificationRule::with(['recipients', 'template'])
            ->where('is_active', true)
            ->whereIn('customer_id', $customers);

        // Source-Filter: nur Regeln der passenden Quelle laden
        if ($sourceFilter && \Schema::hasColumn('notification_rules', 'source')) {
            $query->where('source', $sourceFilter);
        }

        $rules = $query->get();

        $eventId = $event->id;
        $eventType = get_class($event);

        // Force: delete existing logs so unique constraint won't block re-send
        if ($force) {
            NotificationLog::where('event_id', $eventId)
                ->where('event_type', $eventType)
                ->delete();
        }

        Log::info('sendMatchingNotifications: Starte Regelprüfung', [
            'event_id' => $event->id,
            'event_type' => get_class($event),
            'riskLevel' => $riskLevel,
            'categories' => $categories,
            'countryIds' => $countryIds,
            'countryIsoCodes' => $countryIsoCodes,
            'rules_count' => $rules->count(),
            'sourceFilter' => $sourceFilter,
        ]);

        foreach ($rules as $rule) {
            Log::info('sendMatchingNotifications: Prüfe Regel', [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'rule_source' => $rule->source,
                'rule_risk_levels' => $rule->risk_levels,
                'rule_categories' => $rule->categories,
                'rule_country_ids' => $rule->country_ids,
                'customer_id' => $rule->customer_id,
            ]);

            if (!$this->ruleMatches($rule, $riskLevel, $categories, $countryIds)) {
                Log::info('sendMatchingNotifications: Regel matcht NICHT', ['rule_id' => $rule->id]);
                continue;
            }

            Log::info('sendMatchingNotifications: Regel matcht', ['rule_id' => $rule->id]);

            // Rate limiting: check per customer per hour
            if ($this->isRateLimited($rule->customer_id)) {
                Log::warning('Rate-Limit erreicht, überspringe Benachrichtigung', [
                    'rule_id' => $rule->id,
                    'customer_id' => $rule->customer_id,
                ]);
                continue;
            }

            // Bei Travel-Alert-Regeln: betroffene Reisen suchen
            $rulePlaceholders = $placeholders;
            $ruleSource = $rule->source ?? NotificationRule::SOURCE_TRAVEL_ALERT;
            if ($ruleSource === NotificationRule::SOURCE_TRAVEL_ALERT) {
                // Event-Länder verwenden: Abgleich mit countries_visited der Reisen
                // Die Länder kommen vom Event selbst, nicht von der Regel
                $eventCountryIsos = array_map('strtoupper', $countryIsoCodes);

                Log::info('Travel Alert: Prüfe affected_trips', [
                    'rule_id' => $rule->id,
                    'customer_id' => $rule->customer_id,
                    'eventCountryIsoCodes' => $eventCountryIsos,
                ]);

                $affectedTrips = $this->findAffectedTrips($rule->customer_id, $eventCountryIsos, $event);
                $rulePlaceholders['{affected_trips}'] = $this->buildAffectedTripsHtml($affectedTrips);
                $rulePlaceholders['{affected_trips_count}'] = (string) $affectedTrips->count();
                Log::info('Travel Alert: affected_trips Ergebnis', [
                    'rule_id' => $rule->id,
                    'affected_trips_count' => $affectedTrips->count(),
                ]);

                // Travel Alert: Keine Mail senden wenn keine Reisen betroffen sind
                if ($affectedTrips->isEmpty()) {
                    Log::info('Travel Alert: Keine betroffenen Reisen, überspringe', ['rule_id' => $rule->id]);
                    continue;
                }

                // Duplikat-Prüfung mit Trips-Vergleich: erneut senden wenn mehr Reisen betroffen
                if (!$force) {
                    $lastLog = NotificationLog::where('notification_rule_id', $rule->id)
                        ->forEvent($eventId, $eventType)
                        ->byStatus('sent')
                        ->latest('created_at')
                        ->first();

                    if ($lastLog) {
                        $previousCount = $lastLog->affected_trips_count ?? 0;
                        if ($affectedTrips->count() <= $previousCount) {
                            Log::debug('Travel Alert: Keine neuen Reisen betroffen, überspringe', [
                                'rule_id' => $rule->id,
                                'event_id' => $eventId,
                                'previous_trips' => $previousCount,
                                'current_trips' => $affectedTrips->count(),
                            ]);
                            continue;
                        }
                        Log::info('Travel Alert: Neue Reisen betroffen, sende erneut', [
                            'rule_id' => $rule->id,
                            'event_id' => $eventId,
                            'previous_trips' => $previousCount,
                            'current_trips' => $affectedTrips->count(),
                        ]);
                    }
                }
            } else {
                // GTM: Standard-Duplikat-Prüfung (ohne Trips-Vergleich)
                if (!$force && $this->alreadySentForEvent($rule->id, $eventId, $eventType)) {
                    Log::debug('GTM: Notification bereits versendet, überspringe', [
                        'rule_id' => $rule->id,
                        'event_id' => $eventId,
                    ]);
                    continue;
                }
                $rulePlaceholders['{affected_trips}'] = '';
                $rulePlaceholders['{affected_trips_count}'] = '0';
            }

            if ($this->sendNotification($rule, $rulePlaceholders, $eventId, $eventType, $sentEmails)) {
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
        array $eventCategories,
        array $countryIds,
    ): bool {
        // Risk Level Filter: leer = alle Risikostufen matchen
        if (!empty($rule->risk_levels) && !in_array($riskLevel, $rule->risk_levels)) {
            return false;
        }

        // Category Filter: leer = alle Kategorien matchen
        // Event kann mehrere Kategorien haben, mindestens eine muss übereinstimmen
        if (!empty($rule->categories)) {
            if (empty($eventCategories) || empty(array_intersect($rule->categories, $eventCategories))) {
                return false;
            }
        }

        // Country Filter: Bei Travel-Alert-Regeln wird die Länderzuordnung
        // über findAffectedTrips() anhand der Reisedaten geprüft, nicht hier.
        // Nur bei GTM-Regeln wird der manuelle Länderfilter geprüft.
        $ruleSource = $rule->source ?? NotificationRule::SOURCE_TRAVEL_ALERT;
        if ($ruleSource !== NotificationRule::SOURCE_TRAVEL_ALERT && !empty($rule->country_ids)) {
            if (empty($countryIds) || empty(array_intersect($rule->country_ids, $countryIds))) {
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
        $source = $rule->source ?? NotificationRule::SOURCE_TRAVEL_ALERT;
        $template = $rule->template ?? NotificationTemplate::system($source)->first();

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
                'affected_trips_count' => (int) ($placeholders['{affected_trips_count}'] ?? 0),
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

    /**
     * Finde aktive Reisen eines Kunden, die von einem Event betroffen sind.
     * Bei Travel-Alert: countryIsoCodes sind die REGEL-Länder.
     * Leer = alle Reisen im Zeitraum, gefüllt = nur Reisen in diese Länder.
     */
    private function findAffectedTrips(
        int $customerId,
        array $countryIsoCodes,
        CustomEvent|DisasterEvent $event,
    ): Collection {
        // Eventzeitraum bestimmen (bei CustomEvent kann es ein Datumsbereich sein)
        $eventStartDate = $event instanceof CustomEvent
            ? ($event->start_date ?? now())
            : ($event->event_date ?? now());

        // Kein end_date = offenes/andauerndes Event
        $eventEndDate = $event instanceof CustomEvent
            ? $event->end_date
            : $eventStartDate;

        Log::info('findAffectedTrips: Suche Reisen', [
            'customer_id' => $customerId,
            'countryIsoCodes' => $countryIsoCodes,
            'filterByCountries' => !empty($countryIsoCodes),
            'eventStartDate' => $eventStartDate?->toDateTimeString(),
            'eventEndDate' => $eventEndDate?->toDateTimeString() ?? 'offen',
        ]);

        // Überschneidung: Reise endet nach Event-Start
        // Wenn Event ein Enddatum hat: Reise muss auch vor Event-Ende starten
        // Status: active + confirmed berücksichtigen (nicht cancelled/completed/draft)
        $query = TdTrip::where('customer_id', $customerId)
            ->whereIn('status', ['active', 'confirmed'])
            ->where('computed_end_at', '>=', $eventStartDate);

        if ($eventEndDate) {
            $query->where('computed_start_at', '<=', $eventEndDate);
        }

        $trips = $query->with('travellers')->get();

        Log::info('findAffectedTrips: Reisen im Zeitraum', [
            'count' => $trips->count(),
            'trips' => $trips->map(fn ($t) => [
                'id' => $t->id,
                'countries_visited' => $t->countries_visited,
                'computed_start_at' => $t->computed_start_at?->toDateString(),
                'computed_end_at' => $t->computed_end_at?->toDateString(),
            ])->toArray(),
        ]);

        // Wenn keine Länder angegeben: keine Reisen betroffen (Event hat keine Länderzuordnung)
        if (empty($countryIsoCodes)) {
            Log::info('findAffectedTrips: Keine Event-Länder vorhanden, keine Reisen betroffen');
            return collect();
        }

        // Reisen filtern, deren countries_visited mit den Event-Ländern übereinstimmen
        return $trips
            ->filter(function (TdTrip $trip) use ($countryIsoCodes) {
                $tripCountries = $trip->countries_visited ?? [];

                return !empty(array_intersect(
                    array_map('strtoupper', $tripCountries),
                    array_map('strtoupper', $countryIsoCodes),
                ));
            });
    }

    /**
     * Erzeuge HTML-Block mit betroffenen Reisen für die E-Mail.
     */
    /**
     * Public wrapper für buildAffectedTripsHtml (für Test-Mails).
     */
    public function buildAffectedTripsHtmlPublic(Collection $trips): string
    {
        return $this->buildAffectedTripsHtml($trips);
    }

    private function buildAffectedTripsHtml(Collection $trips): string
    {
        if ($trips->isEmpty()) {
            return '';
        }

        $html = '<div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px;">';
        $html .= '<p style="margin: 0 0 10px; font-weight: bold; color: #856404;"><strong>&#9888; Betroffene Reisen (' . $trips->count() . '):</strong></p>';

        foreach ($trips as $trip) {
            $name = $trip->trip_name ?: ($trip->booking_reference ?: 'Reise #' . $trip->id);
            $dates = '';
            if ($trip->computed_start_at && $trip->computed_end_at) {
                $dates = $trip->computed_start_at->format('d.m.Y') . ' – ' . $trip->computed_end_at->format('d.m.Y');
            }
            $countries = implode(', ', $trip->countries_visited ?? []);

            // Quelle: Travel Link oder Travel Data
            $sourceLabel = $trip->pds_share_url ? 'Travel Link' : 'Travel Data';
            $sourceBg = $trip->pds_share_url ? '#d1ecf1' : '#e2e3e5';
            $sourceColor = $trip->pds_share_url ? '#0c5460' : '#383d41';

            $html .= '<div style="margin-bottom: 10px; padding: 10px; background: #ffffff; border: 1px solid #e0c36a; border-radius: 6px;">';
            $html .= '<div style="margin-bottom: 4px;">';
            $html .= '<strong style="font-size: 14px;">' . e($name) . '</strong>';
            $html .= ' <span style="display: inline-block; padding: 1px 8px; font-size: 11px; border-radius: 10px; background: ' . $sourceBg . '; color: ' . $sourceColor . ';">' . $sourceLabel . '</span>';
            $html .= '</div>';

            if ($dates) {
                $html .= '<div style="font-size: 13px; color: #555;">&#128197; ' . $dates . '</div>';
            }
            if ($countries) {
                $html .= '<div style="font-size: 13px; color: #555;">&#127758; Ziele: ' . e($countries) . '</div>';
            }

            // Reisende anzeigen
            if ($trip->relationLoaded('travellers') && $trip->travellers->isNotEmpty()) {
                $travellerNames = $trip->travellers
                    ->map(fn ($t) => $t->full_name ?: $t->email)
                    ->filter()
                    ->implode(', ');
                if ($travellerNames) {
                    $html .= '<div style="font-size: 13px; color: #555;">&#128100; Reisende: ' . e($travellerNames) . '</div>';
                }
            }

            if ($trip->pds_tid || $trip->pds_share_url) {
                $travelDetailsBase = rtrim(config('services.passolution.travel_details_link', 'https://travel-details.eu'), '/');
                $tid = $trip->pds_tid ?: $trip->external_trip_id;
                $travelLink = $tid
                    ? $travelDetailsBase . '/de?tid=' . urlencode($tid) . '&preview'
                    : $trip->pds_share_url;
                $html .= '<div style="margin-top: 4px;"><a href="' . e($travelLink) . '" style="color: #0d6efd; font-size: 13px;">&#128279; Travel Link öffnen</a></div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }
}
