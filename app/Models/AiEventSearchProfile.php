<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hinterlegte KI-Suche nach Ereignissen: Auftrag, Filter und Zeitplan. Der
 * Zeitplaner fuehrt sie zu den angegebenen Zeiten automatisch aus; ihre
 * Ergebnisse landen bei den uebrigen KI-Vorschlaegen.
 */
class AiEventSearchProfile extends Model
{
    public const WEEKDAYS = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'];

    public const WEEKDAYS_SHORT = [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'];

    protected $fillable = [
        'name', 'prompt', 'exclude_existing', 'country_codes', 'event_type_codes', 'priorities',
        'keyword', 'days_ahead', 'weekdays', 'times', 'is_active', 'created_by',
    ];

    protected $casts = [
        'exclude_existing' => 'boolean',
        'country_codes' => 'array',
        'event_type_codes' => 'array',
        'priorities' => 'array',
        'days_ahead' => 'integer',
        'weekdays' => 'array',
        'times' => 'array',
        'is_active' => 'boolean',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
    ];

    protected $attributes = [
        'exclude_existing' => true,
        'is_active' => true,
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function searches(): HasMany
    {
        return $this->hasMany(AiEventSearch::class, 'profile_id');
    }

    /**
     * Die Uhrzeiten in gueltiger Form, aufsteigend.
     *
     * @return array<int, string>
     */
    public function sortedTimes(): array
    {
        $times = array_values(array_unique(array_filter(
            array_map('strval', $this->times ?? []),
            fn (string $time) => (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time),
        )));
        sort($times);

        return $times;
    }

    /**
     * @return array<int, int>
     */
    public function sortedWeekdays(): array
    {
        $weekdays = array_values(array_unique(array_filter(
            array_map('intval', $this->weekdays ?? []),
            fn (int $day) => $day >= 1 && $day <= 7,
        )));
        sort($weekdays);

        return $weekdays;
    }

    /**
     * Der naechste Termin nach $after – null ohne Uhrzeit.
     */
    public function nextRunAfter(CarbonInterface $after): ?CarbonImmutable
    {
        $after = CarbonImmutable::instance($after);
        $times = $this->sortedTimes();
        $weekdays = $this->sortedWeekdays();

        if ($times === []) {
            return null;
        }

        for ($offset = 0; $offset <= 7; $offset++) {
            $day = $after->startOfDay()->addDays($offset);

            if ($weekdays !== [] && ! in_array($day->dayOfWeekIso, $weekdays, true)) {
                continue;
            }

            foreach ($times as $time) {
                [$hour, $minute] = array_map('intval', explode(':', $time));
                $at = $day->setTime($hour, $minute);

                if ($at->gt($after)) {
                    return $at;
                }
            }
        }

        return null;
    }

    /**
     * Den naechsten Termin ab jetzt festhalten (null = pausiert oder ohne Uhrzeit).
     */
    public function scheduleNext(?CarbonInterface $after = null): static
    {
        $this->next_run_at = $this->is_active ? $this->nextRunAfter($after ?? now()) : null;

        return $this;
    }

    /**
     * Der Zeitplan in Worten, z. B. "Montag bis Freitag um 07:00 und 13:00 Uhr".
     */
    public function scheduleSummary(): string
    {
        $times = $this->sortedTimes();

        if ($times === []) {
            return 'Kein Zeitpunkt hinterlegt – läuft nur von Hand';
        }

        $weekdays = $this->sortedWeekdays();

        $days = match (true) {
            $weekdays === [] || count($weekdays) === 7 => 'Täglich',
            $weekdays === [1, 2, 3, 4, 5] => 'Montag bis Freitag',
            $weekdays === [6, 7] => 'Am Wochenende',
            default => self::joinList(array_map(fn (int $day) => self::WEEKDAYS[$day], $weekdays)),
        };

        return $days.' um '.self::joinList($times).' Uhr';
    }

    /**
     * Die Filter in der Form, die ein Suchlauf erwartet – der Zeitraum wird
     * zum Zeitpunkt des Laufs berechnet. Null, wenn nichts eingegrenzt ist.
     *
     * @return array<string, mixed>|null
     */
    public function filtersForRun(?CarbonInterface $now = null): ?array
    {
        $now = CarbonImmutable::instance($now ?? now());
        $countries = array_values(array_map('strtoupper', $this->country_codes ?? []));
        $types = array_values($this->event_type_codes ?? []);
        $priorityOptions = CustomEvent::getPriorityOptions();
        // In fester Reihenfolge (Information bis Hoch), gleich wie sie angeklickt wurden.
        $priorities = array_values(array_intersect(array_keys($priorityOptions), $this->priorities ?? []));

        $countryNames = Country::query()->whereIn('iso_code', $countries)->get()
            ->mapWithKeys(fn (Country $country) => [strtoupper((string) $country->iso_code) => $country->getName('de')]);
        $typeNames = EventType::query()->whereIn('code', $types)->pluck('name', 'code');

        $from = $this->days_ahead !== null ? $now->startOfDay() : null;
        $to = $this->days_ahead !== null ? $now->startOfDay()->addDays($this->days_ahead) : null;

        $name = fn (string $code) => ($countryNames[$code] ?? $code).' ('.$code.')';

        // Sind (fast) alle Laender gewaehlt, ist die Liste der Ausnahmen kuerzer und klarer.
        $allCodes = Country::query()->whereNotNull('iso_code')->pluck('iso_code')->map(fn ($code) => strtoupper((string) $code))->unique()->values()->all();
        $excluded = array_values(array_diff($allCodes, $countries));

        if ($countries !== [] && $excluded === []) {
            // Alle gewaehlt = keine Eingrenzung.
            $countries = [];
            $countryLabel = '';
        } elseif ($countries !== [] && count($excluded) < count($countries)) {
            $excludedNames = Country::query()->whereIn('iso_code', $excluded)->get()
                ->mapWithKeys(fn (Country $country) => [strtoupper((string) $country->iso_code) => $country->getName('de')]);
            $countryLabel = 'alle außer '.implode(', ', array_map(fn (string $code) => ($excludedNames[$code] ?? $code).' ('.$code.')', $excluded));
        } else {
            $countryLabel = implode(', ', array_map($name, $countries));
        }

        $labels = array_filter([
            'Länder' => $countryLabel,
            'Event-Typen' => implode(', ', array_map(fn (string $code) => $typeNames[$code] ?? $code, $types)),
            'Priorität' => implode(', ', array_map(fn (string $priority) => $priorityOptions[$priority], $priorities)),
            'Zeitraum' => $from ? $from->format('d.m.Y').' bis '.$to->format('d.m.Y') : '',
            'Stichwort' => trim((string) $this->keyword),
        ]);

        if ($labels === []) {
            return null;
        }

        return [
            'search' => trim((string) $this->keyword),
            'priorities' => $priorities,
            'types' => $types,
            'countries' => $countries,
            'from' => $from?->format('Y-m-d'),
            'to' => $to?->format('Y-m-d'),
            'labels' => collect($labels)->map(fn ($value, $label) => ['label' => $label, 'value' => $value])->values()->all(),
        ];
    }

    /**
     * Die Filter in Worten fuer die Liste – der Zeitraum relativ ("die naechsten 7 Tage").
     */
    public function filterSummary(): ?string
    {
        $filters = $this->filtersForRun();

        if (! $filters) {
            return null;
        }

        return collect($filters['labels'])->map(function (array $entry) {
            $value = $entry['label'] === 'Zeitraum'
                ? ($this->days_ahead === 0 ? 'nur am Tag der Suche' : 'die nächsten '.$this->days_ahead.' '.($this->days_ahead === 1 ? 'Tag' : 'Tage').' ab dem Tag der Suche')
                : $entry['value'];

            return $entry['label'].': '.$value;
        })->implode(' · ');
    }

    /**
     * @param  array<int, string>  $items
     */
    protected static function joinList(array $items): string
    {
        if (count($items) <= 1) {
            return $items[0] ?? '';
        }

        $last = array_pop($items);

        return implode(', ', $items).' und '.$last;
    }
}
