<?php

use App\Livewire\AdminV2\Events\Index;
use App\Models\CustomEvent;
use App\Models\User;
use Livewire\Livewire;

/**
 * Admin v2: Übersetzungsstand der Ereignisse – Hinweis "veraltet" im Editor,
 * Filter und Markierung in der Liste.
 */
function translationAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true]);
}

function trackedEvent(string $germanTitle, array $otherTitles = ['en' => 'Strike', 'nl' => 'Staking']): CustomEvent
{
    config(['app.event_auto_translate' => false]);

    $event = CustomEvent::create([
        'title_translations' => ['de' => $germanTitle] + $otherTitles,
        'popup_content_translations' => ['de' => '<p>Text</p>'] + array_map(fn () => '<p>Tekst</p>', $otherTitles),
        'event_type' => 'other',
        'priority' => 'high',
        'start_date' => now()->subDay(),
        'end_date' => now()->addWeek(),
        'is_active' => true,
        'review_status' => 'approved',
    ]);
    $event->markTranslated(array_keys($otherTitles));
    $event->save();

    return $event;
}

it('zeigt im Editor einen Hinweis, wenn der deutsche Text nach der Übersetzung geändert wurde', function () {
    $event = trackedEvent('Streik');
    $event->update(['title_translations' => array_merge($event->title_translations, ['de' => 'Streik verlängert'])]);

    $response = $this->actingAs(translationAdmin())->get(route('adminv2.events.edit', $event));

    $response->assertOk()
        ->assertSee('Übersetzung veraltet')
        ->assertSee('Freigegebene Ereignisse werden automatisch neu übersetzt');
});

it('filtert die Liste nach Ereignissen mit veralteter oder fehlender Übersetzung', function () {
    $stale = trackedEvent('Streik A');
    $stale->update(['title_translations' => array_merge($stale->title_translations, ['de' => 'Streik A, verlängert'])]);
    $missing = trackedEvent('Streik B', []);
    $complete = trackedEvent('Streik C');

    $component = Livewire::actingAs(translationAdmin())->test(Index::class)->set('translation', 'stale');

    $component->assertSee('Streik A, verlängert')
        ->assertSee('Übersetzung veraltet')
        ->assertDontSee('Streik B')
        ->assertDontSee('Streik C');
    expect([$missing->refresh()->translation_status, $complete->refresh()->translation_status])->toBe(['missing', 'complete']);
});
