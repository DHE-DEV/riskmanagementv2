<?php

use App\Http\Controllers\AdminV2\BoundaryGeoJsonController;
use App\Livewire\AdminV2\Auth\Login;
use App\Livewire\AdminV2\CustomerManagement\ApiClients\Editor as ApiClientEditor;
use App\Livewire\AdminV2\CustomerManagement\ApiClients\Index as ApiClientIndex;
use App\Livewire\AdminV2\CustomerManagement\Customers\Editor as CustomerEditor;
use App\Livewire\AdminV2\CustomerManagement\Customers\Index as CustomerIndex;
use App\Livewire\AdminV2\CustomerManagement\FeaturePreauthorizations\Editor as FeaturePreauthorizationEditor;
use App\Livewire\AdminV2\CustomerManagement\FeaturePreauthorizations\Index as FeaturePreauthorizationIndex;
use App\Livewire\AdminV2\CustomerManagement\PluginClients\Editor as PluginClientEditor;
use App\Livewire\AdminV2\CustomerManagement\PluginClients\Index as PluginClientIndex;
use App\Livewire\AdminV2\CustomerManagement\PluginRegistrations\Index as PluginRegistrationIndex;
use App\Livewire\AdminV2\CustomerManagement\PluginRegistrations\Show as PluginRegistrationShow;
use App\Livewire\AdminV2\CustomerManagement\TravelAlertOrders\Index as TravelAlertOrderIndex;
use App\Livewire\AdminV2\CustomerManagement\TravelAlertOrders\Show as TravelAlertOrderShow;
use App\Livewire\AdminV2\Dashboard;
use App\Livewire\AdminV2\Events\AiResults as EventAiResults;
use App\Livewire\AdminV2\Events\Editor as EventEditor;
use App\Livewire\AdminV2\Events\Index as EventIndex;
use App\Livewire\AdminV2\Events\Overview as EventOverview;
use App\Livewire\AdminV2\Events\RuleCheck as EventRuleCheck;
use App\Livewire\AdminV2\MasterData\Airlines\Editor as AirlineEditor;
use App\Livewire\AdminV2\MasterData\Airlines\Index as AirlineIndex;
use App\Livewire\AdminV2\MasterData\AirportCodes\Editor as AirportCodeEditor;
use App\Livewire\AdminV2\MasterData\AirportCodes\Index as AirportCodeIndex;
use App\Livewire\AdminV2\MasterData\Airports\Editor as AirportEditor;
use App\Livewire\AdminV2\MasterData\Airports\Index as AirportIndex;
use App\Livewire\AdminV2\MasterData\Cities\Editor as CityEditor;
use App\Livewire\AdminV2\MasterData\Cities\Index as CityIndex;
use App\Livewire\AdminV2\MasterData\Continents\Editor as ContinentEditor;
use App\Livewire\AdminV2\MasterData\Continents\Index as ContinentIndex;
use App\Livewire\AdminV2\MasterData\Countries\Editor as CountryEditor;
use App\Livewire\AdminV2\MasterData\Countries\Index as CountryIndex;
use App\Livewire\AdminV2\MasterData\Regions\Editor as RegionEditor;
use App\Livewire\AdminV2\MasterData\Regions\Index as RegionIndex;
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

        // Stammdaten – je Bereich Liste, Anlegen und Bearbeiten.
        Route::prefix('master-data')->name('master-data.')->group(function () {
            Route::get('continents', ContinentIndex::class)->name('continents.index');
            Route::get('continents/create', ContinentEditor::class)->name('continents.create');
            Route::get('continents/{continent}', ContinentEditor::class)->whereNumber('continent')->name('continents.edit');

            Route::get('countries', CountryIndex::class)->name('countries.index');
            Route::get('countries/create', CountryEditor::class)->name('countries.create');
            Route::get('countries/{country}', CountryEditor::class)->whereNumber('country')->name('countries.edit');

            Route::get('regions', RegionIndex::class)->name('regions.index');
            Route::get('regions/create', RegionEditor::class)->name('regions.create');
            Route::get('regions/{region}', RegionEditor::class)->whereNumber('region')->name('regions.edit');

            Route::get('cities', CityIndex::class)->name('cities.index');
            Route::get('cities/create', CityEditor::class)->name('cities.create');
            Route::get('cities/{city}', CityEditor::class)->whereNumber('city')->name('cities.edit');

            Route::get('airports', AirportIndex::class)->name('airports.index');
            Route::get('airports/create', AirportEditor::class)->name('airports.create');
            Route::get('airports/{airport}', AirportEditor::class)->whereNumber('airport')->name('airports.edit');

            Route::get('airport-codes', AirportCodeIndex::class)->name('airport-codes.index');
            Route::get('airport-codes/create', AirportCodeEditor::class)->name('airport-codes.create');
            Route::get('airport-codes/{airportCode}', AirportCodeEditor::class)->whereNumber('airportCode')->name('airport-codes.edit');

            Route::get('airlines', AirlineIndex::class)->name('airlines.index');
            Route::get('airlines/create', AirlineEditor::class)->name('airlines.create');
            Route::get('airlines/{airline}', AirlineEditor::class)->whereNumber('airline')->name('airlines.edit');

            // Laendergrenzen fuer die Karten der Bearbeitungsseiten.
            Route::get('boundaries/continent/{continent}', [BoundaryGeoJsonController::class, 'continent'])->whereNumber('continent')->name('boundaries.continent');
            Route::get('boundaries/country/{country}', [BoundaryGeoJsonController::class, 'country'])->whereNumber('country')->name('boundaries.country');

            // Bereiche, die noch nicht umgezogen sind, zeigen einen Hinweis.
            Route::get('{section}', MasterDataSection::class)
                ->whereIn('section', MasterData::placeholderKeys())
                ->name('section');
        });

        // Kundenverwaltung – je Bereich Liste und Bearbeiten bzw. Ansehen.
        Route::prefix('customer-management')->name('customer-management.')->group(function () {
            Route::get('customers', CustomerIndex::class)->name('customers.index');
            Route::get('customers/create', CustomerEditor::class)->name('customers.create');
            Route::get('customers/{customer}', CustomerEditor::class)->whereNumber('customer')->name('customers.edit');

            Route::get('feature-preauthorizations', FeaturePreauthorizationIndex::class)->name('feature-preauthorizations.index');
            Route::get('feature-preauthorizations/create', FeaturePreauthorizationEditor::class)->name('feature-preauthorizations.create');
            Route::get('feature-preauthorizations/{preauthorization}', FeaturePreauthorizationEditor::class)->whereNumber('preauthorization')->name('feature-preauthorizations.edit');

            Route::get('travel-alert-orders', TravelAlertOrderIndex::class)->name('travel-alert-orders.index');
            Route::get('travel-alert-orders/{order}', TravelAlertOrderShow::class)->whereNumber('order')->name('travel-alert-orders.show');

            Route::get('plugin-clients', PluginClientIndex::class)->name('plugin-clients.index');
            Route::get('plugin-clients/create', PluginClientEditor::class)->name('plugin-clients.create');
            Route::get('plugin-clients/{pluginClient}', PluginClientEditor::class)->whereNumber('pluginClient')->name('plugin-clients.edit');

            Route::get('plugin-registrations', PluginRegistrationIndex::class)->name('plugin-registrations.index');
            Route::get('plugin-registrations/{registration}', PluginRegistrationShow::class)->whereNumber('registration')->name('plugin-registrations.show');

            Route::get('api-clients', ApiClientIndex::class)->name('api-clients.index');
            Route::get('api-clients/create', ApiClientEditor::class)->name('api-clients.create');
            Route::get('api-clients/{apiClient}', ApiClientEditor::class)->whereNumber('apiClient')->name('api-clients.edit');
        });

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
