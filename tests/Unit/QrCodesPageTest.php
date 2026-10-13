<?php

use App\Livewire\AdminV2\System\QrCodes;
use App\Models\User;
use Livewire\Livewire;

// DB-frei: Admin wird nur im Speicher angelegt; geprueft werden Vorschau und PDF-Download.
uses(Tests\AppTestCase::class);

beforeEach(function () {
    $admin = new User(['name' => 'Admin', 'email' => 'admin@example.test']);
    $admin->forceFill(['id' => 1, 'is_admin' => true, 'is_active' => true]);
    $this->actingAs($admin, 'web');
});

it('shows the qr code for a travel details id and offers the pdf', function () {
    Livewire::test(QrCodes::class)
        ->set('value', 'zipthrg1z6y5')
        ->assertSee('https://travel-details.eu/de?tid=ZIPTHRG1Z6Y5')
        ->assertSee('ZIPT-HRG1-Z6Y5')
        ->assertSeeHtml('shape-rendering="crispEdges"')
        ->call('downloadPdf')
        ->assertFileDownloaded('qr-ZIPT-HRG1-Z6Y5.pdf');
});

it('warns about an invalid travel details id and does not download', function () {
    Livewire::test(QrCodes::class)
        ->set('value', 'abc')
        ->assertSee('Keine gültige Reise-ID')
        ->assertDontSeeHtml('shape-rendering="crispEdges"')
        ->call('downloadPdf')
        ->assertNoFileDownloaded();
});

it('encodes free content unchanged', function () {
    Livewire::test(QrCodes::class)
        ->set('type', QrCodes::TYPE_FREE)
        ->set('value', 'https://www.passolution.de')
        ->assertSee('https://www.passolution.de')
        ->assertSeeHtml('shape-rendering="crispEdges"');
});

it('rejects non admins', function () {
    $user = new User(['name' => 'Kunde', 'email' => 'kunde@example.test']);
    $user->forceFill(['id' => 2, 'is_admin' => false, 'is_active' => true]);
    $this->actingAs($user, 'web');

    Livewire::test(QrCodes::class)->assertForbidden();
});
