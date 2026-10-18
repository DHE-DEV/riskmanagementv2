<?php

use App\Models\Customer;
use Illuminate\Support\Facades\Http;

/**
 * GET /v1/entry-requirements: Bestimmungen je Reiseland fuer eine Nationalitaet (aus der PDS-API).
 */
function entryToken(): string
{
    return Customer::factory()->create(['gtm_api_enabled' => true])->createToken('App', ['gtm:read'])->plainTextToken;
}

beforeEach(function () {
    config(['services.passolution.api_url' => 'https://pds.example/api/v2', 'services.passolution.api_key' => 'test']);
});

it('liefert die Bestimmungen je Land in der Form der Reisenden-Seite', function () {
    Http::fake(['pds.example/*' => Http::response(['records' => [
        ['destination' => 'CL', 'title' => 'Reiseziel Chile / Nationalität Österreich', 'nationality' => 'AT', 'destination_flag' => 'data:image/png;base64,xx',
            'visa' => ['title' => 'Visabestimmungen', 'content' => '<p>Kein Visum</p>']],
    ]])]);

    $this->withToken(entryToken())->getJson('/api/v1/entry-requirements?countries=cl,PE,xyz&nationality=at')
        ->assertOk()
        ->assertJsonPath('data.nationality', 'AT')
        ->assertJsonPath('data.travel.CL.records.0.title', 'Reiseziel Chile / Nationalität Österreich')
        ->assertJsonPath('data.travel.CL.records.0.visa.content', '<p>Kein Visum</p>')
        ->assertJsonMissingPath('data.travel.CL.records.0.destination_flag');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/content/all/html') && $request['nat'] === 'AT' && $request['countries'] === 'CL,PE');

    // Zweiter Abruf aus dem Zwischenspeicher
    $this->withToken(entryToken())->getJson('/api/v1/entry-requirements?countries=PE,CL&nationality=AT')->assertOk();
    Http::assertSentCount(1);
});

it('prueft die Eingaben und meldet eine nicht erreichbare Quelle', function () {
    Http::fake(['pds.example/*' => Http::response(['error' => 'x'], 500)]);
    $token = entryToken();

    $this->getJson('/api/v1/entry-requirements?countries=CL&nationality=AT')->assertUnauthorized();
    $this->withToken($token)->getJson('/api/v1/entry-requirements?countries=CL')->assertUnprocessable();
    $this->withToken($token)->getJson('/api/v1/entry-requirements?countries=CL&nationality=DE')->assertStatus(502);
});
