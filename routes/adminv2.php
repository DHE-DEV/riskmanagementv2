<?php

use App\Livewire\AdminV2\Auth\Login;
use App\Livewire\AdminV2\Dashboard;
use App\Livewire\AdminV2\Events\AiResults as EventAiResults;
use App\Livewire\AdminV2\Events\Editor as EventEditor;
use App\Livewire\AdminV2\Events\Index as EventIndex;
use App\Livewire\AdminV2\Events\Overview as EventOverview;
use App\Livewire\AdminV2\Events\RuleCheck as EventRuleCheck;
use App\Livewire\AdminV2\MasterData\Section as MasterDataSection;
use App\Livewire\AdminV2\Rules\Show as RuleShow;
use App\Livewire\AdminV2\System\Ai as SystemAi;
use App\Livewire\AdminV2\System\AiSearchEditor as SystemAiSearchEditor;
use App\Livewire\AdminV2\System\RecurringTasks\Editor as RecurringTaskEditor;
use App\Livewire\AdminV2\System\RecurringTasks\Index as RecurringTaskIndex;
use App\Livewire\AdminV2\System\Teams as SystemTeams;
use App\Livewire\AdminV2\Tasks\Detail as TaskDetail;
use App\Livewire\AdminV2\Tasks\Index as TaskIndex;
use App\Support\AdminV2\MasterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin v2
|--------------------------------------------------------------------------
|
| Neuer Admin-Bereich ohne Filament (Livewire + Flux). Er soll /admin nach
| und nach abloesen und nutzt dieselbe Anmeldung (web-Guard, is_admin).
|
*/

Route::prefix('adminv2')->name('adminv2.')->group(function () {
    Route::get('login', Login::class)->name('login');

    Route::middleware('adminv2')->group(function () {
        Route::get('/', Dashboard::class)->name('dashboard');

        Route::get('events/overview', EventOverview::class)->name('events.overview');
        Route::get('events', EventIndex::class)->name('events.index');
        Route::get('events/ai-results', EventAiResults::class)->name('events.ai-results');
        Route::get('events/create', EventEditor::class)->name('events.create');
        Route::get('events/{event}', EventEditor::class)->whereNumber('event')->name('events.edit');
        Route::get('events/{event}/rules', EventRuleCheck::class)->whereNumber('event')->name('events.rules');

        // Benachrichtigungsregel eines Kunden – nur zum Nachsehen.
        Route::get('notification-rules/{rule}', RuleShow::class)->whereNumber('rule')->name('rules.show');

        Route::get('tasks', TaskIndex::class)->name('tasks.index');
        Route::get('tasks/create', TaskDetail::class)->name('tasks.create');
        Route::get('tasks/{task}', TaskDetail::class)->whereNumber('task')->name('tasks.show');

        Route::get('master-data/{section}', MasterDataSection::class)
            ->whereIn('section', array_keys(MasterData::sections()))
            ->name('master-data.section');

        Route::get('system/ai', SystemAi::class)->name('system.ai');
        Route::get('system/ai/searches/create', SystemAiSearchEditor::class)->name('system.ai.searches.create');
        Route::get('system/ai/searches/{profile}', SystemAiSearchEditor::class)->whereNumber('profile')->name('system.ai.searches.edit');
        Route::get('system/teams', SystemTeams::class)->name('system.teams');
        Route::get('system/recurring-tasks', RecurringTaskIndex::class)->name('system.recurring-tasks.index');
        Route::get('system/recurring-tasks/create', RecurringTaskEditor::class)->name('system.recurring-tasks.create');
        Route::get('system/recurring-tasks/{recurrence}', RecurringTaskEditor::class)->whereNumber('recurrence')->name('system.recurring-tasks.edit');

        Route::post('logout', function (Request $request) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('adminv2.login');
        })->name('logout');
    });
});
