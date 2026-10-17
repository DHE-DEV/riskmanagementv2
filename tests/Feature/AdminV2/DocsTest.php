<?php

use App\Models\User;
use App\Support\AdminV2\Docs;

/**
 * Doku in Admin v2: Uebersicht und Anleitungen aus docs/admin/*.md, nur fuer Admins.
 */
it('zeigt die Doku nur Admins', function () {
    $this->get('/adminv2/docs')->assertRedirect();

    $this->actingAs(User::factory()->create(['is_admin' => false, 'is_active' => true]))
        ->get('/adminv2/docs')->assertForbidden();
});

it('listet die Anleitungen und zeigt die Push-Anleitung mit Inhaltsverzeichnis', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true]));

    $this->get('/adminv2/docs')
        ->assertOk()
        ->assertSee('Push-Benachrichtigungen')
        ->assertSee(route('adminv2.docs.show', 'push-notifications'), false);

    $this->get('/adminv2/docs/push-notifications')
        ->assertOk()
        ->assertSee('Push-Benachrichtigungen')
        ->assertSee('id="skalierung"', false)
        ->assertSee('href="#einrichtung"', false)
        ->assertSee('/api/v1/devices');

    $this->get('/adminv2/docs/gibt-es-nicht')->assertNotFound();
});

it('hat fuer jede Anleitung eine Markdown-Datei', function () {
    foreach (Docs::DOCS as $key => $doc) {
        expect(base_path('docs/admin/'.$doc['file']))->toBeFile()
            ->and(app(Docs::class)->renderDoc($key)['toc'])->not->toBeEmpty();
    }
});
