<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Wiederkehrende Aufgabe: Vorlage und Rhythmus. Der Zeitplaner legt zum
 * jeweiligen Termin eine gewoehnliche Aufgabe (AdminTask) daraus an.
 *
 * Die Termine werden hier berechnet – fuer den Zeitplaner (next_run_at) und
 * fuer die Vorschau im Formular gleichermassen.
 */
class AdminTaskRecurrence extends Model
{
    use SoftDeletes;

    public const FREQUENCY_DAILY = 'daily';

    public const FREQUENCY_WEEKLY = 'weekly';

    public const FREQUENCY_MONTHLY = 'monthly';

    public const FREQUENCY_YEARLY = 'yearly';

    public const WEEKDAYS = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'];

    public const WEEKDAYS_SHORT = [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'];

    public const MONTHS = [1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April', 5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'];

    public const NTH = [1 => 'ersten', 2 => 'zweiten', 3 => 'dritten', 4 => 'vierten', -1 => 'letzten'];

    protected $fillable = [
        'title', 'description', 'category_id', 'priority', 'created_by',
        'responsible_id', 'responsible_team_id', 'next_assignee_id', 'next_assignee_team_id',
        'frequency', 'interval', 'weekdays', 'monthly_mode', 'day_of_month', 'nth', 'nth_weekday', 'month',
        'weekend_mode', 'create_time', 'starts_on', 'ends_on', 'max_occurrences',
        'due_in_days', 'remind_days_before', 'remind_time', 'skip_if_open', 'is_active',
    ];

    protected $casts = [
        'weekdays' => 'array',
        'interval' => 'integer',
        'day_of_month' => 'integer',
        'nth' => 'integer',
        'nth_weekday' => 'integer',
        'month' => 'integer',
        'starts_on' => 'date',
        'ends_on' => 'date',
        'max_occurrences' => 'integer',
        'occurrences_count' => 'integer',
        'due_in_days' => 'integer',
        'remind_days_before' => 'integer',
        'skip_if_open' => 'boolean',
        'is_active' => 'boolean',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'last_skipped_at' => 'datetime',
    ];

    protected $attributes = [
        'priority' => AdminTask::PRIORITY_NORMAL,
        'interval' => 1,
        'monthly_mode' => 'day',
        'day_of_month' => 1,
        'nth' => 1,
        'nth_weekday' => 1,
        'weekend_mode' => 'keep',
        'create_time' => '07:00:00',
        'remind_time' => '09:00:00',
        'occurrences_count' => 0,
        'skip_if_open' => false,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    public static function frequencyOptions(): array
    {
        return [
            self::FREQUENCY_DAILY => 'Täglich',
            self::FREQUENCY_WEEKLY => 'Wöchentlich',
            self::FREQUENCY_MONTHLY => 'Monatlich',
            self::FREQUENCY_YEARLY => 'Jährlich',
        ];
    }

    /**
     * Platzhalter fuer den Titel der angelegten Aufgabe.
     *
     * @return array<string, string>
     */
    public static function placeholders(): array
    {
        return [
            '{datum}' => 'Datum des Termins (02.10.2026)',
            '{monat}' => 'Monat (Oktober)',
            '{jahr}' => 'Jahr (2026)',
            '{kw}' => 'Kalenderwoche (40)',
            '{quartal}' => 'Quartal (Q4)',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AdminTaskCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function nextAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'next_assignee_id');
    }

    public function responsibleTeam(): BelongsTo
    {
        return $this->belongsTo(AdminTeam::class, 'responsible_team_id')->withTrashed();
    }

    public function nextAssigneeTeam(): BelongsTo
    {
        return $this->belongsTo(AdminTeam::class, 'next_assignee_team_id')->withTrashed();
    }

    public function responsibleLabel(): ?string
    {
        return $this->responsibleTeam?->label() ?? (trim((string) $this->responsible?->name) ?: null);
    }

    public function nextAssigneeLabel(): ?string
    {
        return $this->nextAssigneeTeam?->label() ?? (trim((string) $this->nextAssignee?->name) ?: null);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(AdminTask::class, 'recurrence_id');
    }

    /**
     * Sind alle vorgesehenen Aufgaben angelegt bzw. ist das Enddatum erreicht?
     */
    public function isFinished(): bool
    {
        if ($this->max_occurrences !== null && $this->occurrences_count >= $this->max_occurrences) {
            return true;
        }

        return $this->ends_on !== null && $this->nextOccurrences(now(), 1) === [];
    }

    /**
     * Zustand fuer die Anzeige: active | paused | finished.
     */
    public function state(): string
    {
        return match (true) {
            $this->isFinished() => 'finished',
            ! $this->is_active => 'paused',
            default => 'active',
        };
    }

    /**
     * Den naechsten Termin ab jetzt festhalten (null = pausiert oder beendet).
     */
    public function scheduleNext(?CarbonInterface $after = null): static
    {
        $this->next_run_at = $this->is_active
            ? ($this->nextOccurrences($after ?? now(), 1)[0] ?? null)
            : null;

        return $this;
    }

    /**
     * Die naechsten Termine nach $after – begrenzt durch Enddatum und
     * Hoechstzahl.
     *
     * @return array<int, CarbonImmutable>
     */
    public function nextOccurrences(CarbonInterface $after, int $limit = 1): array
    {
        if (! $this->starts_on || ! $this->frequency) {
            return [];
        }

        if ($this->max_occurrences !== null) {
            $limit = min($limit, max(0, $this->max_occurrences - $this->occurrences_count));
        }

        if ($limit <= 0) {
            return [];
        }

        $after = CarbonImmutable::instance($after);
        $start = CarbonImmutable::parse($this->starts_on)->startOfDay();
        $end = $this->ends_on ? CarbonImmutable::parse($this->ends_on)->endOfDay() : null;
        [$hour, $minute] = array_map('intval', explode(':', (string) $this->create_time) + [0, 0]);

        $found = [];
        $index = $this->firstPeriodIndex($start, $after);

        // Ein Wochenend-Termin kann in die Nachbarperiode rutschen – deshalb
        // etwas mehr sammeln als gebraucht und erst am Ende sortieren.
        for ($guard = 0; $guard < 1500 && count($found) < $limit + 3; $guard++, $index++) {
            $dates = $this->periodDates($start, $index);

            if ($dates === []) {
                continue;
            }

            if ($end && $dates[0]->subDays(3)->gt($end)) {
                break;
            }

            foreach ($dates as $date) {
                $date = $this->shiftWeekend($date);

                if (! $date || $date->lt($start) || ($end && $date->gt($end))) {
                    continue;
                }

                $at = $date->setTime($hour, $minute);

                if ($at->gt($after)) {
                    $found[$at->format('Y-m-d')] = $at;
                }
            }
        }

        ksort($found);

        return array_slice(array_values($found), 0, $limit);
    }

    /**
     * Die Periode (Tag, Woche, Monat, Jahr im Abstand "interval"), ab der
     * gesucht wird – eine frueher als noetig, wegen verschobener Wochenenden.
     */
    protected function firstPeriodIndex(CarbonImmutable $start, CarbonImmutable $after): int
    {
        if ($after->lte($start)) {
            return 0;
        }

        $interval = max(1, (int) $this->interval);

        $elapsed = match ($this->frequency) {
            self::FREQUENCY_WEEKLY => $start->startOfWeek(CarbonInterface::MONDAY)->diffInWeeks($after),
            self::FREQUENCY_MONTHLY => $start->startOfMonth()->diffInMonths($after),
            self::FREQUENCY_YEARLY => $start->startOfYear()->diffInYears($after),
            default => $start->diffInDays($after),
        };

        return max(0, (int) floor($elapsed / $interval) - 1);
    }

    /**
     * Die Termine (ohne Uhrzeit) einer Periode, aufsteigend.
     *
     * @return array<int, CarbonImmutable>
     */
    protected function periodDates(CarbonImmutable $start, int $index): array
    {
        $step = $index * max(1, (int) $this->interval);

        switch ($this->frequency) {
            case self::FREQUENCY_WEEKLY:
                $monday = $start->startOfWeek(CarbonInterface::MONDAY)->addWeeks($step);
                $weekdays = array_values(array_unique(array_filter(
                    array_map('intval', $this->weekdays ?? []),
                    fn (int $day) => $day >= 1 && $day <= 7,
                )));
                sort($weekdays);

                return array_map(
                    fn (int $day) => $monday->addDays($day - 1),
                    $weekdays ?: [$start->dayOfWeekIso],
                );

            case self::FREQUENCY_MONTHLY:
                return [$this->dayInMonth($start->startOfMonth()->addMonths($step))];

            case self::FREQUENCY_YEARLY:
                $month = min(12, max(1, (int) ($this->month ?: $start->month)));

                return [$this->dayInMonth($start->startOfYear()->addYears($step)->setMonth($month))];

            default:
                return [$start->addDays($step)];
        }
    }

    /**
     * Der Termin innerhalb eines Monats: fester Tag oder z. B. zweiter Dienstag.
     */
    protected function dayInMonth(CarbonImmutable $monthStart): CarbonImmutable
    {
        if ($this->monthly_mode === 'weekday') {
            $weekday = min(7, max(1, (int) $this->nth_weekday));

            if ((int) $this->nth === -1) {
                $date = $monthStart->endOfMonth()->startOfDay();

                while ($date->dayOfWeekIso !== $weekday) {
                    $date = $date->subDay();
                }

                return $date;
            }

            $date = $monthStart;

            while ($date->dayOfWeekIso !== $weekday) {
                $date = $date->addDay();
            }

            return $date->addWeeks(min(4, max(1, (int) $this->nth)) - 1);
        }

        $day = (int) $this->day_of_month;

        // 0 = letzter Tag; den 31. gibt es nicht in jedem Monat – dann gilt der letzte.
        return $monthStart->setDay($day <= 0 ? $monthStart->daysInMonth : min($day, $monthStart->daysInMonth));
    }

    /**
     * Termin am Wochenende: belassen, auslassen (null) oder verschieben.
     */
    protected function shiftWeekend(CarbonImmutable $date): ?CarbonImmutable
    {
        // Bei woechentlichen Aufgaben sind die Wochentage ausdruecklich gewaehlt.
        if (! $date->isWeekend() || $this->frequency === self::FREQUENCY_WEEKLY) {
            return $date;
        }

        return match ($this->weekend_mode) {
            'skip' => null,
            'before' => $date->subDays($date->isSaturday() ? 1 : 2),
            'after' => $date->addDays($date->isSaturday() ? 2 : 1),
            default => $date,
        };
    }

    /**
     * Der Rhythmus in Worten, z. B. "Alle 2 Wochen am Montag und Donnerstag um 07:00 Uhr".
     */
    public function summary(): string
    {
        $interval = max(1, (int) $this->interval);
        $time = substr((string) $this->create_time, 0, 5);

        $dayInMonth = function (): string {
            if ($this->monthly_mode === 'weekday') {
                return 'am '.(self::NTH[(int) $this->nth] ?? 'ersten').' '.(self::WEEKDAYS[(int) $this->nth_weekday] ?? 'Montag');
            }

            return (int) $this->day_of_month <= 0 ? 'am letzten Tag' : 'am '.(int) $this->day_of_month.'.';
        };

        $text = match ($this->frequency) {
            self::FREQUENCY_DAILY => ($interval === 1 ? 'Jeden Tag' : "Alle {$interval} Tage")
                .($this->weekend_mode === 'skip' ? ' (nur Montag bis Freitag)' : ''),
            self::FREQUENCY_WEEKLY => ($interval === 1 ? 'Jede Woche' : "Alle {$interval} Wochen").' am '
                .$this->joinList(array_map(fn ($day) => self::WEEKDAYS[(int) $day] ?? '', $this->sortedWeekdays())),
            self::FREQUENCY_MONTHLY => ($interval === 1 ? 'Jeden Monat' : "Alle {$interval} Monate").' '.$dayInMonth(),
            self::FREQUENCY_YEARLY => ($interval === 1 ? 'Jedes Jahr' : "Alle {$interval} Jahre").' '.$dayInMonth()
                .($this->monthly_mode === 'weekday' ? ' im ' : ' ').(self::MONTHS[(int) ($this->month ?: $this->starts_on?->month ?: 1)] ?? ''),
            default => '',
        };

        if (in_array($this->frequency, [self::FREQUENCY_MONTHLY, self::FREQUENCY_YEARLY], true)) {
            $text .= match ($this->weekend_mode) {
                'skip' => ' (entfällt am Wochenende)',
                'before' => ' (am Wochenende: Freitag davor)',
                'after' => ' (am Wochenende: Montag danach)',
                default => '',
            };
        }

        return trim($text).' um '.$time.' Uhr';
    }

    /**
     * @return array<int, int>
     */
    protected function sortedWeekdays(): array
    {
        $weekdays = array_values(array_unique(array_map('intval', $this->weekdays ?? [])));
        sort($weekdays);

        return $weekdays ?: [(int) ($this->starts_on?->dayOfWeekIso ?? 1)];
    }

    /**
     * @param  array<int, string>  $items
     */
    protected function joinList(array $items): string
    {
        $items = array_values(array_filter($items));

        if (count($items) <= 1) {
            return $items[0] ?? '';
        }

        $last = array_pop($items);

        return implode(', ', $items).' und '.$last;
    }

    /**
     * Titel der Aufgabe fuer einen Termin – mit ersetzten Platzhaltern.
     */
    public function titleFor(CarbonInterface $date): string
    {
        return strtr($this->title, [
            '{datum}' => $date->format('d.m.Y'),
            '{monat}' => self::MONTHS[$date->month] ?? '',
            '{jahr}' => $date->format('Y'),
            '{kw}' => (string) $date->isoWeek,
            '{quartal}' => 'Q'.$date->quarter,
        ]);
    }
}
