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
use App\Services\PassolutionApiService;
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
     * Sammelt pro Regel die Versand-Entscheidung samt Begruendung.
     * Wird nur im Dry-Run befuellt (siehe collectDecisions()), damit der
     * Normalbetrieb unveraendert und ohne Zusatzaufwand laeuft.
     *
     * @var array<int, array{rule_id: int, rule_name: string, customer_id: int, event_id: int, outcome: string, reason: string, recipient: string|null}>
     */
    private array $decisions = [];

    private bool $collectDecisions = false;

    /**
     * PDS-Reisen je Kunde und Zeitraum, einmal pro Lauf geholt.
     *
     * @var array<string, Collection<int, TdTrip>>
     */
    private array $pdsTripCache = [];

    /**
     * Kunden, deren PDS-Abruf in diesem Lauf fehlgeschlagen ist. Sie werden
     * nicht fuer jedes weitere Event erneut abgefragt.
     *
     * @var array<int, true>
     */
    private array $pdsFailedCustomers = [];

    /**
     * Ist in diesem Lauf mindestens ein PDS-Abruf fehlgeschlagen? Wird vom
     * Dry-Run ausgewertet, damit ein API-Ausfall nicht wie "keine betroffenen
     * Reisen" aussieht.
     */
    private bool $pdsApiFailed = false;

    public function pdsApiFailed(): bool
    {
        return $this->pdsApiFailed;
    }

    /**
     * Kreuzfahrten, deren Haefen PDS in diesem Lauf nicht liefern konnte
     * (Link-Kennungen je Kunde). Ohne Haefen kennt die Pruefung ihre Laender
     * nicht – sie gelten dann nicht als betroffen.
     *
     * @var array<int, array<int, string>>
     */
    private array $pdsPortsMissing = [];

    /**
     * @return array<int, array<int, string>>
     */
    public function pdsPortsMissing(): array
    {
        return $this->pdsPortsMissing;
    }

    /**
     * Aktiviert das Mitschreiben der Versand-Entscheidungen (Dry-Run).
     */
    public function collectDecisions(bool $enabled = true): static
    {
        $this->collectDecisions = $enabled;
        $this->decisions = [];

        return $this;
    }

    /**
     * Liefert die mitgeschriebenen Entscheidungen des letzten Laufs.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDecisions(): array
    {
        return $this->decisions;
    }

    /**
     * Haelt eine Versand-Entscheidung fest (nur wenn collectDecisions aktiv).
     */
    private function decide(
        NotificationRule $rule,
        int $eventId,
        string $outcome,
        string $reason,
        ?string $recipient = null,
    ): void {
        if (! $this->collectDecisions) {
            return;
        }

        $this->decisions[] = [
            'rule_id' => $rule->id,
            'rule_name' => $rule->name,
            'customer_id' => $rule->customer_id,
            'event_id' => $eventId,
            'outcome' => $outcome,
            'reason' => $reason,
            'recipient' => $recipient,
        ];
    }

    /**
     * Versende Benachrichtigungen für ein neues CustomEvent.
     */
    public function processCustomEvent(CustomEvent $event, bool $force = false, ?string $sourceFilter = null): int
    {
        $event->loadMissing(['countries', 'eventType', 'eventTypes']);

        ['countryIds' => $countryIds, 'countryIsoCodes' => $countryIsoCodes, 'categories' => $categories] = $this->matchCriteria($event);

        $placeholders = $this->customEventPlaceholders($event, $categories);

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
     * Platzhalter eines CustomEvents fuer die Mail-Vorlagen.
     *
     * @param  array<int, string>  $categories
     * @return array<string, string>
     */
    private function customEventPlaceholders(CustomEvent $event, array $categories): array
    {
        // unique(): ein Land kann mehrere Standort-Datensaetze haben und wuerde sonst mehrfach erscheinen.
        $countryName = $event->countries->map(fn ($c) => $c->getName('de'))->unique()->implode(', ')
            ?: ($event->country?->getName('de') ?? '');

        // Standorte inkl. Region/Stadt - matcht weiterhin auf Laenderebene, dient nur der Anzeige.
        $locationSummary = $event->locationSummary('de') ?: $countryName;

        // Anzeigename der Kategorien: die Namen der Event-Typen, sonst die der Codes.
        $categoryLabel = $event->eventTypes->pluck('name')->implode(', ');
        if (!$categoryLabel) {
            $options = NotificationRule::categoryOptions();
            $categoryLabel = collect($categories)
                ->map(fn ($c) => $options[$c] ?? NotificationRule::CATEGORIES[$c] ?? $c)
                ->implode(', ');
        }

        return [
            '{event_title}' => $event->title,
            '{country_name}' => $countryName,
            '{locations}' => $locationSummary,
            '{risk_level}' => NotificationRule::RISK_LEVELS[$event->priority] ?? $event->priority,
            '{category}' => $categoryLabel,
            '{description}' => $event->description ?? $event->popup_content ?? '',
            '{event_date}' => $event->start_date?->format('d.m.Y') ?? now()->format('d.m.Y'),
            // Versionierung: ab Version 2 handelt es sich um eine Aktualisierung
            // eines bereits gemeldeten Ereignisses.
            '{version}' => (string) ($event->version ?? 1),
            '{version_note}' => (string) ($event->version_note ?? ''),
            '{update_hint}' => ($event->version ?? 1) > 1
                ? 'Aktualisierte Fassung (Version ' . $event->version . ')'
                : '',
        ];
    }

    /** Schutz vor versehentlichem Doppelversand von Hand (Sekunden). */
    private const MANUAL_RESEND_LOCK_SECONDS = 60;

    /**
     * Von Hand: die Benachrichtigung fuer genau EINE Regel und EIN Ereignis
     * senden – auch wenn sie schon einmal verschickt wurde.
     *
     * Die Duplikat-Pruefung und das Stundenlimit gelten hier bewusst nicht.
     * Alles andere ist wie im Versand: Die Regel muss aktiv sein und auf das
     * Ereignis zutreffen, bei Travel Alert muss es betroffene Reisen geben,
     * und abgemeldete Empfaenger bekommen keine Mail. Frueher protokollierte
     * Versendungen bleiben erhalten.
     *
     * @return array{sent: bool, message: string, recipient: ?string}
     */
    public function sendForRule(CustomEvent $event, NotificationRule $rule): array
    {
        $event->loadMissing(['countries', 'eventType', 'eventTypes']);
        $rule->loadMissing(['recipients', 'template', 'customer']);

        $refuse = fn (string $message) => ['sent' => false, 'message' => $message, 'recipient' => null];

        if (! $rule->is_active) {
            return $refuse('Die Regel ist deaktiviert.');
        }

        if (! $rule->customer?->notifications_enabled) {
            return $refuse('Die Benachrichtigungen des Kunden sind ausgeschaltet.');
        }

        ['countryIds' => $countryIds, 'countryIsoCodes' => $countryIsoCodes, 'categories' => $categories] = $this->matchCriteria($event);

        if (! $this->ruleMatches($rule, (string) $event->priority, $categories, $countryIds)) {
            return $refuse('Die Regel trifft auf dieses Ereignis nicht zu.');
        }

        $eventType = get_class($event);

        $justSent = NotificationLog::where('notification_rule_id', $rule->id)
            ->forEvent($event->id, $eventType)
            ->where('created_at', '>=', now()->subSeconds(self::MANUAL_RESEND_LOCK_SECONDS))
            ->exists();

        if ($justSent) {
            return $refuse('Für diese Regel wurde gerade erst eine Benachrichtigung verschickt. Bitte einen Moment warten.');
        }

        $placeholders = $this->customEventPlaceholders($event, $categories);
        $affectedTrips = null;

        if (($rule->source ?? NotificationRule::SOURCE_TRAVEL_ALERT) === NotificationRule::SOURCE_TRAVEL_ALERT) {
            $affectedTrips = $this->findAffectedTrips($rule->customer_id, array_map('strtoupper', $countryIsoCodes), $event);

            if ($affectedTrips->isEmpty()) {
                return $refuse('Es sind keine Reisen des Kunden betroffen.');
            }

            $placeholders['{affected_trips}'] = $this->buildAffectedTripsHtml($affectedTrips);
            $placeholders['{affected_trips_count}'] = (string) $affectedTrips->count();
        } else {
            $placeholders['{affected_trips}'] = '';
            $placeholders['{affected_trips_count}'] = '0';
        }

        // Die Begruendung des Versands mitlesen, ohne einen laufenden Mitschnitt zu stoeren.
        [$wasCollecting, $previousDecisions] = [$this->collectDecisions, $this->decisions];
        $this->collectDecisions = true;
        $this->decisions = [];

        try {
            $sentEmails = [];
            $sent = $this->sendNotification($rule, $placeholders, $event->id, $eventType, $sentEmails, $affectedTrips ?? null);
            $decision = $this->decisions[0] ?? null;
        } finally {
            $this->collectDecisions = $wasCollecting;
            $this->decisions = $previousDecisions;
        }

        Log::info('Benachrichtigung von Hand ausgeloest', [
            'rule_id' => $rule->id,
            'event_id' => $event->id,
            'sent' => $sent,
            'user_id' => auth('web')->id(),
        ]);

        return [
            'sent' => $sent,
            'message' => $sent
                ? 'Benachrichtigung versendet.'
                : 'Nicht versendet: '.str_replace(['Empfaenger', 'fuer'], ['Empfänger', 'für'], $decision['reason'] ?? 'unbekannter Grund').'.',
            'recipient' => $decision['recipient'] ?? null,
        ];
    }

    /**
     * Woran die Regeln ein CustomEvent messen: Laender (IDs fuer GTM-Regeln,
     * ISO-Codes fuer den Abgleich mit Reisen) und Kategorien (Codes der Event-Typen).
     *
     * @return array{countryIds: array<int, int>, countryIsoCodes: array<int, string>, categories: array<int, string>}
     */
    private function matchCriteria(CustomEvent $event): array
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

        // Kategorien aus eventTypes ableiten (category-Feld ist oft NULL).
        // Massgeblich ist der Code des Event-Typs - der Abgleich mit den Regeln
        // laeuft in ruleMatches() ueber dieselben Codes.
        $categories = $event->eventTypes->pluck('code')->unique()->values()->toArray();

        // Fallback auf das category-Feld des Events
        if (empty($categories) && $event->category) {
            $categories = [NotificationRule::normalizeCategory($event->category)];
        }

        return compact('countryIds', 'countryIsoCodes', 'categories');
    }

    /**
     * Vorschau fuer EIN Ereignis: Welche Regeln welcher Kunden wuerden greifen?
     *
     * Es wird nichts versendet und nichts protokolliert. Geprueft wird mit
     * denselben Bausteinen wie im Versand (ruleMatches, findAffectedTrips),
     * aber ueber ALLE Regeln – auch inaktive und solche von Kunden mit
     * abgeschaltetem Versand –, damit sichtbar wird, woran es jeweils liegt.
     *
     * @param  array<int, int>|null  $customerIds  nur Regeln dieser Kunden; null = alle
     * @return array<int, array{
     *     rule: NotificationRule,
     *     source: string,
     *     criteria_match: bool,
     *     would_notify: bool,
     *     reasons: array<int, string>,
     *     recipient: ?string,
     *     affected_trips: ?Collection,
     *     already_sent_at: ?\Illuminate\Support\Carbon,
     * }>
     */
    public function previewRulesForEvent(CustomEvent $event, ?array $customerIds = null): array
    {
        ['countryIds' => $countryIds, 'countryIsoCodes' => $countryIsoCodes, 'categories' => $categories] = $this->matchCriteria($event);

        $eventCountryIsos = array_map('strtoupper', $countryIsoCodes);
        $eventType = get_class($event);

        $rules = NotificationRule::with(['recipients', 'customer'])
            ->when($customerIds !== null, fn ($query) => $query->whereIn('customer_id', $customerIds))
            ->get();

        $lastSent = NotificationLog::query()
            ->forEvent($event->id, $eventType)
            ->byStatus('sent')
            ->orderBy('created_at')
            ->get()
            ->keyBy('notification_rule_id');

        $results = [];

        foreach ($rules as $rule) {
            $source = $rule->source ?? NotificationRule::SOURCE_TRAVEL_ALERT;
            $isTravelAlert = $source === NotificationRule::SOURCE_TRAVEL_ALERT;
            $reasons = [];

            // 1. Kriterien der Regel
            if (! empty($rule->risk_levels) && ! in_array($event->priority, $rule->risk_levels)) {
                $reasons[] = 'Priorität passt nicht';
            }

            if (! empty($rule->categories)) {
                $ruleCategories = NotificationRule::normalizeCategories($rule->categories);
                $eventCategories = NotificationRule::normalizeCategories($categories);

                if (empty($eventCategories) || empty(array_intersect($ruleCategories, $eventCategories))) {
                    $reasons[] = 'Event-Typ passt nicht';
                }
            }

            if (! $isTravelAlert && ! empty($rule->country_ids)
                && (empty($countryIds) || empty(array_intersect($rule->country_ids, $countryIds)))) {
                $reasons[] = 'Land passt nicht';
            }

            $criteriaMatch = $this->ruleMatches($rule, (string) $event->priority, $categories, $countryIds);

            // 2. Voraussetzungen fuer den Versand
            $enabled = (bool) $rule->is_active && (bool) $rule->customer?->notifications_enabled;

            if (! $rule->is_active) {
                $reasons[] = 'Regel ist deaktiviert';
            }
            if (! $rule->customer) {
                $reasons[] = 'Kunde nicht mehr vorhanden';
            } elseif (! $rule->customer->notifications_enabled) {
                $reasons[] = 'Benachrichtigungen des Kunden sind ausgeschaltet';
            }

            $recipient = $rule->recipients->where('recipient_type', 'to')->first()?->email;

            if (! $recipient) {
                $reasons[] = 'Kein Empfänger hinterlegt';
            } elseif ($this->isUnsubscribed($recipient, $rule->customer_id)) {
                $reasons[] = 'Empfänger hat sich abgemeldet';
            }

            // 3. Travel Alert: betroffene Reisen – nur dort abrufen, wo die Regel ueberhaupt greifen kann.
            $affectedTrips = null;

            if ($isTravelAlert && $criteriaMatch && $enabled) {
                $affectedTrips = $this->findAffectedTrips($rule->customer_id, $eventCountryIsos, $event);

                if ($affectedTrips->isEmpty()) {
                    $reasons[] = empty($eventCountryIsos)
                        ? 'Keine betroffenen Reisen (Ereignis hat keine Länder)'
                        : 'Keine betroffenen Reisen im Zeitraum';
                }
            }

            $results[] = [
                'rule' => $rule,
                'source' => $source,
                'criteria_match' => $criteriaMatch,
                'would_notify' => $reasons === [],
                'reasons' => $reasons,
                'recipient' => $recipient,
                'affected_trips' => $affectedTrips,
                'already_sent_at' => $lastSent->get($rule->id)?->created_at,
            ];
        }

        return $results;
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
            '{category}' => NotificationRule::categoryOptions()['environment']
                ?? NotificationRule::CATEGORIES['environment'],
            '{description}' => $event->description ?? '',
            '{event_date}' => $event->event_date?->format('d.m.Y') ?? now()->format('d.m.Y'),
            // Katastrophen-Events kennen keine Versionierung - die Platzhalter
            // werden trotzdem geleert, damit sie nicht roh in der Mail landen.
            '{version}' => '1',
            '{version_note}' => '',
            '{update_hint}' => '',
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
        $customEvents = $this->unnotifiedCustomEventsQuery($since)->get();

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
        $disasterEvents = $this->unnotifiedDisasterEventsQuery($since)->get();

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
     * Events, die ein Queue-Lauf betrachtet. Bewusst als eigene Methoden,
     * damit die Diagnose in scopeSummary() nicht von der Verarbeitung
     * abweichen kann.
     */
    private function unnotifiedCustomEventsQuery(\DateTimeInterface $since): \Illuminate\Database\Eloquent\Builder
    {
        return CustomEvent::where('is_active', true)
            ->where('review_status', 'approved')
            ->whereNull('customer_id')
            // Abgeloeste Versionen scheiden ohnehin ueber is_active aus; die
            // Bedingung haelt den Lauf auch bei Altdaten eindeutig.
            ->whereNull('superseded_by_id')
            // Neben frisch angelegten Ereignissen auch solche, die erst spaeter
            // aktiviert wurden - so wird jede neue Version zuverlaessig gemeldet,
            // selbst wenn ihr Entwurf tagelang bearbeitet wurde.
            ->where(function ($query) use ($since) {
                $query->where('created_at', '>=', $since)
                    ->orWhere('activated_at', '>=', $since);
            });
    }

    private function unnotifiedDisasterEventsQuery(\DateTimeInterface $since): \Illuminate\Database\Eloquent\Builder
    {
        return DisasterEvent::where('created_at', '>=', $since);
    }

    /**
     * Zaehlt die Voraussetzungen eines Queue-Laufs, damit ein Dry-Run ohne
     * Ergebnis konkret benennen kann, woran es liegt: fehlen die Events,
     * die Regeln, oder ist der Benachrichtigungs-Schalter der Kunden aus?
     *
     * @return array{lookback_hours: int, custom_events: int, disaster_events: int, rules_total: int, rules_active: int, rules_effective: int, customers_enabled: int}
     */
    public function scopeSummary(string $source): array
    {
        $lookbackHours = (int) config('notifications.lookback_hours', 24);
        $since = now()->subHours($lookbackHours);

        $enabledCustomers = Customer::where('notifications_enabled', true)->pluck('id');

        $rulesForSource = NotificationRule::query()
            ->when(
                \Schema::hasColumn('notification_rules', 'source'),
                fn ($q) => $q->where('source', $source),
            );

        return [
            'lookback_hours' => $lookbackHours,
            'custom_events' => $this->unnotifiedCustomEventsQuery($since)->count(),
            'disaster_events' => $this->unnotifiedDisasterEventsQuery($since)->count(),
            'rules_total' => (clone $rulesForSource)->count(),
            'rules_active' => (clone $rulesForSource)->where('is_active', true)->count(),
            'rules_effective' => (clone $rulesForSource)->where('is_active', true)
                ->whereIn('customer_id', $enabledCustomers)->count(),
            'customers_enabled' => $enabledCustomers->count(),
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
                $this->decide($rule, $eventId, 'skipped', 'Regel matcht nicht (Risiko/Kategorie/Land)');
                continue;
            }

            Log::info('sendMatchingNotifications: Regel matcht', ['rule_id' => $rule->id]);

            // Rate limiting: check per customer per hour
            if ($this->isRateLimited($rule->customer_id)) {
                Log::warning('Rate-Limit erreicht, überspringe Benachrichtigung', [
                    'rule_id' => $rule->id,
                    'customer_id' => $rule->customer_id,
                ]);
                $this->decide($rule, $eventId, 'skipped', 'Rate-Limit erreicht ('.self::RATE_LIMIT_PER_HOUR.'/Stunde)');
                continue;
            }

            // Bei Travel-Alert-Regeln: betroffene Reisen suchen
            $affectedTrips = null;
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
                    $this->decide($rule, $eventId, 'skipped', 'keine betroffenen Reisen'
                        .(empty($eventCountryIsos) ? ' (Event hat keine Laender)' : ''));
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
                            $this->decide($rule, $eventId, 'skipped',
                                "keine neuen Reisen (vorher {$previousCount}, jetzt {$affectedTrips->count()})");
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
                    $this->decide($rule, $eventId, 'skipped', 'bereits versendet');
                    continue;
                }
                $rulePlaceholders['{affected_trips}'] = '';
                $rulePlaceholders['{affected_trips_count}'] = '0';
            }

            if ($this->sendNotification($rule, $rulePlaceholders, $eventId, $eventType, $sentEmails, $affectedTrips ?? null)) {
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
        // Event kann mehrere Kategorien haben, mindestens eine muss übereinstimmen.
        // Beide Seiten werden auf die Event-Typ-Codes normalisiert, damit auch
        // Regeln mit den alten Schluesseln (traffic/security) weiter greifen.
        if (!empty($rule->categories)) {
            $ruleCategories = NotificationRule::normalizeCategories($rule->categories);
            $eventCategories = NotificationRule::normalizeCategories($eventCategories);

            if (empty($eventCategories) || empty(array_intersect($ruleCategories, $eventCategories))) {
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
        ?Collection $affectedTrips = null,
    ): bool {
        $source = $rule->source ?? NotificationRule::SOURCE_TRAVEL_ALERT;
        $template = $rule->template ?? NotificationTemplate::system($source)->first();

        if (!$template) {
            Log::warning('Kein Template gefunden für Notification Rule', ['rule_id' => $rule->id]);
            $this->decide($rule, $eventId, 'skipped', 'keine E-Mail-Vorlage gefunden');
            return false;
        }

        $toRecipient = $rule->recipients->where('recipient_type', 'to')->first();

        if (!$toRecipient) {
            Log::warning('Kein TO-Empfänger für Notification Rule', ['rule_id' => $rule->id]);
            $this->decide($rule, $eventId, 'skipped', 'kein TO-Empfaenger hinterlegt');
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
            $this->decide($rule, $eventId, 'skipped', 'Empfaenger hat fuer dieses Event bereits eine Mail', $recipientEmail);
            return false;
        }

        // Unsubscribe check: skip if recipient has unsubscribed
        if ($this->isUnsubscribed($recipientEmail, $rule->customer_id)) {
            Log::info('Empfänger hat sich abgemeldet, überspringe', [
                'rule_id' => $rule->id,
                'email' => $recipientEmail,
            ]);
            $this->decide($rule, $eventId, 'skipped', 'Empfaenger hat sich abgemeldet', $recipientEmail);
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
            ] + $this->affectedTripsForLog($affectedTrips));

            // Track for deduplication
            $sentEmails[] = $deduplicationKey;

            $this->decide($rule, $eventId, 'sent', $subject !== '' ? $subject : 'versendet', $recipientEmail);

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

            $this->decide($rule, $eventId, 'failed', $e->getMessage(), $recipientEmail);

            return false;
        }
    }

    /**
     * Kennung einer Reise ueber lokale und PDS-Reisen hinweg: die Link-Kennung,
     * ersatzweise die lokale ID.
     */
    private function tripKey(TdTrip $trip): string
    {
        return (string) ($trip->pds_tid ?: $trip->external_trip_id ?: 'trip-'.$trip->id);
    }

    /** Gibt es die Spalte notification_logs.affected_trips schon (Migration eingespielt)? */
    private ?bool $logsStoreTrips = null;

    /**
     * Die in einer Mail genannten Reisen fuers Protokoll – damit sich spaeter
     * je Reise sagen laesst, wann sie gemeldet wurde.
     *
     * @return array{affected_trips?: array<int, array{key: string, name: ?string, start: ?string, end: ?string}>}
     */
    private function affectedTripsForLog(?Collection $trips): array
    {
        if ($trips === null || $trips->isEmpty()) {
            return [];
        }

        // Ohne die Spalte schluege das Protokollieren fehl – und die Mail ginge beim naechsten Lauf erneut raus.
        if (! ($this->logsStoreTrips ??= \Schema::hasColumn('notification_logs', 'affected_trips'))) {
            return [];
        }

        return ['affected_trips' => $trips->map(fn (TdTrip $trip) => [
            'key' => $this->tripKey($trip),
            'name' => $trip->trip_name ?: $trip->booking_reference,
            'start' => $trip->computed_start_at?->format('Y-m-d'),
            'end' => $trip->computed_end_at?->format('Y-m-d'),
        ])->values()->all()];
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

        // Fallback wie in der Reisen-Ansicht: liegen lokal keine Reisen vor,
        // direkt bei PDS nachfragen. Ohne das bleiben Kunden aussen vor, deren
        // Reisen nie nach td_trips synchronisiert wurden.
        if ($trips->isEmpty()) {
            // Den Zeitraum zusaetzlich hier pruefen: verlassen wir uns allein auf
            // den Datumsfilter von PDS, entscheidet am Ende nur noch das Land.
            $trips = $this->fetchPdsTrips($customerId, $eventStartDate, $eventEndDate)
                ->filter(fn (TdTrip $trip) => $this->tripOverlapsPeriod($trip, $eventStartDate, $eventEndDate))
                ->values();
        }

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
     * Holt die Reisen eines Kunden direkt bei PDS – derselbe Endpunkt, den auch
     * die Reisen-Ansicht nutzt. Schluessel ist die pds_account_id des Kunden;
     * sie steht fest in der Datenbank und funktioniert damit auch im Scheduler,
     * wo es keine Session und keine SSO-Identitaet gibt.
     *
     * Ergebnis sind NICHT gespeicherte TdTrip-Instanzen, damit der weitere
     * Ablauf (Laenderfilter, Mail-Baustein) unveraendert damit arbeiten kann.
     *
     * @return Collection<int, TdTrip>
     */
    private function fetchPdsTrips(int $customerId, ?\DateTimeInterface $from, ?\DateTimeInterface $to): Collection
    {
        // Pro Kunde und Zeitraum nur einmal abfragen: findAffectedTrips() wird
        // je Regel UND je Event aufgerufen, sonst entstuenden dutzende HTTP-Calls.
        // Der Zeitraum gehoert in den Schluessel – der Abruf ist auf ihn
        // eingeschraenkt, ein anderes Event braucht also andere Reisen.
        $cacheKey = $customerId.'|'.($from?->format('Y-m-d') ?? '').'|'.($to?->format('Y-m-d') ?? '');

        if (array_key_exists($cacheKey, $this->pdsTripCache)) {
            return $this->pdsTripCache[$cacheKey];
        }

        if (isset($this->pdsFailedCustomers[$customerId])) {
            return collect();
        }

        $accountId = Customer::whereKey($customerId)->value('pds_account_id');

        if (! $accountId) {
            Log::info('findAffectedTrips: Kunde ohne pds_account_id, kein PDS-Abruf', [
                'customer_id' => $customerId,
            ]);

            return $this->pdsTripCache[$cacheKey] = collect();
        }

        ['rows' => $rows, 'ports_missing' => $portsMissing] = $this->fetchPdsTravelDetails((int) $accountId, $from, $to);

        if ($rows === null) {
            // Ein API-Ausfall darf nicht als "keine betroffenen Reisen"
            // durchgehen – sonst bleibt ein Totalausfall unbemerkt.
            Log::warning('findAffectedTrips: PDS-Abruf fehlgeschlagen', [
                'customer_id' => $customerId,
                'pds_account_id' => $accountId,
            ]);
            $this->pdsApiFailed = true;
            $this->pdsFailedCustomers[$customerId] = true;

            return collect();
        }

        if ($portsMissing !== []) {
            $this->pdsPortsMissing[$customerId] = array_values(array_unique(array_merge($this->pdsPortsMissing[$customerId] ?? [], $portsMissing)));
        }

        $trips = collect($rows)->map(fn (array $row) => $this->tripFromPdsRow($row, $customerId));

        Log::info('findAffectedTrips: Reisen von PDS geholt', [
            'customer_id' => $customerId,
            'pds_account_id' => $accountId,
            'count' => $trips->count(),
        ]);

        return $this->pdsTripCache[$cacheKey] = $trips;
    }

    /**
     * Eine Zeile des PDS-Abrufs als (nicht gespeicherte) Reise.
     *
     * @param  array<string, mixed>  $row
     */
    private function tripFromPdsRow(array $row, int $customerId): TdTrip
    {
        $tid = $row['tid'] ?? $row['id'] ?? null;

        $trip = new TdTrip([
            'customer_id' => $customerId,
            'trip_name' => $row['trip_name'] ?? null,
            'booking_reference' => $row['reference_id'] ?? null,
            'external_trip_id' => $tid,
            'pds_tid' => $tid,
            'status' => 'active',
            'computed_start_at' => $row['start_date'] ?? null,
            'computed_end_at' => $row['end_date'] ?? null,
            'countries_visited' => $this->extractPdsCountryCodes($row),
        ]);

        // Nicht persistiert: die Reise existiert nur im Fremdsystem.
        $trip->exists = false;

        return $trip;
    }

    /**
     * Vorschau: die zukuenftigen Reisen (Travel-Detail-Links) eines Kunden und
     * ob sie von diesem Ereignis betroffen sind. Nichts wird versendet.
     *
     * "Betroffen" heisst wie im Versand: Der Reisezeitraum ueberschneidet den
     * des Ereignisses und die Reise fuehrt in eines seiner Laender. Zusaetzlich
     * wird festgehalten, ob der Versand die Reise tatsaechlich mitzaehlt
     * (findAffectedTrips) – weicht das ab, soll es auffallen.
     *
     * @return array{
     *     trips: array<int, array{trip: TdTrip, key: string, in_period: bool, matching_countries: array<int, string>, affected: bool, counted: bool, ports_missing: bool}>,
     *     failed: bool,
     * }
     */
    public function upcomingTripsForEvent(int $customerId, CustomEvent $event): array
    {
        $eventCountryIsos = array_map('strtoupper', $this->matchCriteria($event)['countryIsoCodes']);
        $eventStart = $event->start_date ?? now();
        $eventEnd = $event->end_date;
        $today = now()->startOfDay();

        $key = fn (TdTrip $trip) => $this->tripKey($trip);

        // Lokal gespeicherte Reisen – dieselben Status wie im Versand.
        $trips = TdTrip::where('customer_id', $customerId)
            ->whereIn('status', ['active', 'confirmed'])
            ->where('computed_end_at', '>=', $today)
            ->get()
            ->keyBy($key);

        // Dazu die Travel-Detail-Links aus PDS; lokal Gespeichertes hat Vorrang.
        ['trips' => $pdsTrips, 'failed' => $failed, 'ports_missing' => $portsMissing] = $this->fetchUpcomingPdsTrips($customerId, $today);

        foreach ($pdsTrips as $trip) {
            if (! $trips->has($key($trip))) {
                $trips->put($key($trip), $trip);
            }
        }

        $counted = $this->findAffectedTrips($customerId, $eventCountryIsos, $event)->map($key)->flip();

        $rows = $trips->map(function (TdTrip $trip, string $tripKey) use ($eventCountryIsos, $eventStart, $eventEnd, $counted, $portsMissing) {
            $inPeriod = $this->tripOverlapsPeriod($trip, $eventStart, $eventEnd);
            $matching = array_values(array_intersect(array_map('strtoupper', $trip->countries_visited ?? []), $eventCountryIsos));

            return [
                'trip' => $trip,
                'key' => $tripKey,
                'in_period' => $inPeriod,
                'matching_countries' => $matching,
                'affected' => $inPeriod && $matching !== [],
                'counted' => $counted->has($tripKey),
                'ports_missing' => in_array($tripKey, $portsMissing, true),
            ];
        });

        return [
            'trips' => $rows->values()->all(),
            'failed' => $failed,
        ];
    }

    /**
     * Alle Travel-Detail-Links eines Kunden ab $from. Kreuzfahrten, deren
     * Haefen PDS nicht liefern konnte, stehen in "ports_missing".
     *
     * @return array{trips: Collection, failed: bool, ports_missing: array<int, string>}
     */
    private function fetchUpcomingPdsTrips(int $customerId, \DateTimeInterface $from): array
    {
        $accountId = Customer::whereKey($customerId)->value('pds_account_id');

        if (! $accountId) {
            return ['trips' => collect(), 'failed' => false, 'ports_missing' => []];
        }

        ['rows' => $rows, 'ports_missing' => $portsMissing] = $this->fetchPdsTravelDetails((int) $accountId, $from, null);

        return [
            'trips' => collect($rows ?? [])->map(fn (array $row) => $this->tripFromPdsRow($row, $customerId)),
            'failed' => $rows === null,
            'ports_missing' => $portsMissing,
        ];
    }

    /** Hoechstzahl der Einzelabrufe, mit denen fehlende Haefen nachgeladen werden. */
    private const MAX_CRUISE_PROBES = 10;

    /**
     * Travel-Details eines Accounts im Zeitraum – abgesichert gegen einen
     * Fehler in den Kreuzfahrt-Daten.
     *
     * PDS scheitert am gesamten Abruf, sobald es zu einer einzigen Kreuzfahrt
     * im Zeitraum die Route nicht laden kann. Damit daran nicht alle Reisen
     * des Kunden haengen, wird die Liste dann ohne Kreuzfahrt-Daten geholt und
     * die Haefen werden tageweise nachgeladen: Ein Abruf fuer einen einzelnen
     * Tag gelingt, solange an diesem Tag keine der fehlerhaften Kreuzfahrten
     * unterwegs ist. Was danach noch fehlt, steht in "ports_missing".
     *
     * @return array{rows: array<int, array<string, mixed>>|null, ports_missing: array<int, string>} rows = null: Abruf ganz gescheitert
     */
    private function fetchPdsTravelDetails(int $accountId, ?\DateTimeInterface $from, ?\DateTimeInterface $to): array
    {
        $api = app(PassolutionApiService::class);
        $fromDay = $from?->format('Y-m-d');
        $toDay = $to?->format('Y-m-d');

        $fetch = function (?string $start, ?string $end, bool $withCruiseInfo) use ($api, $accountId): ?array {
            try {
                return $api->fetchTravelDetailsByAccountId($accountId, $start, $end, $withCruiseInfo);
            } catch (\Throwable $e) {
                Log::error('PDS-Abruf der Travel-Details fehlgeschlagen', ['pds_account_id' => $accountId, 'error' => $e->getMessage()]);

                return null;
            }
        };

        if (($rows = $fetch($fromDay, $toDay, true)) !== null) {
            return ['rows' => $rows, 'ports_missing' => []];
        }

        // Nur wenn PDS mit einem Serverfehler geantwortet hat. Ist PDS gar
        // nicht erreichbar, wuerde ein zweiter Versuch nur die Wartezeit verdoppeln.
        if (($api->lastTravelDetailsStatus() ?? 0) < 500 || ($rows = $fetch($fromDay, $toDay, false)) === null) {
            return ['rows' => null, 'ports_missing' => []];
        }

        $tidOf = fn (array $row) => (string) ($row['tid'] ?? $row['id'] ?? '');
        $rows = collect($rows)->keyBy($tidOf);

        // Kreuzfahrten ohne Haefen – die naechsten zuerst, falls die Abrufe nicht fuer alle reichen.
        $missing = $rows
            ->filter(fn (array $row) => ! empty($row['cruise_compass']))
            ->sortBy(fn (array $row) => (string) ($row['start_date'] ?? ''))
            ->keys()
            ->flip();

        $triedDays = [];

        foreach ($missing->keys() as $tid) {
            $start = substr((string) ($rows[$tid]['start_date'] ?? ''), 0, 10);
            $end = substr((string) ($rows[$tid]['end_date'] ?? ''), 0, 10);

            // Erst der erste, dann der letzte Reisetag innerhalb des Zeitraums.
            $days = array_unique(array_filter([
                $fromDay ? max($start, $fromDay) : $start,
                $toDay ? min($end, $toDay) : $end,
            ]));

            foreach ($days as $day) {
                if (! $missing->has($tid) || isset($triedDays[$day]) || count($triedDays) >= self::MAX_CRUISE_PROBES) {
                    continue;
                }

                $triedDays[$day] = true;

                foreach ($fetch($day, $day, true) ?? [] as $row) {
                    if ($rows->has($tidOf($row))) {
                        $rows->put($tidOf($row), $row);
                        $missing->forget($tidOf($row));
                    }
                }
            }
        }

        $portsMissing = $missing->keys()->map(fn ($tid) => (string) $tid)->all();

        Log::warning('PDS: Abruf mit Kreuzfahrt-Daten gescheitert, Haefen einzeln nachgeladen', [
            'pds_account_id' => $accountId,
            'from' => $fromDay,
            'to' => $toDay,
            'einzelabrufe' => count($triedDays),
            'kreuzfahrten_ohne_haefen' => $portsMissing,
        ]);

        return ['rows' => $rows->values()->all(), 'ports_missing' => $portsMissing];
    }

    /**
     * Ueberschneidet sich die Reise mit dem Zeitraum? Verglichen wird wie beim
     * PDS-Abruf auf Tagesebene ueber die gesamte Reisedauer – auch bei
     * Kreuzfahrten, deren Haefen keinem einzelnen Tag zugeordnet werden.
     * $to = null steht fuer ein offenes Event.
     */
    private function tripOverlapsPeriod(TdTrip $trip, \DateTimeInterface $from, ?\DateTimeInterface $to): bool
    {
        $tripStart = $trip->computed_start_at?->format('Y-m-d');
        $tripEnd = $trip->computed_end_at?->format('Y-m-d');

        if (! $tripStart || ! $tripEnd) {
            return false;
        }

        if ($tripEnd < $from->format('Y-m-d')) {
            return false;
        }

        return ! $to || $tripStart <= $to->format('Y-m-d');
    }

    /**
     * Laendercodes einer PDS-Reise: normale Ziele, bei Kreuzfahrten zusaetzlich
     * die Haefen. Gleiche Logik wie in der Reisen-Ansicht.
     *
     * @param  array<string, mixed>  $row
     * @return array<int, string>
     */
    private function extractPdsCountryCodes(array $row): array
    {
        $codes = array_map('strtoupper', $row['destinations'] ?? []);

        if (! empty($row['cruise']['port_calls']) && is_array($row['cruise']['port_calls'])) {
            foreach ($row['cruise']['port_calls'] as $portCall) {
                if ($code = ($portCall['port']['country']['code'] ?? null)) {
                    $codes[] = strtoupper($code);
                }
            }
        }

        return array_values(array_unique($codes));
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
