<?php

use App\Livewire\AdminV2\System\RecurringTasks\Editor as RecurringEditor;
use App\Livewire\AdminV2\System\Teams;
use App\Livewire\AdminV2\Tasks\Detail;
use App\Livewire\AdminV2\Tasks\Index;
use App\Mail\AdminTaskAssignedMail;
use App\Mail\AdminTaskDueMail;
use App\Mail\AdminTaskReminderMail;
use App\Models\AdminTask;
use App\Models\AdminTaskCategory;
use App\Models\AdminTaskRecurrence;
use App\Models\AdminTeam;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

/**
 * Teams der Mitarbeiter: anlegen, Mitglieder zuordnen, als Verantwortlicher
 * oder naechster Bearbeiter einer Aufgabe – samt Benachrichtigungen an die
 * zentrale Adresse oder an jedes Mitglied.
 */
function teamUser(string $name): User
{
    return User::factory()->create(['name' => $name, 'is_admin' => true, 'is_active' => true]);
}

function team(string $name, array $members = [], array $attributes = []): AdminTeam
{
    $team = AdminTeam::create(['name' => $name] + $attributes);
    $team->users()->sync(collect($members)->pluck('id')->all());

    return $team;
}

function teamTask(array $attributes = []): AdminTask
{
    return AdminTask::create(array_merge([
        'title' => 'Quelle prüfen',
        'category_id' => AdminTaskCategory::firstOrCreate(['name' => 'Global Travel Monitor'])->id,
    ], $attributes));
}

afterEach(fn () => Carbon::setTestNow());

it('laesst Teams unter System anlegen, bearbeiten und loeschen', function () {
    $anna = teamUser('Anna');
    $dennis = teamUser('Dennis');
    $carla = teamUser('Carla');

    $this->actingAs($anna);

    $this->get(route('adminv2.system.teams'))->assertOk()->assertSee('Noch keine Teams');

    $page = Livewire::test(Teams::class)
        ->call('create')
        ->call('save')
        ->assertHasErrors('name')
        ->set('name', 'Redaktion')
        ->set('description', 'Pflegt die Ereignisse')
        ->set('memberIds', [(string) $dennis->id, (string) $carla->id])
        // Zentrale Adresse gewaehlt, aber keine angegeben.
        ->set('notifyMode', AdminTeam::NOTIFY_TEAM_EMAIL)
        ->call('save')
        ->assertHasErrors('email')
        ->set('email', 'keine-adresse')
        ->call('save')
        ->assertHasErrors('email')
        ->set('email', 'redaktion@example.com')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Redaktion')
        ->assertSee('2 Mitglieder')
        ->assertSee('Benachrichtigungen an die Team-Adresse');

    $team = AdminTeam::firstWhere('name', 'Redaktion');

    expect($team->users()->pluck('users.id')->sort()->values()->all())->toBe([$dennis->id, $carla->id])
        ->and($team->notificationEmails())->toBe(['redaktion@example.com']);

    // Derselbe Name ein zweites Mal geht nicht.
    $page->call('create')->set('name', 'Redaktion')->call('save')->assertHasErrors('name');

    // Bearbeiten: Mitglieder aendern, einzeln benachrichtigen.
    $page->call('edit', $team->id)
        ->assertSet('name', 'Redaktion')
        ->assertSet('email', 'redaktion@example.com')
        ->set('memberIds', [(string) $anna->id, (string) $dennis->id, (string) $carla->id])
        ->set('notifyMode', AdminTeam::NOTIFY_MEMBERS)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('3 Mitglieder')
        ->assertSee('jedes Mitglied einzeln');

    expect($team->fresh()->notificationEmails())->toEqualCanonicalizing([$anna->email, $dennis->email, $carla->email]);

    // Mit offener Aufgabe laesst sich das Team nicht loeschen.
    $task = teamTask(['responsible_team_id' => $team->id]);

    $page->call('delete', $team->id);
    expect(AdminTeam::count())->toBe(1);

    $task->update(['status' => AdminTask::STATUS_DONE]);
    $page->call('delete', $team->id);

    expect(AdminTeam::count())->toBe(0)
        // Die erledigte Aufgabe zeigt weiter, wem sie gehoerte.
        ->and($task->fresh()->responsibleLabel())->toBe('Team Redaktion (gelöscht)');
});

it('ist nur fuer Administratoren erreichbar', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false, 'is_active' => true]));

    $this->get(route('adminv2.system.teams'))->assertForbidden();
});

it('laesst ein Team als Verantwortlichen oder naechsten Bearbeiter einer Aufgabe waehlen', function () {
    Mail::fake();

    $anna = teamUser('Anna');
    $dennis = teamUser('Dennis');
    $carla = teamUser('Carla');
    $eva = teamUser('Eva');

    $redaktion = team('Redaktion', [$dennis, $carla]);
    $support = team('Support', [$eva], ['email' => 'support@example.com', 'notify_mode' => AdminTeam::NOTIFY_TEAM_EMAIL]);

    $this->actingAs($anna);

    Livewire::test(Detail::class)
        ->assertSee('Team Redaktion')
        ->set('title', 'Einreisebestimmungen prüfen')
        ->set('categoryId', (string) AdminTaskCategory::firstOrCreate(['name' => 'Stammdaten'])->id)
        ->set('responsibleId', 'team:999')
        ->call('save')
        ->assertHasErrors('responsibleId')
        ->set('responsibleId', 'team:'.$redaktion->id)
        ->call('save')
        ->assertHasNoErrors();

    $task = AdminTask::firstWhere('title', 'Einreisebestimmungen prüfen');

    expect($task->responsible_id)->toBeNull()
        ->and($task->responsible_team_id)->toBe($redaktion->id)
        ->and($task->responsibleLabel())->toBe('Team Redaktion')
        ->and($task->handlerLabel())->toBe('Team Redaktion')
        ->and($task->currentHandler())->toBeNull();

    // Neue Aufgabe fuers Team: jedes Mitglied bekommt die Mail.
    Mail::assertSent(AdminTaskAssignedMail::class, 2);
    Mail::assertSent(AdminTaskAssignedMail::class, fn ($mail) => $mail->hasTo($dennis->email));
    Mail::assertSent(AdminTaskAssignedMail::class, fn ($mail) => $mail->hasTo($carla->email));

    // Uebergabe an ein Team mit zentraler Adresse: eine Mail dorthin.
    Livewire::test(Detail::class, ['task' => $task->id])
        ->assertSet('responsibleId', 'team:'.$redaktion->id)
        ->set('nextAssigneeId', 'team:'.$support->id)
        ->call('save')
        ->assertHasNoErrors();

    Mail::assertSent(AdminTaskAssignedMail::class, 3);
    Mail::assertSent(AdminTaskAssignedMail::class, fn ($mail) => $mail->hasTo('support@example.com'));
    Mail::assertNotSent(AdminTaskAssignedMail::class, fn ($mail) => $mail->hasTo($eva->email));

    $task->refresh();

    expect($task->handlerLabel())->toBe('Team Support')
        ->and($task->handlerEmails())->toBe(['support@example.com']);

    // Im Verlauf steht das Team mit Namen.
    $change = collect($task->activities()->latest('id')->first()->changes)->firstWhere('field', 'next_assignee_id');

    expect($change['old'])->toBeNull()->and($change['new'])->toBe('Team Support');

    // Vom Team zurueck zu einer Person: ein Eintrag je Rolle.
    $task->update(['responsible_team_id' => null, 'responsible_id' => $anna->id]);
    $changes = collect($task->activities()->latest('id')->first()->changes);

    expect($changes)->toHaveCount(1)
        ->and($changes[0]['old'])->toBe('Team Redaktion')
        ->and($changes[0]['new'])->toBe('Anna');
});

it('zeigt Team-Aufgaben bei jedem Mitglied und filtert nach Team', function () {
    $anna = teamUser('Anna');
    $dennis = teamUser('Dennis');
    $carla = teamUser('Carla');

    $redaktion = team('Redaktion', [$dennis]);
    $support = team('Support', [$carla]);

    teamTask(['title' => 'Verantwortet die Redaktion', 'created_by' => $anna->id, 'responsible_team_id' => $redaktion->id]);
    teamTask(['title' => 'Liegt beim Support', 'created_by' => $anna->id, 'responsible_id' => $anna->id, 'next_assignee_team_id' => $support->id]);
    teamTask(['title' => 'Nur Anna', 'created_by' => $anna->id, 'responsible_id' => $anna->id]);

    $titles = fn ($list) => collect($list->viewData('tasks')->items())->pluck('title')->sort()->values()->all();

    // Dennis: die Aufgabe seines Teams liegt bei ihm und er verantwortet sie mit.
    $this->actingAs($dennis);

    $list = Livewire::test(Index::class)->assertSee('Liegt bei Team Redaktion');

    expect($titles($list))->toBe(['Verantwortet die Redaktion'])
        ->and($titles($list->set('tab', 'responsible')))->toBe(['Verantwortet die Redaktion'])
        ->and(AdminTask::query()->open()->handledBy($dennis->id)->count())->toBe(1);

    // Carla: bei ihr liegt die Aufgabe ueber das Support-Team; verantwortlich bleibt Anna.
    $this->actingAs($carla);

    $list = Livewire::test(Index::class);

    expect($titles($list))->toBe(['Liegt beim Support'])
        ->and($titles($list->set('tab', 'responsible')))->toBe([]);

    // Anna: die Support-Aufgabe liegt nicht mehr bei ihr.
    $this->actingAs($anna);

    $list = Livewire::test(Index::class);

    expect($titles($list))->toBe(['Nur Anna'])
        ->and($titles($list->set('tab', 'all')->set('teams', [(string) $support->id])))->toBe(['Liegt beim Support'])
        ->and($titles($list->set('teams', [])->set('persons', [(string) $dennis->id])->set('personRole', 'handler')))->toBe(['Verantwortet die Redaktion']);

    $list->set('teams', [(string) $redaktion->id])->assertSee('Filter Team Redaktion entfernen');
});

it('benachrichtigt bei Faelligkeit und Erinnerung das Team nach seiner Einstellung', function () {
    Carbon::setTestNow('2026-10-02 08:00:00');
    Mail::fake();

    $anna = teamUser('Anna');
    $dennis = teamUser('Dennis');
    $carla = teamUser('Carla');
    $inactive = User::factory()->create(['name' => 'Weg', 'is_admin' => true, 'is_active' => false]);

    $redaktion = team('Redaktion', [$dennis, $carla, $inactive]);
    $support = team('Support', [$dennis], ['email' => 'support@example.com', 'notify_mode' => AdminTeam::NOTIFY_TEAM_EMAIL]);

    // Faellig heute: liegt bei der Redaktion (jedes Mitglied), verantwortet vom Support (zentrale Adresse).
    $task = teamTask(['responsible_team_id' => $support->id, 'next_assignee_team_id' => $redaktion->id, 'due_date' => '2026-10-02']);
    $task->reminders()->create(['remind_at' => '2026-10-02 07:30:00']);

    $this->artisan('tasks:send-reminders')->assertSuccessful();

    // Erinnerung: an die aktiven Mitglieder der Redaktion.
    Mail::assertSent(AdminTaskReminderMail::class, 2);
    Mail::assertSent(AdminTaskReminderMail::class, fn ($mail) => $mail->hasTo($dennis->email));
    Mail::assertSent(AdminTaskReminderMail::class, fn ($mail) => $mail->hasTo($carla->email));
    Mail::assertNotSent(AdminTaskReminderMail::class, fn ($mail) => $mail->hasTo($inactive->email));

    // Faelligkeit: Redaktion einzeln plus die Team-Adresse des Supports.
    Mail::assertSent(AdminTaskDueMail::class, 3);
    Mail::assertSent(AdminTaskDueMail::class, fn ($mail) => $mail->hasTo('support@example.com'));

    expect((new AdminTaskDueMail($task->fresh()))->render())->toContain('Team Redaktion')->toContain('Team Support');
});

it('legt wiederkehrende Aufgaben auch fuer ein Team an', function () {
    Carbon::setTestNow('2026-10-01 06:00:00');

    $anna = teamUser('Anna');
    $redaktion = team('Redaktion', [teamUser('Dennis')]);

    $this->actingAs($anna);

    Livewire::test(RecurringEditor::class)
        ->set('title', 'Quellen prüfen')
        ->set('categoryId', (string) AdminTaskCategory::firstOrCreate(['name' => 'Global Travel Monitor'])->id)
        ->set('frequency', 'daily')
        ->set('responsibleId', 'team:'.$redaktion->id)
        ->call('save')
        ->assertHasNoErrors();

    $series = AdminTaskRecurrence::first();

    expect($series->responsible_team_id)->toBe($redaktion->id)
        ->and($series->responsible_id)->toBeNull()
        ->and($series->responsibleLabel())->toBe('Team Redaktion');

    Livewire::test(RecurringEditor::class, ['recurrence' => $series->id])->assertSet('responsibleId', 'team:'.$redaktion->id);

    Carbon::setTestNow('2026-10-01 07:02:00');
    $this->artisan('tasks:create-recurring')->assertSuccessful();

    $task = AdminTask::first();

    expect($task->responsible_team_id)->toBe($redaktion->id)
        ->and($task->responsible_id)->toBeNull()
        ->and($task->handlerLabel())->toBe('Team Redaktion');
});
