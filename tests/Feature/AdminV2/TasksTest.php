<?php

use App\Livewire\AdminV2\Events\Editor;
use App\Livewire\AdminV2\Tasks\Detail;
use App\Livewire\AdminV2\Tasks\Index;
use App\Livewire\AdminV2\Tasks\Panel;
use App\Mail\AdminTaskAssignedMail;
use App\Mail\AdminTaskDueMail;
use App\Mail\AdminTaskReminderMail;
use App\Models\AdminTask;
use App\Models\AdminTaskActivity;
use App\Models\AdminTaskCategory;
use App\Models\CustomEvent;
use App\Models\EventType;
use App\Models\User;
use App\Services\CustomEventVersionService;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

/**
 * Aufgabenverwaltung im Admin-Bereich: Aufgaben fuer sich und andere, Verlauf,
 * Notizen, Erinnerungen und Aufgaben an einem Ereignis.
 */
function employee(string $name): User
{
    return User::factory()->create(['name' => $name, 'is_admin' => true, 'is_active' => true]);
}

function taskCategory(string $name = 'Global Travel Monitor'): AdminTaskCategory
{
    return AdminTaskCategory::firstOrCreate(['name' => $name]);
}

function makeTask(array $attributes = []): AdminTask
{
    return AdminTask::create(array_merge([
        'title' => 'Quelle nachprüfen',
        'category_id' => taskCategory()->id,
    ], $attributes));
}

it('bringt die drei Standard-Rubriken mit', function () {
    expect(AdminTaskCategory::ordered()->pluck('name')->all())
        ->toBe(['Global Travel Monitor', 'Travel Alert', 'Stammdaten']);
});

it('legt eine Aufgabe fuer eine andere Person an', function () {
    $anna = employee('Anna');
    $dennis = employee('Dennis');
    $kerstin = employee('Kerstin');

    $this->actingAs($anna);

    Livewire::test(Detail::class)
        ->assertSet('responsibleId', (string) $anna->id)
        ->set('title', 'Flughafencodes prüfen')
        ->set('categoryId', (string) taskCategory('Stammdaten')->id)
        ->set('dueDate', '2026-10-20')
        ->set('priority', AdminTask::PRIORITY_HIGH)
        ->call('addReminder')
        // Vorbelegt: der Morgen des Faelligkeitstags, fuer den jeweiligen Bearbeiter.
        ->assertSet('reminders.0.remindAt', '2026-10-20T09:00')
        ->set('reminders.0.remindAt', '2026-10-19T09:00')
        ->set('responsibleId', (string) $dennis->id)
        ->set('nextAssigneeId', (string) $kerstin->id)
        ->call('save')
        ->assertHasNoErrors()
        // Nach dem Anlegen geht es auf der Seite der neuen Aufgabe weiter.
        ->assertRedirect(route('adminv2.tasks.show', AdminTask::firstWhere('title', 'Flughafencodes prüfen')));

    $task = AdminTask::firstWhere('title', 'Flughafencodes prüfen');

    expect($task->created_by)->toBe($anna->id)
        ->and($task->responsible_id)->toBe($dennis->id)
        ->and($task->next_assignee_id)->toBe($kerstin->id)
        ->and($task->currentHandler()->is($kerstin))->toBeTrue()
        ->and($task->category->name)->toBe('Stammdaten')
        ->and($task->due_date->format('Y-m-d'))->toBe('2026-10-20')
        ->and($task->activities()->pluck('type')->all())->toBe([AdminTaskActivity::TYPE_CREATED]);
});

it('zeigt jedem die Aufgaben, die bei ihm liegen', function () {
    $anna = employee('Anna');
    $dennis = employee('Dennis');

    makeTask(['title' => 'Liegt bei Dennis', 'created_by' => $anna->id, 'responsible_id' => $anna->id, 'next_assignee_id' => $dennis->id]);
    makeTask(['title' => 'Liegt bei Anna', 'created_by' => $anna->id, 'responsible_id' => $anna->id]);
    makeTask(['title' => 'Schon erledigt', 'responsible_id' => $dennis->id, 'status' => AdminTask::STATUS_DONE]);

    $this->actingAs($dennis);

    Livewire::test(Index::class)
        ->assertSee('Liegt bei Dennis')
        ->assertDontSee('Liegt bei Anna')
        ->assertDontSee('Schon erledigt')
        ->set('tab', 'all')
        ->assertSee('Liegt bei Anna')
        ->set('status', 'done')
        ->assertSee('Schon erledigt')
        ->assertDontSee('Liegt bei Anna');

    $this->actingAs($anna);

    Livewire::test(Index::class)
        ->assertSee('Liegt bei Anna')
        ->assertDontSee('Liegt bei Dennis')
        ->set('tab', 'created')
        ->assertSee('Liegt bei Dennis');
});

it('haelt im Verlauf fest, wer wann was geaendert hat', function () {
    $anna = employee('Anna');
    $dennis = employee('Dennis');

    $this->actingAs($anna);

    $task = makeTask(['created_by' => $anna->id, 'responsible_id' => $anna->id]);

    Livewire::test(Detail::class, ['task' => $task->id])
        ->set('status', AdminTask::STATUS_IN_PROGRESS)
        ->set('nextAssigneeId', (string) $dennis->id)
        ->set('dueDate', '2026-11-01')
        ->call('save')
        ->set('note', 'Rückfrage beim Auswärtigen Amt gestellt.')
        ->call('addNote')
        ->assertHasNoErrors()
        ->assertSee('Rückfrage beim Auswärtigen Amt gestellt.')
        ->assertSee('In Arbeit');

    $entries = $task->activities()->oldest('id')->get();

    expect($entries->pluck('type')->all())->toBe([
        AdminTaskActivity::TYPE_CREATED,
        AdminTaskActivity::TYPE_CHANGED,
        AdminTaskActivity::TYPE_NOTE,
    ]);

    $changes = collect($entries[1]->changes)->keyBy('field');

    expect($entries[1]->user_id)->toBe($anna->id)
        ->and($changes['status']['old'])->toBe('Offen')
        ->and($changes['status']['new'])->toBe('In Arbeit')
        ->and($changes['next_assignee_id']['old'])->toBeNull()
        ->and($changes['next_assignee_id']['new'])->toBe('Dennis')
        ->and($changes['due_date']['new'])->toBe('01.11.2026')
        ->and($entries[2]->body)->toBe('Rückfrage beim Auswärtigen Amt gestellt.');

    // Speichern ohne Aenderung erzeugt keinen leeren Eintrag.
    Livewire::test(Detail::class, ['task' => $task->id])->call('save');

    expect($task->activities()->count())->toBe(3);
});

it('ordnet Aufgaben eines neuen Ereignisses beim ersten Speichern zu', function () {
    $type = EventType::create(['code' => 'safety', 'name' => 'Sicherheit', 'icon' => 'fa-shield-alt', 'is_active' => true, 'sort_order' => 1]);

    $this->actingAs($admin = employee('Anna'));

    $editor = Livewire::test(Editor::class);
    $token = $editor->get('taskToken');

    expect($token)->not->toBeNull();

    // Aufgabe aus der Seitenspalte des noch nicht gespeicherten Ereignisses.
    Livewire::withQueryParams(['token' => $token, 'category' => 'Global Travel Monitor'])
        ->test(Detail::class)
        ->assertSet('categoryId', (string) taskCategory()->id)
        ->assertSee('wird beim Speichern des Ereignisses zugeordnet')
        ->set('title', 'Karte prüfen')
        ->call('save')
        ->assertHasNoErrors();

    Livewire::withQueryParams([])
        ->test(Panel::class, ['eventId' => null, 'token' => $token])
        ->assertSee('Karte prüfen')
        // Die Aufgabe oeffnet sich in einem eigenen Tab.
        ->assertSee(route('adminv2.tasks.show', AdminTask::firstWhere('title', 'Karte prüfen')), false)
        ->assertSee('token='.$token, false);

    $editor->set('titles.de', 'Streik in Rom')
        ->set('eventTypeIds', [(string) $type->id])
        ->call('save');

    $event = CustomEvent::firstWhere('title', 'Streik in Rom');
    $task = AdminTask::firstWhere('title', 'Karte prüfen');

    expect($task->subject->is($event))->toBeTrue()
        ->and($task->subject_token)->toBeNull();

    // Die Aufgabe bleibt auch an einer neuen Version des Ereignisses sichtbar.
    $version = app(CustomEventVersionService::class)->createNewVersion($event, $admin->id);

    Livewire::test(Panel::class, ['eventId' => $version->id, 'token' => null])
        ->assertSee('Karte prüfen')
        ->call('toggleDone', $task->id);

    expect($task->fresh()->isDone())->toBeTrue()
        ->and($task->fresh()->completed_at)->not->toBeNull();
});

it('zeigt in der Ereignisliste, wie viele Aufgaben ein Ereignis hat und wie viele offen sind', function () {
    $this->actingAs($admin = employee('Anna'));

    $event = CustomEvent::create([
        'title' => 'Streik in Rom', 'popup_content' => 'Text', 'event_type' => 'other', 'priority' => 'medium',
        'start_date' => now()->subDay(), 'is_active' => true, 'archived' => false, 'review_status' => 'approved',
    ]);
    $other = CustomEvent::create([
        'title' => 'Ohne Aufgaben', 'popup_content' => 'Text', 'event_type' => 'other', 'priority' => 'low',
        'start_date' => now()->subDay(), 'is_active' => true, 'archived' => false, 'review_status' => 'approved',
    ]);

    $subject = ['subject_type' => $event->getMorphClass(), 'subject_id' => $event->id];
    makeTask($subject + ['title' => 'Offen 1']);
    makeTask($subject + ['title' => 'Offen 2', 'status' => AdminTask::STATUS_IN_PROGRESS]);
    makeTask($subject + ['title' => 'Fertig', 'status' => AdminTask::STATUS_DONE]);

    $list = Livewire::test(\App\Livewire\AdminV2\Events\Index::class);

    expect($list->viewData('taskCounts')[$event->id])->toBe(['total' => 3, 'open' => 2])
        ->and($list->viewData('taskCounts')[$other->id])->toBe(['total' => 0, 'open' => 0]);

    $list->assertSee('3 Aufgaben, 2 offen')->assertSee('0 Aufgaben');

    // Eine neue Version uebernimmt die Aufgaben ihres Ereignisses.
    $version = app(CustomEventVersionService::class)->createNewVersion($event, $admin->id);

    expect(Livewire::test(\App\Livewire\AdminV2\Events\Index::class)->set('tab', 'draft')->viewData('taskCounts')[$version->id])
        ->toBe(['total' => 3, 'open' => 2]);
});

it('filtert die Ereignisliste nach den Aufgaben der Ereignisse', function () {
    $anna = employee('Anna');
    $dennis = employee('Dennis');

    $event = fn (string $title) => CustomEvent::create([
        'title' => $title, 'popup_content' => 'Text', 'event_type' => 'other', 'priority' => 'medium',
        'start_date' => now()->subDay(), 'is_active' => true, 'archived' => false, 'review_status' => 'approved',
    ]);
    $taskFor = fn (CustomEvent $subject, array $attributes) => makeTask($attributes + [
        'subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->id, 'responsible_id' => $anna->id,
    ]);

    $withOpen = $event('Mit offener Aufgabe');
    $taskFor($withOpen, ['title' => 'Offen bei Anna']);

    $forDennis = $event('Liegt bei Dennis');
    $taskFor($forDennis, ['title' => 'Für Dennis', 'next_assignee_id' => $dennis->id, 'due_date' => now()->subDays(2)]);

    $allDone = $event('Alles erledigt');
    $taskFor($allDone, ['title' => 'Fertig', 'status' => AdminTask::STATUS_DONE]);

    $event('Ganz ohne Aufgaben');

    $this->actingAs($anna);

    Livewire::test(\App\Livewire\AdminV2\Events\Index::class)
        ->set('taskFilter', 'open')
        ->assertSee('Mit offener Aufgabe')->assertSee('Liegt bei Dennis')
        ->assertDontSee('Alles erledigt')->assertDontSee('Ganz ohne Aufgaben')
        ->set('taskFilter', 'mine')
        ->assertSee('Mit offener Aufgabe')->assertDontSee('Liegt bei Dennis')
        ->set('taskFilter', 'overdue')
        ->assertSee('Liegt bei Dennis')->assertDontSee('Mit offener Aufgabe')
        ->set('taskFilter', 'done')
        ->assertSee('Alles erledigt')->assertDontSee('Mit offener Aufgabe')->assertDontSee('Ganz ohne Aufgaben')
        ->set('taskFilter', 'none')
        ->assertSee('Ganz ohne Aufgaben')->assertDontSee('Alles erledigt')
        ->set('taskFilter', '')
        ->set('taskPerson', (string) $dennis->id)
        ->assertSee('Liegt bei Dennis')->assertDontSee('Mit offener Aufgabe')
        ->assertSee('Filter Offene Aufgaben bei Dennis entfernen')
        ->call('removeFilter', 'taskPerson')
        ->assertSee('Ganz ohne Aufgaben');
});

it('verschickt eine faellige Erinnerung genau einmal an den naechsten Bearbeiter', function () {
    Mail::fake();

    $anna = employee('Anna');
    $dennis = employee('Dennis');

    $due = makeTask(['title' => 'Jetzt fällig', 'responsible_id' => $anna->id, 'next_assignee_id' => $dennis->id]);
    $reminder = $due->reminders()->create(['remind_at' => now()->subMinute()]);
    makeTask(['title' => 'Erst morgen', 'responsible_id' => $anna->id])->reminders()->create(['remind_at' => now()->addDay()]);
    makeTask(['title' => 'Schon erledigt', 'responsible_id' => $anna->id, 'status' => AdminTask::STATUS_DONE])->reminders()->create(['remind_at' => now()->subHour()]);

    $this->artisan('tasks:send-reminders')->assertSuccessful();
    $this->artisan('tasks:send-reminders')->assertSuccessful();

    Mail::assertSent(AdminTaskReminderMail::class, 1);
    Mail::assertSent(AdminTaskReminderMail::class, fn (AdminTaskReminderMail $mail) => $mail->hasTo($dennis->email) && $mail->task->is($due));

    // Die Erinnerung selbst ist keine Aenderung der Aufgabe.
    expect($due->activities()->count())->toBe(1)
        ->and($reminder->fresh()->sent_at)->not->toBeNull();

    // Wird die Erinnerung verschoben, geht sie erneut raus.
    $due->fresh()->syncReminders([['id' => $reminder->id, 'remind_at' => now()->subSecond()->toDateTimeString()]]);
    $this->artisan('tasks:send-reminders');

    Mail::assertSent(AdminTaskReminderMail::class, 2);
});

it('meldet per Mail, wenn eine Aufgabe fuer jemanden beginnt', function () {
    Mail::fake();

    $anna = employee('Anna');
    $dennis = employee('Dennis');
    $carla = employee('Carla');

    $this->actingAs($anna);

    // Anna legt eine Aufgabe an: Dennis verantwortet sie, Carla bearbeitet sie als Naechste.
    $task = makeTask(['title' => 'Quelle prüfen', 'created_by' => $anna->id, 'responsible_id' => $dennis->id, 'next_assignee_id' => $carla->id]);

    Mail::assertSent(AdminTaskAssignedMail::class, 2);
    Mail::assertSent(AdminTaskAssignedMail::class, fn (AdminTaskAssignedMail $mail) => $mail->hasTo($dennis->email) && $mail->isNew && $mail->assignedBy === 'Anna');
    Mail::assertSent(AdminTaskAssignedMail::class, fn (AdminTaskAssignedMail $mail) => $mail->hasTo($carla->email));

    // Eine Aufgabe fuer sich selbst loest keine Mail aus.
    makeTask(['title' => 'Eigene Aufgabe', 'created_by' => $anna->id, 'responsible_id' => $anna->id]);
    Mail::assertSent(AdminTaskAssignedMail::class, 2);

    // Uebergabe: nur die neue Bearbeiterin erfaehrt davon; andere Aenderungen loesen nichts aus.
    $task->update(['next_assignee_id' => $dennis->id]);
    $task->update(['title' => 'Quelle gründlich prüfen']);

    Mail::assertSent(AdminTaskAssignedMail::class, 3);
    Mail::assertSent(AdminTaskAssignedMail::class, fn (AdminTaskAssignedMail $mail) => $mail->hasTo($dennis->email) && ! $mail->isNew);

    expect((new AdminTaskAssignedMail($task))->render())->toContain('Neue Aufgabe für dich')->toContain('Quelle gründlich prüfen');
});

it('meldet per Mail, wenn eine Aufgabe faellig und nicht erledigt ist', function () {
    \Illuminate\Support\Carbon::setTestNow('2026-10-02 06:30:00');
    Mail::fake();

    $anna = employee('Anna');
    $dennis = employee('Dennis');

    $due = makeTask(['title' => 'Heute fällig', 'responsible_id' => $anna->id, 'next_assignee_id' => $dennis->id, 'due_date' => '2026-10-02']);
    makeTask(['title' => 'Überfällig', 'responsible_id' => $anna->id, 'due_date' => '2026-09-30']);
    makeTask(['title' => 'Erst morgen', 'responsible_id' => $anna->id, 'due_date' => '2026-10-03']);
    makeTask(['title' => 'Schon erledigt', 'responsible_id' => $anna->id, 'due_date' => '2026-10-01', 'status' => AdminTask::STATUS_DONE]);

    // Nicht mitten in der Nacht.
    $this->artisan('tasks:send-reminders')->assertSuccessful();
    Mail::assertNotSent(AdminTaskDueMail::class);

    \Illuminate\Support\Carbon::setTestNow('2026-10-02 08:00:00');
    $this->artisan('tasks:send-reminders')->assertSuccessful();
    $this->artisan('tasks:send-reminders')->assertSuccessful();

    // Heute faellig: an Bearbeiter und Verantwortliche. Ueberfaellig: nur an Anna (beides in einer Person).
    Mail::assertSent(AdminTaskDueMail::class, 3);
    Mail::assertSent(AdminTaskDueMail::class, fn (AdminTaskDueMail $mail) => $mail->hasTo($dennis->email) && $mail->task->is($due));
    Mail::assertSent(AdminTaskDueMail::class, fn (AdminTaskDueMail $mail) => $mail->hasTo($anna->email) && $mail->task->is($due));
    Mail::assertSent(AdminTaskDueMail::class, fn (AdminTaskDueMail $mail) => $mail->hasTo($anna->email) && $mail->task->title === 'Überfällig');

    // Keine Aenderung der Aufgabe – der Verlauf bleibt unberuehrt.
    expect($due->activities()->count())->toBe(1)
        ->and((new AdminTaskDueMail($due->fresh()))->render())->toContain('heute fällig und noch nicht erledigt');

    // Wird das Datum verschoben, geht die Mail am neuen Tag erneut raus.
    $due->fresh()->update(['due_date' => '2026-10-05']);
    $this->artisan('tasks:send-reminders');
    Mail::assertSent(AdminTaskDueMail::class, 3);

    \Illuminate\Support\Carbon::setTestNow('2026-10-05 09:00:00');
    $this->artisan('tasks:send-reminders');
    Mail::assertSent(AdminTaskDueMail::class, 6);

    \Illuminate\Support\Carbon::setTestNow();
});

it('laesst Rubriken ergaenzen und ausblenden', function () {
    $this->actingAs(employee('Anna'));

    Livewire::test(Index::class)
        ->set('newCategory', 'Kunden')
        ->call('addCategory')
        ->assertHasNoErrors()
        ->set('newCategory', 'Kunden')
        ->call('addCategory')
        ->assertHasErrors('newCategory')
        ->call('toggleCategory', taskCategory('Travel Alert')->id);

    expect(AdminTaskCategory::active()->ordered()->pluck('name')->all())
        ->toBe(['Global Travel Monitor', 'Stammdaten', 'Kunden']);
});

it('zeigt die Aufgabenseite und oeffnet eine Aufgabe ueber den Link aus der Mail', function () {
    $task = makeTask(['title' => 'Aus der Mail']);

    $this->actingAs(employee('Anna'));

    $this->get('/adminv2/tasks')->assertOk()->assertSee('Aufgaben');

    // Die Aufgabe hat eine eigene Seite; die Liste oeffnet sie in einem neuen Tab.
    $this->get(route('adminv2.tasks.show', $task))->assertOk()->assertSee('Aus der Mail')->assertSee('Notizen und Verlauf');
    $this->get(route('adminv2.tasks.create'))->assertOk()->assertSee('Neue Aufgabe');
    $this->get('/adminv2/tasks/999999')->assertNotFound();

    expect((new AdminTaskReminderMail($task))->render())->toContain(route('adminv2.tasks.show', $task));

    Livewire::withQueryParams([])
        ->test(Index::class)
        ->set('tab', 'all')
        ->assertSee('href="'.route('adminv2.tasks.show', $task).'"', false)
        ->assertSee('target="_blank"', false);

    // Loeschen fuehrt zurueck zur Liste.
    Livewire::withQueryParams([])
        ->test(Detail::class, ['task' => $task->id])
        ->call('delete')
        ->assertRedirect(route('adminv2.tasks.index'));

    expect(AdminTask::find($task->id))->toBeNull();
});

it('filtert und sortiert die Aufgaben wie die Ereignisliste', function () {
    \Illuminate\Support\Carbon::setTestNow('2026-10-02 09:00:00');

    $anna = employee('Anna');
    $dennis = employee('Dennis');
    $gtm = taskCategory('Global Travel Monitor');
    $alert = taskCategory('Travel Alert');

    $event = CustomEvent::create([
        'title' => 'Streik in Rom', 'popup_content' => 'Text', 'event_type' => 'other', 'priority' => 'medium',
        'start_date' => now()->subDay(), 'is_active' => true, 'archived' => false, 'review_status' => 'approved',
    ]);

    $base = ['created_by' => $anna->id, 'responsible_id' => $anna->id];

    makeTask($base + ['title' => 'Alpha überfällig', 'category_id' => $gtm->id, 'due_date' => '2026-09-28']);
    makeTask($base + ['title' => 'Bravo heute', 'category_id' => $alert->id, 'due_date' => '2026-10-02', 'status' => AdminTask::STATUS_IN_PROGRESS]);
    makeTask($base + ['title' => 'Charlie nächste Woche', 'category_id' => $gtm->id, 'due_date' => '2026-10-06', 'next_assignee_id' => $dennis->id]);
    makeTask($base + ['title' => 'Delta ohne Datum', 'category_id' => $alert->id, 'subject_type' => $event->getMorphClass(), 'subject_id' => $event->id]);
    makeTask($base + ['title' => 'Echo erledigt', 'category_id' => $gtm->id, 'due_date' => '2026-09-20', 'status' => AdminTask::STATUS_DONE]);
    makeTask(['title' => 'Foxtrott von Dennis', 'category_id' => $gtm->id, 'created_by' => $dennis->id, 'responsible_id' => $dennis->id, 'due_date' => '2026-11-15']);

    $this->actingAs($anna);

    $titles = fn ($list) => collect($list->viewData('tasks')->items())->pluck('title')->all();

    $list = Livewire::test(Index::class)->set('tab', 'all');

    // Standard: offene Aufgaben nach Faelligkeit, ohne Datum zuletzt.
    expect($titles($list))->toBe(['Alpha überfällig', 'Bravo heute', 'Charlie nächste Woche', 'Foxtrott von Dennis', 'Delta ohne Datum']);

    $list->assertSee('Liegt bei Dennis')->assertSee('Ereignis: Streik in Rom')->assertSee('überfällig');

    // Sortierung umkehren; ein Wechsel des Kriteriums beginnt wieder aufsteigend.
    expect($titles($list->call('toggleDirection'))[0])->toBe('Foxtrott von Dennis')
        ->and($titles($list->set('sort', 'title')))->toBe(['Alpha überfällig', 'Bravo heute', 'Charlie nächste Woche', 'Delta ohne Datum', 'Foxtrott von Dennis']);

    // Status
    expect($titles($list->set('status', 'in_progress')))->toBe(['Bravo heute'])
        ->and($titles($list->set('status', 'done')))->toBe(['Echo erledigt'])
        ->and($titles($list->set('status', 'all')))->toHaveCount(6);

    $list->assertSee('Filter Status Alle entfernen')->call('removeFilter', 'status')->assertSet('status', 'open');

    // Rubriken (Mehrfachauswahl)
    expect($titles($list->set('categories', [(string) $alert->id])))->toBe(['Bravo heute', 'Delta ohne Datum'])
        ->and($titles($list->set('categories', [(string) $alert->id, (string) $gtm->id])))->toHaveCount(5);

    $list->assertSee('Filter Rubrik Travel Alert entfernen')->call('removeFilter', 'categories', (string) $gtm->id)
        ->assertSet('categories', [(string) $alert->id])
        ->call('resetFilters')
        ->assertSet('categories', []);

    // Faelligkeit
    expect($titles($list->set('due', 'overdue')))->toBe(['Alpha überfällig'])
        ->and($titles($list->set('due', 'today')))->toBe(['Bravo heute'])
        ->and($titles($list->set('due', 'week')))->toBe(['Bravo heute', 'Charlie nächste Woche'])
        ->and($titles($list->set('due', 'none')))->toBe(['Delta ohne Datum'])
        ->and($titles($list->set('due', '')->set('dueFrom', '2026-10-05')->set('dueTo', '2026-10-31')))->toBe(['Charlie nächste Woche']);

    $list->assertSee('05.10.2026 – 31.10.2026')->call('removeFilter', 'period');

    // Personen und Rolle
    expect($titles($list->set('persons', [(string) $dennis->id])))->toBe(['Charlie nächste Woche', 'Foxtrott von Dennis'])
        ->and($titles($list->set('personRole', 'creator')))->toBe(['Foxtrott von Dennis'])
        ->and($titles($list->set('personRole', 'handler')))->toBe(['Charlie nächste Woche', 'Foxtrott von Dennis'])
        ->and($titles($list->set('personRole', 'responsible')))->toBe(['Foxtrott von Dennis']);

    $list->call('resetFilters');

    // Bezug
    expect($titles($list->set('subject', 'event')))->toBe(['Delta ohne Datum'])
        ->and($titles($list->set('subject', 'none')))->toHaveCount(4);

    // Die Zahlen an den Reitern folgen den Filtern.
    expect($list->set('subject', '')->set('categories', [(string) $gtm->id])->instance()->tabCounts)
        ->toBe(['mine' => 1, 'responsible' => 2, 'created' => 2, 'all' => 3]);

    \Illuminate\Support\Carbon::setTestNow();
});

it('verschickt beliebig viele Erinnerungen an verschiedene Personen', function () {
    \Illuminate\Support\Carbon::setTestNow('2026-10-02 08:00:00');
    Mail::fake();

    $anna = employee('Anna');
    $dennis = employee('Dennis');
    $carla = employee('Carla');

    $this->actingAs($anna);

    $task = makeTask(['title' => 'Quelle prüfen', 'created_by' => $anna->id, 'responsible_id' => $anna->id, 'next_assignee_id' => $dennis->id]);

    $page = Livewire::test(Detail::class, ['task' => $task->id])
        ->call('addReminder')
        ->call('addReminder')
        ->call('addReminder')
        // 1: an den jeweiligen Bearbeiter, 2: an Carla mit Hinweis, 3: an Anna, erst uebermorgen
        ->set('reminders.0.remindAt', '2026-10-02T09:00')
        ->set('reminders.1.remindAt', '2026-10-02T09:30')
        ->set('reminders.1.userId', (string) $carla->id)
        ->set('reminders.1.note', 'Bitte vorher beim Auswärtigen Amt nachsehen.')
        ->set('reminders.2.remindAt', '2026-10-04T10:00')
        ->set('reminders.2.userId', (string) $anna->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('reminders.1.userId', (string) $carla->id);

    expect($task->reminders()->count())->toBe(3);

    // Im Verlauf steht, welche Erinnerungen dazukamen.
    $changes = collect($task->activities()->latest('id')->first()->changes);

    expect($changes)->toHaveCount(3)
        ->and($changes->pluck('new')->all())->toContain('02.10.2026 09:30 an Carla („Bitte vorher beim Auswärtigen Amt nachsehen.“)')
        ->and($changes->pluck('new')->all())->toContain('02.10.2026 09:00 an den aktuellen Bearbeiter');

    // Um 10 Uhr sind zwei Erinnerungen faellig – jede geht an ihre Person, genau einmal.
    \Illuminate\Support\Carbon::setTestNow('2026-10-02 10:00:00');
    $this->artisan('tasks:send-reminders')->assertSuccessful();
    $this->artisan('tasks:send-reminders')->assertSuccessful();

    Mail::assertSent(AdminTaskReminderMail::class, 2);
    Mail::assertSent(AdminTaskReminderMail::class, fn (AdminTaskReminderMail $mail) => $mail->hasTo($dennis->email) && $mail->reminderNote === null);
    Mail::assertSent(AdminTaskReminderMail::class, fn (AdminTaskReminderMail $mail) => $mail->hasTo($carla->email) && $mail->reminderNote === 'Bitte vorher beim Auswärtigen Amt nachsehen.');

    expect((new AdminTaskReminderMail($task, 'Bitte vorher nachsehen.'))->render())->toContain('Bitte vorher nachsehen.');

    // Die Liste zeigt die naechste offene Erinnerung.
    Livewire::test(Index::class)->set('tab', 'all')->assertSee('Erinnerung 04.10.2026 10:00');

    // Eine verschickte Erinnerung verschieben: sie geht erneut raus. Eine entfernen: sie ist weg.
    $page = Livewire::test(Detail::class, ['task' => $task->id])
        ->assertSee('Verschickt am 02.10.2026 10:00')
        ->set('reminders.0.remindAt', '2026-10-03T09:00')
        ->call('removeReminder', 1)
        ->call('save')
        ->assertHasNoErrors();

    expect($task->reminders()->count())->toBe(2)
        ->and($task->reminders()->whereNull('sent_at')->count())->toBe(2);

    \Illuminate\Support\Carbon::setTestNow('2026-10-04 10:05:00');
    $this->artisan('tasks:send-reminders');

    Mail::assertSent(AdminTaskReminderMail::class, 4);
    Mail::assertSent(AdminTaskReminderMail::class, fn (AdminTaskReminderMail $mail) => $mail->hasTo($anna->email));

    // Eine Erinnerung ohne Zeitpunkt wird nicht gespeichert.
    $page->call('addReminder')->set('reminders.2.remindAt', '')->call('save')->assertHasErrors('reminders.2.remindAt');

    \Illuminate\Support\Carbon::setTestNow();
});

it('fuehrt eine Prioritaet an der Aufgabe – mit Filter und Sortierung', function () {
    $anna = employee('Anna');
    $this->actingAs($anna);

    $base = ['created_by' => $anna->id, 'responsible_id' => $anna->id];

    makeTask($base + ['title' => 'Unwichtig', 'priority' => AdminTask::PRIORITY_LOW]);
    $normal = makeTask($base + ['title' => 'Gewöhnlich']);
    makeTask($base + ['title' => 'Eilt', 'priority' => AdminTask::PRIORITY_URGENT]);
    makeTask($base + ['title' => 'Wichtig', 'priority' => AdminTask::PRIORITY_HIGH]);

    expect($normal->fresh()->priority)->toBe(AdminTask::PRIORITY_NORMAL);

    $titles = fn ($list) => collect($list->viewData('tasks')->items())->pluck('title')->all();

    $list = Livewire::test(Index::class)->assertSee('Dringend');

    // Nach Prioritaet sortiert stehen die dringenden zuerst.
    expect($titles($list->set('sort', 'priority')))->toBe(['Eilt', 'Wichtig', 'Gewöhnlich', 'Unwichtig'])
        ->and($titles($list->call('toggleDirection')))->toBe(['Unwichtig', 'Gewöhnlich', 'Wichtig', 'Eilt'])
        ->and($titles($list->set('priorities', ['urgent', 'high'])))->toBe(['Wichtig', 'Eilt']);

    $list->assertSee('Filter Priorität Dringend entfernen')
        ->call('removeFilter', 'priorities', 'urgent')
        ->assertSet('priorities', ['high']);

    // Aendern an der Aufgabe: steht im Verlauf.
    Livewire::test(Detail::class, ['task' => $normal->id])
        ->assertSet('priority', AdminTask::PRIORITY_NORMAL)
        ->set('priority', AdminTask::PRIORITY_URGENT)
        ->call('save')
        ->assertHasNoErrors()
        ->set('priority', 'unsinn')
        ->call('save')
        ->assertHasErrors('priority');

    $change = collect($normal->activities()->latest('id')->first()->changes)->firstWhere('field', 'priority');

    expect($normal->fresh()->priority)->toBe(AdminTask::PRIORITY_URGENT)
        ->and($change['old'])->toBe('Normal')
        ->and($change['new'])->toBe('Dringend');
});
