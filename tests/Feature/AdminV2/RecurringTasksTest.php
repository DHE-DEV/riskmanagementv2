<?php

use App\Livewire\AdminV2\System\RecurringTasks\Editor;
use App\Livewire\AdminV2\System\RecurringTasks\Index;
use App\Mail\AdminTaskAssignedMail;
use App\Models\AdminTask;
use App\Models\AdminTaskCategory;
use App\Models\AdminTaskRecurrence;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

/**
 * Wiederkehrende Aufgaben: Terminberechnung, automatische Anlage durch den
 * Zeitplaner und die Seiten unter System.
 */
function recurringUser(string $name, bool $active = true): User
{
    return User::factory()->create(['name' => $name, 'is_admin' => true, 'is_active' => $active]);
}

function recurrence(array $attributes = []): AdminTaskRecurrence
{
    $model = new AdminTaskRecurrence(array_merge([
        'title' => 'Quellen prüfen',
        'category_id' => AdminTaskCategory::firstOrCreate(['name' => 'Global Travel Monitor'])->id,
        'frequency' => AdminTaskRecurrence::FREQUENCY_DAILY,
        'starts_on' => '2026-10-01',
        'create_time' => '07:00:00',
    ], $attributes));

    return $model;
}

/** Die naechsten Termine als lesbare Liste. */
function occurrences(AdminTaskRecurrence $recurrence, string $after, int $limit = 4): array
{
    return array_map(fn ($date) => $date->format('D d.m.Y H:i'), $recurrence->nextOccurrences(Carbon::parse($after), $limit));
}

afterEach(fn () => Carbon::setTestNow());

it('berechnet taegliche Termine, auch mit Abstand und nur an Werktagen', function () {
    // Do 01.10.2026, 07:00 ist um 09:00 schon vorbei.
    expect(occurrences(recurrence(), '2026-10-01 09:00', 3))
        ->toBe(['Fri 02.10.2026 07:00', 'Sat 03.10.2026 07:00', 'Sun 04.10.2026 07:00'])
        ->and(occurrences(recurrence(), '2026-10-01 06:00', 1))->toBe(['Thu 01.10.2026 07:00'])
        // Vor dem Beginn: der erste Termin ist der Starttag.
        ->and(occurrences(recurrence(), '2026-09-01 00:00', 1))->toBe(['Thu 01.10.2026 07:00'])
        ->and(occurrences(recurrence(['interval' => 3]), '2026-10-01 09:00', 3))
        ->toBe(['Sun 04.10.2026 07:00', 'Wed 07.10.2026 07:00', 'Sat 10.10.2026 07:00'])
        ->and(occurrences(recurrence(['weekend_mode' => 'skip']), '2026-10-02 09:00', 2))
        ->toBe(['Mon 05.10.2026 07:00', 'Tue 06.10.2026 07:00']);
});

it('berechnet woechentliche Termine an mehreren Wochentagen und im Mehrwochen-Abstand', function () {
    $weekly = recurrence(['frequency' => 'weekly', 'weekdays' => [1, 4], 'create_time' => '08:30:00']);

    expect(occurrences($weekly, '2026-10-01 09:00', 4))
        ->toBe(['Mon 05.10.2026 08:30', 'Thu 08.10.2026 08:30', 'Mon 12.10.2026 08:30', 'Thu 15.10.2026 08:30'])
        // Der Montag der Startwoche liegt vor dem Beginn und zaehlt nicht.
        ->and(occurrences($weekly, '2026-09-01 00:00', 1))->toBe(['Thu 01.10.2026 08:30']);

    // Alle zwei Wochen, gerechnet ab der Startwoche (KW 40): KW 40, 42, 44 …
    expect(occurrences(recurrence(['frequency' => 'weekly', 'interval' => 2, 'weekdays' => [5]]), '2026-10-01 00:00', 3))
        ->toBe(['Fri 02.10.2026 07:00', 'Fri 16.10.2026 07:00', 'Fri 30.10.2026 07:00']);

    // Ohne Wochentag gilt der des Starttags.
    expect(occurrences(recurrence(['frequency' => 'weekly', 'weekdays' => []]), '2026-10-01 09:00', 1))->toBe(['Thu 08.10.2026 07:00']);
});

it('berechnet monatliche Termine: fester Tag, Monatsende, n-ter Wochentag und Wochenenden', function () {
    $monthly = fn (array $attributes) => recurrence(['frequency' => 'monthly', 'starts_on' => '2026-01-01'] + $attributes);

    // Den 31. gibt es nicht in jedem Monat – dann gilt der letzte Tag.
    expect(occurrences($monthly(['day_of_month' => 31]), '2027-01-15 00:00', 4))
        ->toBe(['Sun 31.01.2027 07:00', 'Sun 28.02.2027 07:00', 'Wed 31.03.2027 07:00', 'Fri 30.04.2027 07:00'])
        // Schaltjahr
        ->and(occurrences($monthly(['day_of_month' => 0]), '2028-02-01 00:00', 1))->toBe(['Tue 29.02.2028 07:00'])
        // Zweiter Dienstag und letzter Freitag
        ->and(occurrences($monthly(['monthly_mode' => 'weekday', 'nth' => 2, 'nth_weekday' => 2]), '2026-10-01 00:00', 2))
        ->toBe(['Tue 13.10.2026 07:00', 'Tue 10.11.2026 07:00'])
        ->and(occurrences($monthly(['monthly_mode' => 'weekday', 'nth' => -1, 'nth_weekday' => 5]), '2026-10-01 00:00', 2))
        ->toBe(['Fri 30.10.2026 07:00', 'Fri 27.11.2026 07:00'])
        // Alle drei Monate ab Januar: Januar, April, Juli, Oktober
        ->and(occurrences($monthly(['interval' => 3, 'day_of_month' => 15]), '2026-10-01 00:00', 2))
        ->toBe(['Thu 15.10.2026 07:00', 'Fri 15.01.2027 07:00']);

    // Der 1. November 2026 ist ein Sonntag, der 1. Oktober ein Donnerstag.
    expect(occurrences($monthly(['day_of_month' => 1, 'weekend_mode' => 'keep']), '2026-10-15 00:00', 1))->toBe(['Sun 01.11.2026 07:00'])
        ->and(occurrences($monthly(['day_of_month' => 1, 'weekend_mode' => 'before']), '2026-10-15 00:00', 2))
        ->toBe(['Fri 30.10.2026 07:00', 'Tue 01.12.2026 07:00'])
        ->and(occurrences($monthly(['day_of_month' => 1, 'weekend_mode' => 'after']), '2026-10-15 00:00', 1))->toBe(['Mon 02.11.2026 07:00'])
        ->and(occurrences($monthly(['day_of_month' => 1, 'weekend_mode' => 'skip']), '2026-10-15 00:00', 1))->toBe(['Tue 01.12.2026 07:00']);
});

it('berechnet jaehrliche Termine und beachtet Enddatum und Hoechstzahl', function () {
    $yearly = recurrence(['frequency' => 'yearly', 'month' => 2, 'day_of_month' => 29, 'starts_on' => '2026-01-01']);

    // 29. Februar: ausserhalb von Schaltjahren der 28.
    expect(occurrences($yearly, '2026-10-01 00:00', 3))
        ->toBe(['Sun 28.02.2027 07:00', 'Tue 29.02.2028 07:00', 'Wed 28.02.2029 07:00'])
        ->and(occurrences(recurrence(['frequency' => 'yearly', 'month' => 11, 'monthly_mode' => 'weekday', 'nth' => 1, 'nth_weekday' => 1, 'starts_on' => '2026-01-01']), '2026-10-01 00:00', 2))
        ->toBe(['Mon 02.11.2026 07:00', 'Mon 01.11.2027 07:00']);

    // Enddatum: danach nichts mehr.
    $limited = recurrence(['ends_on' => '2026-10-03']);

    expect(occurrences($limited, '2026-10-01 09:00', 10))->toBe(['Fri 02.10.2026 07:00', 'Sat 03.10.2026 07:00'])
        ->and(occurrences($limited, '2026-10-03 09:00', 10))->toBe([]);

    // Hoechstzahl: nur noch so viele, wie uebrig sind.
    $counted = recurrence(['max_occurrences' => 3]);
    $counted->occurrences_count = 2;

    expect(occurrences($counted, '2026-10-01 09:00', 10))->toHaveCount(1);

    $counted->occurrences_count = 3;

    expect(occurrences($counted, '2026-10-01 09:00', 10))->toBe([])->and($counted->isFinished())->toBeTrue();
});

it('beschreibt den Rhythmus in Worten', function () {
    expect(recurrence()->summary())->toBe('Jeden Tag um 07:00 Uhr')
        ->and(recurrence(['weekend_mode' => 'skip', 'interval' => 2])->summary())->toBe('Alle 2 Tage (nur Montag bis Freitag) um 07:00 Uhr')
        ->and(recurrence(['frequency' => 'weekly', 'weekdays' => [4, 1], 'create_time' => '08:30:00'])->summary())->toBe('Jede Woche am Montag und Donnerstag um 08:30 Uhr')
        ->and(recurrence(['frequency' => 'monthly', 'day_of_month' => 0, 'weekend_mode' => 'before'])->summary())->toBe('Jeden Monat am letzten Tag (am Wochenende: Freitag davor) um 07:00 Uhr')
        ->and(recurrence(['frequency' => 'monthly', 'interval' => 3, 'monthly_mode' => 'weekday', 'nth' => 2, 'nth_weekday' => 2])->summary())->toBe('Alle 3 Monate am zweiten Dienstag um 07:00 Uhr')
        ->and(recurrence(['frequency' => 'yearly', 'month' => 3, 'day_of_month' => 15])->summary())->toBe('Jedes Jahr am 15. März um 07:00 Uhr');
});

it('legt die Aufgabe zum Termin automatisch an – genau einmal', function () {
    Mail::fake();
    Carbon::setTestNow('2026-10-01 09:00:00');

    $anna = recurringUser('Anna');
    $dennis = recurringUser('Dennis');

    $series = recurrence([
        'title' => 'Wochenbericht KW {kw} ({monat} {jahr})',
        'description' => 'Zahlen aus dem Dashboard zusammenstellen.',
        'frequency' => 'weekly', 'weekdays' => [1],
        'created_by' => $anna->id, 'responsible_id' => $anna->id, 'next_assignee_id' => $dennis->id,
        'due_in_days' => 2, 'remind_days_before' => 1, 'remind_time' => '10:00:00',
        'priority' => AdminTask::PRIORITY_HIGH,
    ]);
    $series->scheduleNext()->save();

    expect($series->next_run_at->format('Y-m-d H:i'))->toBe('2026-10-05 07:00');

    // Vor dem Termin passiert nichts.
    $this->artisan('tasks:create-recurring')->assertSuccessful();
    expect(AdminTask::count())->toBe(0);

    // Der Zeitplaner laeuft kurz nach dem Termin – und dann noch einmal.
    Carbon::setTestNow('2026-10-05 07:03:00');
    $this->artisan('tasks:create-recurring')->assertSuccessful();
    $this->artisan('tasks:create-recurring')->assertSuccessful();

    expect(AdminTask::count())->toBe(1);

    $task = AdminTask::first();

    expect($task->title)->toBe('Wochenbericht KW 41 (Oktober 2026)')
        ->and($task->description)->toBe('Zahlen aus dem Dashboard zusammenstellen.')
        ->and($task->recurrence_id)->toBe($series->id)
        ->and($task->created_by)->toBe($anna->id)
        ->and($task->responsible_id)->toBe($anna->id)
        ->and($task->next_assignee_id)->toBe($dennis->id)
        ->and($task->due_date->format('Y-m-d'))->toBe('2026-10-07')
        ->and($task->reminders()->count())->toBe(1)
        ->and($task->reminders()->first()->remind_at->format('Y-m-d H:i'))->toBe('2026-10-06 10:00')
        ->and($task->reminders()->first()->user_id)->toBeNull()
        ->and($task->priority)->toBe(AdminTask::PRIORITY_HIGH)
        ->and($task->status)->toBe(AdminTask::STATUS_OPEN);

    // Beide Beteiligten erfahren per Mail davon.
    Mail::assertSent(AdminTaskAssignedMail::class, 2);

    $series->refresh();

    expect($series->occurrences_count)->toBe(1)
        ->and($series->last_run_at->format('Y-m-d H:i'))->toBe('2026-10-05 07:03')
        ->and($series->next_run_at->format('Y-m-d H:i'))->toBe('2026-10-12 07:00');
});

it('holt nach einem Ausfall des Zeitplaners nur eine Aufgabe nach', function () {
    Carbon::setTestNow('2026-10-01 06:00:00');

    $anna = recurringUser('Anna');
    $series = recurrence(['created_by' => $anna->id, 'responsible_id' => $anna->id]);
    $series->scheduleNext()->save();

    // Vier Tage lang lief nichts.
    Carbon::setTestNow('2026-10-05 12:00:00');
    $this->artisan('tasks:create-recurring')->assertSuccessful();

    expect(AdminTask::count())->toBe(1)
        ->and($series->fresh()->next_run_at->format('Y-m-d H:i'))->toBe('2026-10-06 07:00');
});

it('ueberspringt den Termin, solange die vorige Aufgabe offen ist, und endet nach der Hoechstzahl', function () {
    Carbon::setTestNow('2026-10-01 06:00:00');

    $anna = recurringUser('Anna');
    $series = recurrence(['created_by' => $anna->id, 'responsible_id' => $anna->id, 'skip_if_open' => true, 'max_occurrences' => 2]);
    $series->scheduleNext()->save();

    Carbon::setTestNow('2026-10-01 07:01:00');
    $this->artisan('tasks:create-recurring');

    // Am naechsten Tag ist die erste Aufgabe noch offen: Termin entfaellt.
    Carbon::setTestNow('2026-10-02 07:01:00');
    $this->artisan('tasks:create-recurring');

    expect(AdminTask::count())->toBe(1)
        ->and($series->fresh()->last_skipped_at->format('Y-m-d'))->toBe('2026-10-02')
        ->and($series->fresh()->occurrences_count)->toBe(1);

    AdminTask::first()->update(['status' => AdminTask::STATUS_DONE]);

    Carbon::setTestNow('2026-10-03 07:01:00');
    $this->artisan('tasks:create-recurring');

    // Zweite und letzte Aufgabe: danach gibt es keinen Termin mehr.
    expect(AdminTask::count())->toBe(2)
        ->and($series->fresh()->next_run_at)->toBeNull()
        ->and($series->fresh()->state())->toBe('finished');

    Carbon::setTestNow('2026-10-04 07:01:00');
    $this->artisan('tasks:create-recurring');

    expect(AdminTask::count())->toBe(2);
});

it('faengt eine fehlende verantwortliche Person ab und laesst pausierte Serien ruhen', function () {
    Carbon::setTestNow('2026-10-01 06:00:00');

    $anna = recurringUser('Anna');
    $gone = recurringUser('Weg', active: false);

    // Verantwortliche Person deaktiviert: die Aufgabe geht an den Erfasser der Serie.
    $fallback = recurrence(['title' => 'Mit Ersatz', 'created_by' => $anna->id, 'responsible_id' => $gone->id]);
    $fallback->scheduleNext()->save();

    // Niemand mehr da: die Serie wird angehalten.
    $orphan = recurrence(['title' => 'Verwaist', 'created_by' => $gone->id, 'responsible_id' => $gone->id]);
    $orphan->scheduleNext()->save();

    $paused = recurrence(['title' => 'Pausiert', 'created_by' => $anna->id, 'responsible_id' => $anna->id, 'is_active' => false]);
    $paused->scheduleNext()->save();

    expect($paused->next_run_at)->toBeNull();

    Carbon::setTestNow('2026-10-01 07:01:00');
    $this->artisan('tasks:create-recurring');

    expect(AdminTask::pluck('title')->all())->toBe(['Mit Ersatz'])
        ->and(AdminTask::first()->responsible_id)->toBe($anna->id)
        ->and($fallback->fresh()->last_error)->toContain('ging an den Erfasser')
        ->and($orphan->fresh()->is_active)->toBeFalse()
        ->and($orphan->fresh()->next_run_at)->toBeNull()
        ->and($orphan->fresh()->last_error)->toContain('Angehalten');
});

it('laesst wiederkehrende Aufgaben unter System anlegen, mit Vorschau der Termine', function () {
    Carbon::setTestNow('2026-10-01 09:00:00');

    $anna = recurringUser('Anna');
    $dennis = recurringUser('Dennis');
    $category = AdminTaskCategory::firstOrCreate(['name' => 'Stammdaten']);

    $this->actingAs($anna);

    $this->get(route('adminv2.system.recurring-tasks.index'))->assertOk()->assertSee('Noch keine wiederkehrenden Aufgaben');
    $this->get(route('adminv2.system.recurring-tasks.create'))->assertOk()->assertSee('Vorschau');

    $editor = Livewire::test(Editor::class)
        ->assertSet('responsibleId', (string) $anna->id)
        ->assertSet('startsOn', '2026-10-01')
        ->call('save')
        ->assertHasErrors(['title', 'categoryId'])
        ->set('title', 'Flughafen-Codes abgleichen ({monat})')
        ->set('categoryId', (string) $category->id)
        ->set('responsibleId', (string) $dennis->id)
        ->set('frequency', 'monthly')
        ->set('monthlyMode', 'weekday')
        ->set('nth', '1')
        ->set('nthWeekday', '1')
        ->set('createTime', '06:30')
        ->set('dueInDays', '5')
        ->set('hasReminder', true)
        ->set('remindDaysBefore', '9')
        // Vorschau: erster Montag im Monat
        ->assertSee('Jeden Monat am ersten Montag um 06:30 Uhr')
        ->assertSee('05.10.2026 06:30')
        ->assertSee('02.11.2026 06:30')
        ->assertSee('Flughafen-Codes abgleichen (November)')
        ->assertSee('fällig 10.10.2026')
        // Die Erinnerung laege vor dem Anlegen.
        ->call('save')
        ->assertHasErrors('remindDaysBefore')
        ->set('remindDaysBefore', '1')
        // Ende nach Datum, das vor dem Beginn liegt
        ->set('endMode', 'date')
        ->set('endsOn', '2026-09-01')
        ->call('save')
        ->assertHasErrors('endsOn')
        ->set('endMode', 'count')
        ->set('maxOccurrences', '12')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('adminv2.system.recurring-tasks.index'));

    $series = AdminTaskRecurrence::first();

    expect($series->created_by)->toBe($anna->id)
        ->and($series->responsible_id)->toBe($dennis->id)
        ->and($series->frequency)->toBe('monthly')
        ->and($series->monthly_mode)->toBe('weekday')
        ->and($series->due_in_days)->toBe(5)
        ->and($series->remind_days_before)->toBe(1)
        ->and($series->max_occurrences)->toBe(12)
        ->and($series->ends_on)->toBeNull()
        ->and($series->next_run_at->format('Y-m-d H:i'))->toBe('2026-10-05 06:30');

    // Bearbeiten: die Werte stehen wieder im Formular.
    Livewire::test(Editor::class, ['recurrence' => $series->id])
        ->assertSet('monthlyMode', 'weekday')
        ->assertSet('endMode', 'count')
        ->assertSet('createTime', '06:30')
        ->set('frequency', 'daily')
        ->set('workdaysOnly', true)
        ->assertSee('Jeden Tag (nur Montag bis Freitag) um 06:30 Uhr')
        ->call('save')
        ->assertHasNoErrors();

    expect($series->fresh()->weekend_mode)->toBe('skip')
        ->and($series->fresh()->next_run_at->format('Y-m-d H:i'))->toBe('2026-10-02 06:30');

    // Liste: pausieren, fortsetzen, sofort anlegen, loeschen.
    $list = Livewire::test(Index::class)
        ->assertSee('Flughafen-Codes abgleichen ({monat})')
        ->assertSee('Nächste Aufgabe 02.10.2026 06:30')
        ->call('toggleActive', $series->id);

    expect($series->fresh()->is_active)->toBeFalse()->and($series->fresh()->next_run_at)->toBeNull();

    $list->assertSee('Pausiert')->call('toggleActive', $series->id)->call('createNow', $series->id);

    $task = AdminTask::first();

    expect($task->title)->toBe('Flughafen-Codes abgleichen (Oktober)')
        ->and($task->recurrence_id)->toBe($series->id)
        ->and($series->fresh()->occurrences_count)->toBe(1)
        // Ausser der Reihe: der Rhythmus bleibt.
        ->and($series->fresh()->next_run_at->format('Y-m-d H:i'))->toBe('2026-10-02 06:30');

    // Die Aufgabe zeigt, aus welcher Serie sie stammt.
    $this->get(route('adminv2.tasks.show', $task))->assertOk()->assertSee('Wiederkehrend:');

    $list->call('delete', $series->id);

    expect(AdminTaskRecurrence::count())->toBe(0)
        ->and(AdminTask::count())->toBe(1);

    $this->get(route('adminv2.tasks.show', $task))->assertOk()->assertSee('Serie gelöscht');
});

it('ist nur fuer Administratoren erreichbar', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false, 'is_active' => true]));

    $this->get(route('adminv2.system.recurring-tasks.index'))->assertForbidden();
    $this->get(route('adminv2.system.recurring-tasks.create'))->assertForbidden();
});
