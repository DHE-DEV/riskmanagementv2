<?php

use App\Models\CustomEvent;
use App\Services\CustomEventVersionService;
use App\Services\NotificationRuleService;

/**
 * Revisionssichere Versionierung der Ereignisse:
 * Duplizieren erzeugt eine inaktive Folgeversion, das Aktivieren loest die
 * Vorversion ab, und der Benachrichtigungslauf greift auf die neue Version zu.
 */
function makeEvent(array $attributes = []): CustomEvent
{
    return CustomEvent::create(array_merge([
        'title' => 'Testereignis',
        'popup_content' => 'Erste Fassung',
        'event_type' => 'other',
        'priority' => 'medium',
        'start_date' => now(),
        'is_active' => true,
        'review_status' => 'approved',
    ], $attributes));
}

it('legt fuer ein neues Ereignis Version 1 mit eigener Gruppe an', function () {
    $event = makeEvent();

    expect($event->version)->toBe(1)
        ->and((string) $event->version_group_uuid)->toBe((string) $event->uuid)
        ->and($event->activated_at)->not->toBeNull()
        ->and($event->isCurrentVersion())->toBeTrue();
});

it('erzeugt beim Duplizieren eine inaktive Folgeversion in derselben Gruppe', function () {
    $v1 = makeEvent();

    $v2 = app(CustomEventVersionService::class)->createNewVersion($v1, null, 'Zeitraum korrigiert');

    expect($v2->version)->toBe(2)
        ->and((string) $v2->version_group_uuid)->toBe((string) $v1->version_group_uuid)
        ->and($v2->version_parent_id)->toBe($v1->id)
        ->and($v2->version_note)->toBe('Zeitraum korrigiert')
        ->and($v2->is_active)->toBeFalse()
        ->and($v2->activated_at)->toBeNull()
        ->and((string) $v2->uuid)->not->toBe((string) $v1->uuid);

    // Die Vorversion bleibt bis zur Aktivierung unveraendert aktiv.
    expect($v1->fresh()->is_active)->toBeTrue();
});

it('deaktiviert beim Aktivieren die Vorversion und haelt sie als Historie', function () {
    $v1 = makeEvent();
    $v2 = app(CustomEventVersionService::class)->createNewVersion($v1);

    $v2->activateVersion();

    $v1 = $v1->fresh();
    $v2 = $v2->fresh();

    expect($v2->is_active)->toBeTrue()
        ->and($v2->isCurrentVersion())->toBeTrue()
        ->and($v2->activated_at)->not->toBeNull()
        ->and($v1->is_active)->toBeFalse()
        ->and($v1->superseded_by_id)->toBe($v2->id)
        ->and($v1->superseded_at)->not->toBeNull();

    // Der alte Stand bleibt vollstaendig lesbar.
    expect(CustomEvent::find($v1->id))->not->toBeNull();
    expect($v2->versionHistory()->pluck('version')->all())->toBe([2, 1]);
});

it('blendet abgeloeste Versionen aus den Ausgabe-Scopes aus', function () {
    $v1 = makeEvent();
    $v2 = app(CustomEventVersionService::class)->createNewVersion($v1);
    $v2->activateVersion();

    $activeIds = CustomEvent::active()->pluck('id')->all();

    expect($activeIds)->toContain($v2->id)
        ->and($activeIds)->not->toContain($v1->id);

    expect(CustomEvent::currentVersion()->pluck('id')->all())->toContain($v2->id)
        ->and(CustomEvent::currentVersion()->pluck('id')->all())->not->toContain($v1->id);
});

it('meldet eine spaeter aktivierte Version an den Benachrichtigungslauf', function () {
    $v1 = makeEvent();
    $v2 = app(CustomEventVersionService::class)->createNewVersion($v1);

    // Entwurf wurde tagelang bearbeitet - created_at liegt ausserhalb des
    // Rueckblickfensters, erst die Aktivierung ist frisch.
    CustomEvent::where('id', $v2->id)->update(['created_at' => now()->subDays(5)]);
    $v2->fresh()->activateVersion();

    $service = app(NotificationRuleService::class);
    $method = new ReflectionMethod($service, 'unnotifiedCustomEventsQuery');
    $method->setAccessible(true);

    $ids = $method->invoke($service, now()->subHours(24))->pluck('id')->all();

    expect($ids)->toContain($v2->id)
        ->and($ids)->not->toContain($v1->id);
});

it('loest ueber die UUID einer abgeloesten Version die aktuelle Fassung auf', function () {
    $v1 = makeEvent();
    $v2 = app(CustomEventVersionService::class)->createNewVersion($v1);
    $v2->activateVersion();

    expect($v1->fresh()->resolveCurrentVersion()->id)->toBe($v2->id)
        ->and($v2->fresh()->resolveCurrentVersion()->id)->toBe($v2->id);
});
