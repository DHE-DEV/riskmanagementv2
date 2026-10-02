<?php

namespace App\Livewire\AdminV2\Events;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomEvent;
use App\Models\NotificationLog;
use App\Models\NotificationRule;
use App\Services\NotificationRuleService;
use App\Support\AdminV2\EventState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Welche Benachrichtigungsregeln der Kunden wuerden bei diesem Ereignis greifen?
 *
 * Reine Vorschau: Es wird nichts versendet und nichts protokolliert. Die
 * Auswertung laeuft erst nach dem Seitenaufbau (wire:init), weil fuer
 * Travel-Alert-Regeln die Reisen der Kunden abgefragt werden.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Regeln der Kunden')]
class RuleCheck extends Component
{
    use AuthorizesAdminV2;

    #[Locked]
    public int $eventId;

    /** Kundennummer (PDS-Account-ID); leer = alle Kunden. */
    #[Url(as: 'customer', except: '')]
    public string $customerNumber = '';

    /** Eingabefeld zum Wechseln des Kunden. */
    public string $customerInput = '';

    /** matching = nur zutreffende Regeln, all = alle */
    #[Url(except: 'matching')]
    public string $show = 'matching';

    public bool $loaded = false;

    /** @var array<int, array<string, mixed>> Ergebnis in anzeigefertiger Form */
    public array $results = [];

    public bool $pdsFailed = false;

    /** Kreuzfahrten, zu denen PDS bei der Regel-Pruefung keine Haefen liefern konnte. */
    public int $portsMissing = 0;

    // Versandverlauf: Filter, Sortierung und Seite
    /** sent | failed; leer = alle */
    public string $historyStatus = '';

    /** Quelle der Regel (Travel Alert / Global Travel Monitor); leer = alle */
    public string $historySource = '';

    /** ID einer Regel; leer = alle */
    public string $historyRule = '';

    /** Empfaenger, Betreff oder Kunde */
    public string $historySearch = '';

    public string $historyFrom = '';

    public string $historyTo = '';

    /** desc = neueste zuerst */
    public string $historyDirection = 'desc';

    public int $historyPage = 1;

    private const HISTORY_PER_PAGE = 10;

    /** Regel, deren Benachrichtigung gerade von Hand gesendet werden soll (Rueckfrage-Dialog). */
    public ?int $sendRuleId = null;

    /** Zukuenftige Reisen (Travel-Detail-Links) des gewaehlten Kunden – erst auf Knopfdruck. */
    public bool $tripsLoaded = false;

    /** @var array<int, array<string, mixed>> */
    public array $trips = [];

    public bool $tripsFailed = false;

    /** all = alle zukuenftigen Reisen, affected = nur betroffene, unaffected = nur nicht betroffene */
    public string $tripsShow = 'all';

    /** Name, Referenz oder Link-Kennung */
    public string $tripsSearch = '';

    /** Name eines Landes; leer = alle */
    public string $tripsCountry = '';

    /** Reisen, die im Zeitraum unterwegs sind (Y-m-d) */
    public string $tripsFrom = '';

    public string $tripsTo = '';

    /** reported = schon in einer Mail genannt, unreported = noch nicht */
    public string $tripsReported = '';

    public function mount($event): void
    {
        $this->eventId = CustomEvent::withTrashed()->findOrFail((int) $event)->id;
        $this->customerInput = $this->customerNumber;
    }

    #[Computed]
    public function event(): CustomEvent
    {
        return CustomEvent::withTrashed()->with(['countries', 'eventTypes'])->findOrFail($this->eventId);
    }

    /**
     * Kunden zur Kundennummer. Mehrere lokale Kunden koennen zu einem
     * PDS-Account gehoeren; ersatzweise passt die interne Kunden-ID.
     */
    #[Computed]
    public function customers(): Collection
    {
        return self::customersFor($this->customerNumber);
    }

    public static function customersFor(string $number): Collection
    {
        $number = trim($number);

        if ($number === '' || ! ctype_digit($number)) {
            return collect();
        }

        $byAccount = Customer::query()->where('pds_account_id', (int) $number)->orderBy('id')->get();

        return $byAccount->isNotEmpty()
            ? $byAccount
            : Customer::query()->whereKey((int) $number)->get();
    }

    public function applyCustomer(): void
    {
        $number = trim($this->customerInput);

        if ($number !== '' && self::customersFor($number)->isEmpty()) {
            $this->addError('customerInput', "Zur Kundennummer {$number} gibt es keinen Kunden.");

            return;
        }

        $this->resetErrorBag('customerInput');
        $this->customerNumber = $number;
        unset($this->customers);
        $this->sendRuleId = null;
        $this->reset(['tripsLoaded', 'trips', 'tripsFailed']);

        $this->evaluate();
    }

    public function showAllCustomers(): void
    {
        $this->customerInput = '';
        $this->applyCustomer();
    }

    /**
     * Die Regeln auswerten – ohne Versand und ohne Protokoll.
     */
    public function evaluate(): void
    {
        // Fuer Travel-Alert-Regeln werden Reisen ggf. einzeln bei PDS abgefragt.
        set_time_limit(180);

        $service = app(NotificationRuleService::class);
        $customerIds = $this->customerNumber !== '' ? $this->customers->pluck('id')->all() : null;

        $preview = $service->previewRulesForEvent($this->event, $customerIds);

        $countryNames = Country::query()
            ->whereIn('id', collect($preview)->flatMap(fn ($row) => $row['rule']->country_ids ?? [])->unique())
            ->get()
            ->mapWithKeys(fn (Country $country) => [$country->id => $country->getName('de')]);

        $categoryOptions = NotificationRule::categoryOptions();

        $this->results = collect($preview)
            ->map(function (array $row) use ($countryNames, $categoryOptions) {
                /** @var NotificationRule $rule */
                $rule = $row['rule'];
                $customer = $rule->customer;

                return [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'source' => $row['source'],
                    'customer_id' => $rule->customer_id,
                    'customer_name' => $customer ? (trim((string) ($customer->company_name ?: $customer->name)) ?: $customer->email) : 'Gelöschter Kunde',
                    'customer_number' => $customer?->pds_account_id,
                    'would_notify' => $row['would_notify'],
                    'criteria_match' => $row['criteria_match'],
                    'reasons' => $row['reasons'],
                    'recipient' => $row['recipient'],
                    'risk_levels' => collect($rule->risk_levels ?? [])->map(fn ($level) => NotificationRule::RISK_LEVELS[$level] ?? $level)->all(),
                    'categories' => collect(NotificationRule::normalizeCategories($rule->categories))->map(fn ($code) => $categoryOptions[$code] ?? $code)->all(),
                    'countries' => collect($rule->country_ids ?? [])->map(fn ($id) => $countryNames[$id] ?? '#'.$id)->all(),
                    'trips' => $row['affected_trips']?->map(fn ($trip) => [
                        'name' => $trip->trip_name ?: ($trip->booking_reference ?: 'Reise '.($trip->pds_tid ?? $trip->id)),
                        'period' => ($trip->computed_start_at?->format('d.m.Y') ?? '?').' – '.($trip->computed_end_at?->format('d.m.Y') ?? '?'),
                        'countries' => implode(', ', array_map('strtoupper', $trip->countries_visited ?? [])),
                    ])->values()->all(),
                    'already_sent_at' => $row['already_sent_at']?->format('d.m.Y H:i'),
                ];
            })
            // Zutreffende zuerst, dann nach Kundenname.
            ->sortBy([['would_notify', 'desc'], ['customer_name', 'asc'], ['rule_name', 'asc']])
            ->values()
            ->all();

        // Der Versandverlauf kann sich geaendert haben (z. B. nach dem Senden von Hand).
        unset($this->history, $this->historyRules, $this->tripMails, $this->mailsWithoutTripList);
        $this->pdsFailed = $service->pdsApiFailed();
        $this->portsMissing = collect($service->pdsPortsMissing())->flatten()->unique()->count();
        $this->loaded = true;
    }

    /**
     * Alle Mails zu diesem Ereignis (allen seinen Versionen) – bei gewaehlter
     * Kundennummer nur die an diesen Kunden.
     */
    protected function historyQuery(): Builder
    {
        return NotificationLog::query()
            ->where('event_type', CustomEvent::class)
            ->whereIn('event_id', $this->versions->keys())
            ->when($this->customerNumber !== '', fn (Builder $query) => $query->whereIn('customer_id', $this->customers->pluck('id')));
    }

    /**
     * Jede Version ist ein eigener Datensatz – der Verlauf umfasst alle.
     * Versions-ID => Versionsnummer.
     */
    #[Computed]
    public function versions(): Collection
    {
        $event = $this->event;

        return $event->version_group_uuid
            ? CustomEvent::withTrashed()->where('version_group_uuid', $event->version_group_uuid)->pluck('version', 'id')
            : collect([$event->id => $event->version]);
    }

    /**
     * Die Regeln, die im Verlauf vorkommen – auch inzwischen geloeschte.
     */
    #[Computed]
    public function historyRules(): Collection
    {
        return NotificationRule::withTrashed()
            ->whereIn('id', $this->historyQuery()->whereNotNull('notification_rule_id')->distinct()->pluck('notification_rule_id'))
            ->orderBy('name')
            ->get()
            ->keyBy('id');
    }

    /**
     * Welche Mails sind zu diesem Ereignis wann rausgegangen? Gespeichert sind
     * Zeitpunkt, Regel, Empfaenger, Betreff, Status und die Zahl der Reisen –
     * welche Reisen genannt wurden, erst seit es die Spalte affected_trips gibt.
     *
     * @return array{rows: array<int, array<string, mixed>>, total: int, filtered: int, page: int, pages: int}
     */
    #[Computed]
    public function history(): array
    {
        $total = $this->historyQuery()->count();
        $query = $this->historyQuery()->with('customer');

        if (in_array($this->historyStatus, ['sent', 'failed'], true)) {
            $query->where('status', $this->historyStatus);
        }

        if (in_array($this->historySource, [NotificationRule::SOURCE_TRAVEL_ALERT, NotificationRule::SOURCE_GLOBAL_TRAVEL_MONITOR], true)) {
            $query->whereIn('notification_rule_id', $this->historyRules
                ->filter(fn (NotificationRule $rule) => ($rule->source ?? NotificationRule::SOURCE_TRAVEL_ALERT) === $this->historySource)
                ->keys());
        }

        if (($ruleId = (int) $this->historyRule) > 0) {
            $query->where('notification_rule_id', $ruleId);
        }

        if (($search = trim($this->historySearch)) !== '') {
            $like = '%'.$search.'%';

            $query->where(fn (Builder $q) => $q
                ->where('recipient_email', 'like', $like)
                ->orWhere('subject', 'like', $like)
                ->orWhere('rule_name', 'like', $like)
                ->orWhereHas('customer', fn (Builder $customer) => $customer
                    ->where('company_name', 'like', $like)
                    ->orWhere('name', 'like', $like)));
        }

        if ($from = $this->parseDate($this->historyFrom)) {
            $query->where('created_at', '>=', $from->startOfDay());
        }

        if ($to = $this->parseDate($this->historyTo)) {
            $query->where('created_at', '<=', $to->endOfDay());
        }

        $filtered = (clone $query)->count();
        $pages = max(1, (int) ceil($filtered / self::HISTORY_PER_PAGE));
        $page = min(max(1, $this->historyPage), $pages);
        $direction = $this->historyDirection === 'asc' ? 'asc' : 'desc';

        $rows = $query
            ->orderBy('created_at', $direction)
            ->orderBy('id', $direction)
            ->forPage($page, self::HISTORY_PER_PAGE)
            ->get()
            ->map(function (NotificationLog $log) {
                $rule = $this->historyRules->get($log->notification_rule_id);
                $customer = $log->customer;

                return [
                    'id' => $log->id,
                    'at' => $log->created_at?->format('d.m.Y H:i'),
                    'customer_name' => $customer ? (trim((string) ($customer->company_name ?: $customer->name)) ?: $customer->email) : 'Gelöschter Kunde',
                    'rule_id' => $rule?->id,
                    'rule_name' => $log->rule_name ?: ($rule?->name ?? 'Gelöschte Regel'),
                    'rule_deleted' => (bool) $rule?->trashed(),
                    'source' => $rule ? ($rule->source ?? NotificationRule::SOURCE_TRAVEL_ALERT) : null,
                    'recipient' => $log->recipient_email,
                    'subject' => $log->subject,
                    'status' => $log->status,
                    'error' => $log->error_message,
                    'version' => $this->versions->count() > 1 ? (int) ($this->versions[$log->event_id] ?? 1) : null,
                    'trips_count' => $log->affected_trips_count,
                    'trips' => $log->affected_trips,
                ];
            })
            ->values()
            ->all();

        return ['rows' => $rows, 'total' => $total, 'filtered' => $filtered, 'page' => $page, 'pages' => $pages];
    }

    public function updated(string $property): void
    {
        // Ein geaenderter Filter beginnt wieder auf der ersten Seite.
        if (str_starts_with($property, 'history') && $property !== 'historyPage') {
            $this->historyPage = 1;
        }
    }

    public function historyGoTo(int $page): void
    {
        $this->historyPage = max(1, $page);
    }

    public function toggleHistoryDirection(): void
    {
        $this->historyDirection = $this->historyDirection === 'asc' ? 'desc' : 'asc';
        $this->historyPage = 1;
    }

    public function resetHistoryFilters(): void
    {
        $this->reset(['historyStatus', 'historySource', 'historyRule', 'historySearch', 'historyFrom', 'historyTo', 'historyPage']);
    }

    #[Computed]
    public function historyHasFilters(): bool
    {
        return $this->historyStatus !== '' || $this->historySource !== '' || $this->historyRule !== ''
            || trim($this->historySearch) !== '' || $this->historyFrom !== '' || $this->historyTo !== '';
    }

    protected function parseDate(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Je Reise (Kennung): wann sie in einer versendeten Mail genannt wurde –
     * unabhaengig von den Filtern des Verlaufs.
     *
     * @return array<string, array<int, string>>
     */
    #[Computed]
    public function tripMails(): array
    {
        $mails = [];

        $logs = $this->historyQuery()
            ->where('status', 'sent')
            ->whereNotNull('affected_trips')
            ->orderBy('created_at')
            ->get(['id', 'created_at', 'affected_trips']);

        foreach ($logs as $log) {
            foreach ($log->affected_trips ?? [] as $trip) {
                $mails[(string) ($trip['key'] ?? '')][] = $log->created_at?->format('d.m.Y H:i');
            }
        }

        return $mails;
    }

    /**
     * Versendete Mails mit Reisen, zu denen noch nicht gespeichert wurde, welche es waren.
     */
    #[Computed]
    public function mailsWithoutTripList(): int
    {
        return $this->historyQuery()
            ->where('status', 'sent')
            ->where('affected_trips_count', '>', 0)
            ->whereNull('affected_trips')
            ->count();
    }

    /**
     * Darf fuer dieses Ereignis von Hand gesendet werden? Nur bei einem
     * einzelnen Kunden und nur, solange das Ereignis ausgeliefert wird.
     */
    #[Computed]
    public function canSend(): bool
    {
        return $this->customerNumber !== ''
            && in_array($this->state, [EventState::Live, EventState::Scheduled], true);
    }

    /**
     * Die Regel, um die es im Rueckfrage-Dialog geht – nur eine zutreffende
     * Regel des gewaehlten Kunden.
     *
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function sendRow(): ?array
    {
        if (! $this->sendRuleId || ! $this->canSend) {
            return null;
        }

        $customerIds = $this->customers->pluck('id')->all();

        return collect($this->results)->first(fn (array $row) => $row['rule_id'] === $this->sendRuleId
            && $row['would_notify']
            && in_array($row['customer_id'], $customerIds, true));
    }

    public function confirmSend(int $ruleId): void
    {
        $this->sendRuleId = $ruleId;
        unset($this->sendRow);

        if ($this->sendRow) {
            $this->modal('send-rule')->show();
        }
    }

    /**
     * Die Benachrichtigung fuer die gewaehlte Regel und dieses Ereignis senden –
     * auch wenn sie schon einmal verschickt wurde.
     */
    public function sendRule(): void
    {
        $row = $this->sendRow;
        $this->modal('send-rule')->close();

        if (! $row) {
            $this->dispatch('adminv2-toast', message: 'Senden ist nur für eine zutreffende Regel eines einzelnen Kunden und ein veröffentlichtes Ereignis möglich.', variant: 'danger');

            return;
        }

        set_time_limit(180);

        $result = app(NotificationRuleService::class)->sendForRule(
            $this->event,
            NotificationRule::findOrFail($row['rule_id']),
        );

        $this->sendRuleId = null;
        $this->evaluate();

        $this->dispatch(
            'adminv2-toast',
            message: $result['sent'] ? 'Benachrichtigung an '.$result['recipient'].' versendet.' : $result['message'],
            variant: $result['sent'] ? 'success' : 'danger',
        );
    }

    /**
     * Die zukuenftigen Reisen des gewaehlten Kunden abrufen und gegen das
     * Ereignis pruefen. Nur fuer einen einzelnen Kunden.
     */
    public function loadTrips(): void
    {
        if ($this->customerNumber === '' || $this->customers->isEmpty()) {
            return;
        }

        set_time_limit(180);

        $service = app(NotificationRuleService::class);
        $rows = collect();
        $failed = false;

        // Mehrere lokale Kunden koennen zu einer Kundennummer gehoeren – jede Reise nur einmal.
        foreach ($this->customers as $customer) {
            $result = $service->upcomingTripsForEvent($customer->id, $this->event);
            $failed = $failed || $result['failed'];

            foreach ($result['trips'] as $row) {
                if (! $rows->has($row['key'])) {
                    $rows->put($row['key'], $row);
                }
            }
        }

        $countryNames = Country::query()
            ->whereIn('iso_code', $rows->flatMap(fn (array $row) => array_map('strtoupper', $row['trip']->countries_visited ?? []))->unique())
            ->get()
            ->mapWithKeys(fn (Country $country) => [strtoupper((string) $country->iso_code) => $country->getName('de')]);

        $linkBase = rtrim((string) config('services.passolution.travel_details_link', 'https://travel-details.eu'), '/');

        $this->trips = $rows
            ->sortBy(fn (array $row) => ($row['affected'] ? '0' : '1').($row['trip']->computed_start_at?->format('Y-m-d') ?? '9999'))
            ->map(function (array $row) use ($countryNames, $linkBase) {
                $trip = $row['trip'];
                $tid = $trip->pds_tid ?: $trip->external_trip_id;

                return [
                    'key' => $row['key'],
                    'name' => $trip->trip_name ?: ($trip->booking_reference ?: 'Reise '.$row['key']),
                    'reference' => $trip->trip_name ? $trip->booking_reference : null,
                    'tid' => $tid,
                    'url' => $tid ? $linkBase.'/de?tid='.urlencode((string) $tid).'&preview' : $trip->pds_share_url,
                    'period' => ($trip->computed_start_at?->format('d.m.Y') ?? '?').' – '.($trip->computed_end_at?->format('d.m.Y') ?? '?'),
                    'start' => $trip->computed_start_at?->format('Y-m-d'),
                    'end' => $trip->computed_end_at?->format('Y-m-d'),
                    'countries' => collect($trip->countries_visited ?? [])
                        ->map(fn ($code) => strtoupper((string) $code))
                        ->unique()
                        ->map(fn (string $code) => ['name' => $countryNames[$code] ?? $code, 'match' => in_array($code, $row['matching_countries'], true)])
                        ->values()
                        ->all(),
                    'in_period' => $row['in_period'],
                    'country_match' => $row['matching_countries'] !== [],
                    'affected' => $row['affected'],
                    'counted' => $row['counted'],
                    'ports_missing' => $row['ports_missing'],
                ];
            })
            ->values()
            ->all();

        $this->tripsFailed = $failed;
        $this->pdsFailed = $this->pdsFailed || $service->pdsApiFailed();
        $this->tripsLoaded = true;
    }

    /**
     * Die Reisen nach den gewaehlten Filtern.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function visibleTrips(): array
    {
        $search = mb_strtolower(trim($this->tripsSearch));
        $from = $this->parseDate($this->tripsFrom)?->format('Y-m-d');
        $to = $this->parseDate($this->tripsTo)?->format('Y-m-d');
        $reported = $this->tripMails;

        return array_values(array_filter($this->trips, function (array $trip) use ($search, $from, $to, $reported) {
            if ($this->tripsShow === 'affected' && ! $trip['affected']) {
                return false;
            }

            if ($this->tripsShow === 'unaffected' && $trip['affected']) {
                return false;
            }

            if ($search !== '' && ! str_contains(mb_strtolower($trip['name'].' '.$trip['reference'].' '.$trip['tid']), $search)) {
                return false;
            }

            if ($this->tripsCountry !== '' && ! in_array($this->tripsCountry, array_column($trip['countries'], 'name'), true)) {
                return false;
            }

            // Im Zeitraum unterwegs: Die Reise ueberschneidet das Zeitfenster.
            if ($from && (($trip['end'] ?? null) === null || $trip['end'] < $from)) {
                return false;
            }

            if ($to && (($trip['start'] ?? null) === null || $trip['start'] > $to)) {
                return false;
            }

            $isReported = isset($reported[$trip['key']]);

            return match ($this->tripsReported) {
                'reported' => $isReported,
                'unreported' => ! $isReported,
                default => true,
            };
        }));
    }

    /**
     * Die Laender, in die die geladenen Reisen fuehren – fuer den Filter.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function tripCountries(): array
    {
        $names = collect($this->trips)
            ->flatMap(fn (array $trip) => array_column($trip['countries'], 'name'))
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return $names;
    }

    #[Computed]
    public function tripsHasFilters(): bool
    {
        return $this->tripsShow !== 'all' || trim($this->tripsSearch) !== '' || $this->tripsCountry !== ''
            || $this->tripsFrom !== '' || $this->tripsTo !== '' || $this->tripsReported !== '';
    }

    public function resetTripFilters(): void
    {
        $this->reset(['tripsShow', 'tripsSearch', 'tripsCountry', 'tripsFrom', 'tripsTo', 'tripsReported']);
    }

    /**
     * @return array<string, array{label: string, rows: array<int, array<string, mixed>>, matching: int, total: int}>
     */
    #[Computed]
    public function groups(): array
    {
        $labels = [
            NotificationRule::SOURCE_GLOBAL_TRAVEL_MONITOR => 'Global Travel Monitor',
            NotificationRule::SOURCE_TRAVEL_ALERT => 'Travel Alert',
        ];

        $groups = [];

        foreach ($labels as $source => $label) {
            $rows = array_values(array_filter($this->results, fn (array $row) => $row['source'] === $source));

            $groups[$source] = [
                'label' => $label,
                'total' => count($rows),
                'matching' => count(array_filter($rows, fn (array $row) => $row['would_notify'])),
                'rows' => $this->show === 'all' ? $rows : array_values(array_filter($rows, fn (array $row) => $row['would_notify'])),
            ];
        }

        return $groups;
    }

    #[Computed]
    public function state(): EventState
    {
        return EventState::of($this->event);
    }

    public function render()
    {
        return view('livewire.admin-v2.events.rule-check');
    }
}
