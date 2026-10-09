<?php

use App\Models\CustomEvent;
use App\Services\ApiDocRenderer;
use App\Services\GtmEventService;
use Illuminate\Support\Facades\Cache;

it('zeigt die Doku-Übersicht und jede Anleitung aus den Markdown-Dateien', function () {
    $this->get('/docs/api')
        ->assertOk()
        ->assertSee('16 Endpoints')
        ->assertSee('https://platform.passolution.de/api')
        ->assertSee('https://platform.passolution.de/feed')
        ->assertSee('https://api.global-travel-monitor.de/v1')
        ->assertSee('/docs/gtm-api-openapi.yaml')
        ->assertSee('/api/v1/documentation');

    foreach (ApiDocRenderer::GUIDES as $key => $guide) {
        $this->get("/docs/api/{$key}")->assertOk()->assertSee($guide['title']);
    }

    $this->get('/docs/api/unbekannt')->assertNotFound();
});

it('nennt in der Events-Anleitung die echten Kategorie-Codes und den Nearby-Endpoint', function () {
    $this->get('/docs/api/gtm')
        ->assertOk()
        ->assertSee('safety')
        ->assertSee('strike')
        ->assertSee('/v1/events/nearby')
        ->assertSee('is_nationwide')
        ->assertDontSee('event_category=security');
});

it('bietet auf den Anleitungsseiten einen Testbereich und Testen-Knöpfe an den Beispielen', function () {
    $this->get('/docs/api/gtm')
        ->assertOk()
        ->assertSee('id="test-panel"', false)
        ->assertSee('class="try-btn"', false)
        ->assertSee('data-request="GET /v1/events"', false)
        ->assertSee('Testen 2');
});

it('liefert die API-Dokumentation auch auf der Plattform unter /api/v1/documentation', function () {
    $this->get('/api/v1/documentation')
        ->assertOk()
        ->assertSee('Events API')
        ->assertSee('/api/v1/documentation/gtm-api-openapi.yaml')
        ->assertSee('id="events-api-guide"', false)
        ->assertSee('id="plugin-domain-api-guide"', false);

    $this->get('/api/v1')->assertOk()->assertJsonPath('version', 'v1')->assertJsonPath('endpoints.Events (alle)', '/v1/events');

    $response = $this->get('/api/v1/documentation/gtm-api-guide.md')->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/markdown');

    $this->get('/api/v1/documentation/geheim.md')->assertNotFound();
});

it('liefert Startseite, Übersicht und Downloads auf der API-Subdomain', function () {
    $host = 'http://'.config('app.api_domain');

    $this->get($host.'/')->assertOk()->assertSee('/docs/gtm-api-openapi.yaml')->assertSee('safety');
    $this->get($host.'/v1')->assertOk()->assertJsonPath('version', 'v1');
    $this->get($host.'/docs/gtm-api-openapi.yaml')->assertOk();
});

it('zählt Events ohne data_source beim Filter source=manual als manuell', function () {
    Cache::flush();
    $base = ['is_active' => true, 'archived' => false, 'review_status' => 'approved', 'customer_id' => null, 'end_date' => null];

    CustomEvent::factory()->create($base + ['data_source' => null]);
    CustomEvent::factory()->create($base + ['data_source' => 'manual']);
    CustomEvent::factory()->create($base + ['data_source' => 'passolution_infosystem']);

    $service = app(GtmEventService::class);

    expect($service->getActiveEvents(source: 'manual'))->toHaveCount(2)
        ->and($service->getActiveEvents(source: 'passolution_infosystem'))->toHaveCount(1)
        ->and($service->getActiveEvents())->toHaveCount(3);
});

it('leitet Alias-Hostnamen der API dauerhaft auf die API-Domain um', function () {
    config(['app.api_domain' => 'api.global-travel-monitor.de', 'app.api_domain_aliases' => ['api.global-travel-monitor.eu']]);

    $this->get('http://api.global-travel-monitor.eu/v1/events?per_page=5')
        ->assertStatus(301)
        ->assertRedirect('https://api.global-travel-monitor.de/v1/events?per_page=5');

    $this->get('http://api.global-travel-monitor.de/v1')->assertOk();
});
