<?php

namespace App\Models\Push;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eine Reise, die ein Geraet mit anderen teilt (einzelne App-IDs und/oder Gruppen). Nur der Ersteller
 * aendert sie (Name, Beschreibung, Zeitraum, Laender); die anderen sehen den aktuellen Stand (revision) und koennen ihr
 * lokal einen eigenen Namen geben.
 */
class AppSharedTrip extends Model
{
    use HasUlids;

    public const MAX_RECIPIENTS = 50;

    public const MAX_SIGHTS = 300;

    protected $fillable = ['owner_device_id', 'client_trip_id', 'name', 'description', 'start_date', 'end_date', 'country_codes', 'tid', 'sights', 'sight_visits', 'sight_votes', 'expenses', 'stays', 'day_notes', 'revision'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'country_codes' => 'array',
            'sights' => 'array',
            'sight_visits' => 'array',
            'sight_votes' => 'array',
            'expenses' => 'array',
            'stays' => 'array',
            'day_notes' => 'array',
            'revision' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(AppDevice::class, 'owner_device_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(AppSharedTripRecipient::class);
    }

    public function list(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AppSharedList::class);
    }

    /**
     * Alle, die die Reise sehen: Ersteller, angenommene direkte Empfaenger und angenommene Mitglieder
     * der Gruppen – ohne die, die abgelehnt oder ausgeblendet haben.
     *
     * @return \Illuminate\Support\Collection<int, AppDevice>
     */
    public function participants(): \Illuminate\Support\Collection
    {
        $declined = $this->recipients()->where('status', AppSharedTripRecipient::STATUS_DECLINED)->pluck('app_device_id')->all();
        $ids = collect([$this->owner_device_id])
            ->merge($this->recipients()->where('status', AppSharedTripRecipient::STATUS_ACCEPTED)->pluck('app_device_id'))
            ->merge(AppGroupMember::whereIn('app_group_id', $this->groups()->pluck('app_groups.id'))->where('status', AppGroupMember::STATUS_ACCEPTED)->pluck('app_device_id'))
            ->unique()->reject(fn ($id) => in_array($id, $declined, true));

        return AppDevice::whereIn('id', $ids->all())->get()->sortBy(fn ($d) => [$d->id !== $this->owner_device_id, mb_strtolower($d->publicName())])->values();
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(AppGroup::class, 'app_shared_trip_groups')->withPivot('created_at');
    }

    /** "ABCDEFGHJKLM" → "ABCD-EFGH-JKLM", wie die App den Code zeigt. */
    public function travelLinkCode(): ?string
    {
        return $this->tid ? implode('-', str_split($this->tid, 4)) : null;
    }

    public const VOTES = ['must', 'like', 'no'];

    public const MAX_COMMENT = 500;

    /**
     * Eigene Stimme, „Ich bin dabei“ und Kommentar zu einer gemeinsamen Sehenswuerdigkeit; true, wenn sich etwas
     * geaendert hat.
     */
    public function vote(AppDevice $device, string $key, ?string $vote, bool $going, ?string $comment = null): bool
    {
        $votes = (array) $this->sight_votes;
        $mine = (array) ($votes[$device->id] ?? []);
        $comment = filled($comment) ? mb_substr(trim($comment), 0, self::MAX_COMMENT) : null;
        $entry = array_filter(['vote' => $vote, 'going' => $going ?: null, 'comment' => $comment], fn ($v) => $v !== null);
        if (($mine[$key] ?? []) === $entry) {
            return false;
        }
        if ($entry === []) {
            unset($mine[$key]);
        } else {
            $mine[$key] = $entry;
        }
        $votes[$device->id] = $mine;
        $this->sight_votes = array_filter($votes);

        return true;
    }

    /** Geplanter Tag einer gemeinsamen Sehenswuerdigkeit (fuer alle gleich); true, wenn sich etwas geaendert hat. */
    public function planDay(string $key, ?string $day): bool
    {
        $sights = (array) $this->sights;
        foreach ($sights as $index => $sight) {
            if ($sight['key'] === $key && ! ($sight['deleted'] ?? false)) {
                if (($sight['day'] ?? null) === $day) {
                    return false;
                }
                $sights[$index]['day'] = $day;
                $this->sights = $sights;

                return true;
            }
        }
        abort(404, 'Sight not in this trip.');
    }

    public const MAX_EXPENSES = 500;

    /**
     * Ausgaben eines Geraets einarbeiten (Reisekasse): je Ausgabe gewinnt der neuere Stand (`updated_at`), Geloeschtes
     * bleibt mit `deleted` stehen. Bezahlt von / aufgeteilt auf sind App-IDs der Teilnehmer. true bei Aenderungen.
     *
     * @param  array<int, array<string, mixed>>  $entries
     */
    public function mergeExpenses(AppDevice $device, array $entries): bool
    {
        $current = collect((array) $this->expenses)->keyBy('id')->all();
        $changed = false;
        foreach ($entries as $incoming) {
            $existing = $current[$incoming['id']] ?? null;
            $updated = (int) $incoming['updated_at'];
            if ($existing && $updated <= (int) ($existing['updated_at'] ?? 0)) {
                continue;
            }
            $deleted = (bool) ($incoming['deleted'] ?? false);
            $current[$incoming['id']] = $deleted ? ['id' => $incoming['id'], 'updated_at' => $updated, 'deleted' => true] : [
                'id' => $incoming['id'],
                'title' => mb_substr(trim((string) $incoming['title']), 0, 120),
                'amount' => (int) $incoming['amount'],
                'currency' => strtoupper((string) $incoming['currency']),
                'date' => $incoming['date'] ?? null,
                'paid_by' => self::shareCode($incoming['paid_by']),
                'split' => array_values(array_unique(array_map([self::class, 'shareCode'], (array) $incoming['split']))),
                'by' => $existing['by'] ?? $device->id,
                'updated_at' => $updated,
                'deleted' => false,
            ];
            $changed = true;
        }
        abort_if(count(array_filter($current, fn ($e) => ! $e['deleted'])) > self::MAX_EXPENSES, 422, 'Too many expenses.');
        $this->expenses = array_values($current);

        return $changed;
    }

    /** App-ID einer Ausgabe (bezahlt von / aufgeteilt auf); ungueltige → 422. */
    private static function shareCode(mixed $code): string
    {
        $normalized = AppDevice::normalizeShareCode((string) $code);
        abort_if($normalized === null, 422, 'Invalid app ID in expense.');

        return $normalized;
    }

    /** Aktuelle Ausgaben fuer die App (App-IDs formatiert, ohne geloeschte). */
    public function expensesApi(): array
    {
        return collect((array) $this->expenses)->reject(fn ($e) => $e['deleted'] ?? false)->sortBy([['date', 'desc'], ['updated_at', 'desc']])->values()
            ->map(fn ($e) => [
                'id' => $e['id'],
                'title' => $e['title'],
                'amount' => $e['amount'],
                'currency' => $e['currency'],
                'date' => $e['date'],
                'paid_by' => AppDevice::formatShareCode($e['paid_by']),
                'split' => array_map(fn ($c) => AppDevice::formatShareCode($c), $e['split']),
                'updated_at' => $e['updated_at'],
            ])->all();
    }

    public const MAX_STAYS = 200;

    public const MAX_DAY_NOTES = 400;

    public const STAY_KINDS = ['place', 'sea'];

    /**
     * Aufenthaltsorte eines Geraets einarbeiten (Reiseverlauf): je Eintrag gewinnt der neuere Stand (`updated_at`),
     * Geloeschtes bleibt mit `deleted` stehen; `by` = wer zuletzt geaendert hat. Liefert die Eintraege, deren Ort,
     * Zeitraum oder Zeiten sich geaendert haben (fuer den Hinweis an die anderen) – Notizen allein zaehlen nicht.
     *
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<int, array<string, mixed>>
     */
    public function mergeStays(AppDevice $device, array $entries): array
    {
        $current = collect((array) $this->stays)->keyBy('id')->all();
        $changed = [];
        $touched = false;
        foreach ($entries as $incoming) {
            $existing = $current[$incoming['id']] ?? null;
            $updated = (int) $incoming['updated_at'];
            if ($existing && $updated <= (int) ($existing['updated_at'] ?? 0)) {
                continue;
            }
            $deleted = (bool) ($incoming['deleted'] ?? false);
            if (! $existing && $deleted) {
                continue;
            }
            $sea = ($incoming['kind'] ?? 'place') === 'sea';
            $stay = $deleted ? ['id' => $incoming['id'], 'updated_at' => $updated, 'deleted' => true, 'by' => $device->id] : [
                'id' => $incoming['id'],
                'kind' => $sea ? 'sea' : 'place',
                'from' => $incoming['from'],
                'to' => ($incoming['to'] ?? null) ?: $incoming['from'],
                'name' => $sea ? null : mb_substr(trim((string) $incoming['name']), 0, 120),
                'country_code' => $sea || empty($incoming['country_code']) ? null : strtoupper($incoming['country_code']),
                'lat' => $sea || ! isset($incoming['lat']) ? null : round((float) $incoming['lat'], 6),
                'lng' => $sea || ! isset($incoming['lng']) ? null : round((float) $incoming['lng'], 6),
                'arrival' => $incoming['arrival'] ?? null,
                'departure' => $incoming['departure'] ?? null,
                'note' => filled($incoming['note'] ?? null) ? mb_substr(trim($incoming['note']), 0, 1000) : null,
                'by' => $device->id,
                'updated_at' => $updated,
                'deleted' => false,
            ];
            $current[$incoming['id']] = $stay;
            $touched = true;
            $relevant = ['kind', 'from', 'to', 'name', 'arrival', 'departure', 'deleted'];
            if (array_intersect_key((array) $existing, array_flip($relevant)) != array_intersect_key($stay, array_flip($relevant))
                && ! ($deleted && ($existing['deleted'] ?? false))) {
                $changed[] = $deleted ? array_merge($existing, ['deleted' => true]) : $stay;
            }
        }
        abort_if(count(array_filter($current, fn ($s) => ! $s['deleted'])) > self::MAX_STAYS, 422, 'Too many stays.');
        if ($touched) {
            $this->stays = array_values($current);
        }

        return $changed;
    }

    /**
     * Notizen je Tag einarbeiten (Schluessel = Datum); leere Notiz = geloescht. true bei Aenderungen.
     *
     * @param  array<int, array<string, mixed>>  $entries
     */
    public function mergeDayNotes(AppDevice $device, array $entries): bool
    {
        $current = collect((array) $this->day_notes)->keyBy('date')->all();
        $changed = false;
        foreach ($entries as $incoming) {
            $existing = $current[$incoming['date']] ?? null;
            $updated = (int) $incoming['updated_at'];
            if ($existing && $updated <= (int) ($existing['updated_at'] ?? 0)) {
                continue;
            }
            $note = trim((string) ($incoming['note'] ?? ''));
            $deleted = (bool) ($incoming['deleted'] ?? false) || $note === '';
            if (! $existing && $deleted) {
                continue;
            }
            $current[$incoming['date']] = [
                'date' => $incoming['date'],
                'note' => $deleted ? null : mb_substr($note, 0, 1000),
                'by' => $device->id,
                'updated_at' => $updated,
                'deleted' => $deleted,
            ];
            $changed = true;
        }
        abort_if(count(array_filter($current, fn ($n) => ! $n['deleted'])) > self::MAX_DAY_NOTES, 422, 'Too many day notes.');
        if ($changed) {
            $this->day_notes = array_values($current);
        }

        return $changed;
    }

    /**
     * Reiseverlauf fuer die App: Aufenthalte (nach Datum) und Notizen je Tag, ohne geloeschte, mit „zuletzt geaendert von“.
     *
     * @param  \Illuminate\Support\Collection<int, AppDevice>|null  $participants
     */
    public function itineraryApi(?\Illuminate\Support\Collection $participants = null): array
    {
        $names = ($participants ?? $this->participants())->mapWithKeys(fn (AppDevice $p) => [$p->id => $p->publicName()]);

        return [
            'stays' => collect((array) $this->stays)->reject(fn ($s) => $s['deleted'] ?? false)
                ->sortBy([['from', 'asc'], ['arrival', 'asc']])->values()
                ->map(fn ($s) => [
                    'id' => $s['id'],
                    'kind' => $s['kind'],
                    'from' => $s['from'],
                    'to' => $s['to'],
                    'name' => $s['name'],
                    'country_code' => $s['country_code'],
                    'lat' => $s['lat'],
                    'lng' => $s['lng'],
                    'arrival' => $s['arrival'],
                    'departure' => $s['departure'],
                    'note' => $s['note'],
                    'changed_by' => $names[$s['by'] ?? ''] ?? null,
                    'updated_at' => (int) $s['updated_at'],
                ])->all(),
            'day_notes' => collect((array) $this->day_notes)->reject(fn ($n) => $n['deleted'] ?? false)
                ->sortBy('date')->values()
                ->map(fn ($n) => [
                    'date' => $n['date'],
                    'note' => $n['note'],
                    'changed_by' => $names[$n['by'] ?? ''] ?? null,
                    'updated_at' => (int) $n['updated_at'],
                ])->all(),
        ];
    }

    public function sharesSights(): bool
    {
        return $this->sights !== null;
    }

    /**
     * Aenderungen eines Geraets an den gemeinsamen Sehenswuerdigkeiten einarbeiten: je Eintrag gewinnt der neuere
     * Stand (`updated_at`), Entferntes bleibt mit `deleted` stehen. `$visited` = die davon, die das Geraet besucht hat.
     * true, wenn sich etwas geaendert hat.
     *
     * @param  array<int, array{key: string, updated_at: int|float, deleted?: bool}>  $entries
     * @param  array<int, string>|null  $visited
     */
    public function mergeSights(AppDevice $device, array $entries, ?array $visited): bool
    {
        $current = collect((array) $this->sights)->keyBy('key')->all();
        $changed = false;
        foreach ($entries as $incoming) {
            $existing = $current[$incoming['key']] ?? null;
            $updated = (int) $incoming['updated_at'];
            if ($existing && $updated <= (int) ($existing['updated_at'] ?? 0)) {
                continue;
            }
            $deleted = (bool) ($incoming['deleted'] ?? false);
            if (! $existing && $deleted) {
                continue;
            }
            $current[$incoming['key']] = array_filter([
                'key' => $incoming['key'],
                // Wer sie (zuletzt) hinzugefuegt hat
                'by' => $existing && ! ($existing['deleted'] ?? false) ? ($existing['by'] ?? $device->id) : $device->id,
                'updated_at' => $updated,
                'deleted' => $deleted,
                'day' => $deleted ? null : ($existing['day'] ?? null),
            ], fn ($v) => $v !== null);
            $changed = true;
        }
        if (count(array_filter($current, fn ($s) => ! $s['deleted'])) > self::MAX_SIGHTS) {
            abort(422, 'Too many sights.');
        }
        $this->sights = array_values($current);

        if ($visited !== null) {
            $visits = (array) $this->sight_visits;
            $mine = array_values(array_unique(array_filter($visited, fn ($key) => isset($current[$key]))));
            sort($mine);
            if (($visits[$device->id] ?? []) !== $mine) {
                $visits[$device->id] = $mine;
                $this->sight_visits = $visits;
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * Gemeinsame Sehenswuerdigkeiten fuer ein Geraet: aktuelle Eintraege mit „hinzugefuegt von“ und wer
     * (ausser dem Geraet selbst) schon dort war; null = nicht geteilt.
     *
     * @param  \Illuminate\Support\Collection<int, AppDevice>|null  $participants
     */
    public function sightsApi(AppDevice $device, ?\Illuminate\Support\Collection $participants = null): ?array
    {
        if (! $this->sharesSights()) {
            return null;
        }
        $names = ($participants ?? $this->participants())->mapWithKeys(fn (AppDevice $p) => [$p->id => $p->publicName()]);
        $visits = (array) $this->sight_visits;
        $votes = (array) $this->sight_votes;

        return collect((array) $this->sights)->reject(fn ($s) => $s['deleted'] ?? false)
            ->sortBy('updated_at')->values()
            ->map(fn ($s) => [
                'key' => $s['key'],
                'added_by' => $names[$s['by'] ?? ''] ?? null,
                'updated_at' => (int) $s['updated_at'],
                'visited_by' => collect($visits)->filter(fn ($keys, $id) => $id !== $device->id && $names->has($id) && in_array($s['key'], (array) $keys, true))
                    ->keys()->map(fn ($id) => $names[$id])->values()->all(),
                'day' => $s['day'] ?? null,
                // Stimmen aller Teilnehmer (die eigene mit is_me), Reihenfolge wie die Teilnehmer
                'votes' => $names->keys()->filter(fn ($id) => isset($votes[$id][$s['key']]))->map(fn ($id) => [
                    'name' => $names[$id],
                    'is_me' => $id === $device->id,
                    'vote' => $votes[$id][$s['key']]['vote'] ?? null,
                    'going' => (bool) ($votes[$id][$s['key']]['going'] ?? false),
                    'comment' => $votes[$id][$s['key']]['comment'] ?? null,
                ])->values()->all(),
            ])->all();
    }

    /** Inhalt der Reise fuer die Apps der anderen. */
    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'country_codes' => array_values((array) $this->country_codes),
            'travel_link_code' => $this->travelLinkCode(),
            'revision' => $this->revision,
            'owner_name' => $this->owner?->publicName(),
            // Packliste mitgeteilt – auch fuer Einladungen, die die Liste selbst noch nicht sehen.
            'has_list' => $this->list !== null,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
