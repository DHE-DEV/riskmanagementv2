<?php

use App\Livewire\AdminV2\System\Templates\Editor;
use App\Models\Customer;
use App\Models\NotificationTemplate;
use App\Models\User;
use Livewire\Livewire;

/**
 * System > Vorlagen: die Standard-Vorlagen der Benachrichtigungs-E-Mails als
 * Karten, mit Bearbeiten von Name, Betreff und Inhalt.
 */
function templatesAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true]);
}

function systemTemplate(string $source, string $name): NotificationTemplate
{
    return NotificationTemplate::query()->updateOrCreate(
        ['is_system' => true, 'source' => $source],
        ['name' => $name, 'subject' => 'Reisewarnung: {event_title}', 'body_html' => '<h2>{event_title}</h2><p>{description}</p>'],
    );
}

it('zeigt die Standard-Vorlagen als Karten und verlinkt sie in der Navigation unter System', function () {
    $gtm = systemTemplate('global-travel-monitor', 'Standard-Vorlage');
    $travelAlert = systemTemplate('travel-alert', 'Standard-Vorlage (Travel Alert)');

    // Eigene Vorlagen der Kunden gehoeren nicht hierher.
    NotificationTemplate::create([
        'customer_id' => Customer::factory()->create()->id,
        'source' => 'travel-alert',
        'name' => 'Kundenvorlage Müller',
        'subject' => 'Eigener Betreff',
        'body_html' => '<p>Eigener Inhalt</p>',
        'is_system' => false,
    ]);

    $this->actingAs(templatesAdmin())
        ->get(route('adminv2.system.templates.index'))
        ->assertOk()
        ->assertSee('Vorlagen')
        ->assertSee('Standard-Vorlage (Travel Alert)')
        ->assertSee('Global Travel Monitor')
        ->assertSee('Travel Alert')
        ->assertSee('Reisewarnung: {event_title}')
        ->assertDontSee('Kundenvorlage Müller')
        ->assertSee(route('adminv2.system.templates.edit', $gtm))
        ->assertSee(route('adminv2.system.templates.edit', $travelAlert));
});

it('speichert Name, Betreff und Inhalt einer Vorlage – die Quelle bleibt', function () {
    $template = systemTemplate('travel-alert', 'Standard-Vorlage (Travel Alert)');

    $this->actingAs(templatesAdmin());

    Livewire::test(Editor::class, ['template' => $template->id])
        ->assertSet('name', 'Standard-Vorlage (Travel Alert)')
        ->assertSet('subject', 'Reisewarnung: {event_title}')
        ->assertSee('{affected_trips}')
        ->set('name', '')
        ->set('subject', '')
        ->set('bodyHtml', '')
        ->call('save')
        ->assertHasErrors(['name', 'subject', 'bodyHtml'])
        ->set('name', 'Travel Alert – Standard')
        ->set('subject', 'Neue Ereignisse: {event_title} ({country_name})')
        ->set('bodyHtml', '<p>{update_hint}</p><p>{description}</p>{affected_trips}')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('adminv2.system.templates.index'));

    $template->refresh();

    expect($template->name)->toBe('Travel Alert – Standard')
        ->and($template->subject)->toBe('Neue Ereignisse: {event_title} ({country_name})')
        ->and($template->body_html)->toBe('<p>{update_hint}</p><p>{description}</p>{affected_trips}')
        ->and($template->source)->toBe('travel-alert')
        ->and($template->is_system)->toBeTrue();
});

it('laesst eigene Vorlagen der Kunden nicht ueber den System-Editor bearbeiten', function () {
    $customerTemplate = NotificationTemplate::create([
        'customer_id' => Customer::factory()->create()->id,
        'source' => 'travel-alert',
        'name' => 'Kundenvorlage',
        'subject' => 'Betreff',
        'body_html' => '<p>Inhalt</p>',
        'is_system' => false,
    ]);

    $this->actingAs(templatesAdmin())
        ->get(route('adminv2.system.templates.edit', $customerTemplate))
        ->assertNotFound();
});
