<?php

use App\Models\Customer;
use App\Models\PluginClient;
use App\Models\PluginUsageEvent;

it('rejects handshake with invalid key', function () {
    $response = $this->postJson('/api/plugin/handshake', [
        'key' => 'pk_live_invalid_key_that_does_not_exist',
        'domain' => 'example.com',
    ]);

    $response->assertStatus(403);
    $response->assertJson([
        'allowed' => false,
        'error' => 'Invalid or inactive key',
    ]);
});

it('rejects handshake with inactive key', function () {
    $customer = Customer::factory()->create();
    $pluginClient = PluginClient::create([
        'customer_id' => $customer->id,
        'company_name' => 'Test Company',
        'contact_name' => 'Test Contact',
        'email' => $customer->email,
        'status' => 'active',
    ]);
    $key = $pluginClient->generateKey();
    $key->deactivate();
    $pluginClient->addDomain('example.com');

    $response = $this->postJson('/api/plugin/handshake', [
        'key' => $key->public_key,
        'domain' => 'example.com',
    ]);

    $response->assertStatus(403);
    $response->assertJson([
        'allowed' => false,
        'error' => 'Invalid or inactive key',
    ]);
});

it('rejects handshake for inactive client', function () {
    $customer = Customer::factory()->create();
    $pluginClient = PluginClient::create([
        'customer_id' => $customer->id,
        'company_name' => 'Test Company',
        'contact_name' => 'Test Contact',
        'email' => $customer->email,
        'status' => 'inactive',
    ]);
    $key = $pluginClient->generateKey();
    $pluginClient->addDomain('example.com');

    $response = $this->postJson('/api/plugin/handshake', [
        'key' => $key->public_key,
        'domain' => 'example.com',
    ]);

    $response->assertStatus(403);
    $response->assertJson([
        'allowed' => false,
        'error' => 'Client account is not active',
    ]);
});

it('rejects handshake for unauthorized domain', function () {
    $customer = Customer::factory()->create();
    $pluginClient = PluginClient::create([
        'customer_id' => $customer->id,
        'company_name' => 'Test Company',
        'contact_name' => 'Test Contact',
        'email' => $customer->email,
        'status' => 'active',
    ]);
    $key = $pluginClient->generateKey();
    $pluginClient->addDomain('example.com');

    $response = $this->postJson('/api/plugin/handshake', [
        'key' => $key->public_key,
        'domain' => 'unauthorized-domain.com',
    ]);

    $response->assertStatus(403);
    $response->assertJson([
        'allowed' => false,
        'error' => 'Domain not authorized',
    ]);
});

it('allows handshake for valid key and authorized domain', function () {
    $customer = Customer::factory()->create();
    $pluginClient = PluginClient::create([
        'customer_id' => $customer->id,
        'company_name' => 'Test Company',
        'contact_name' => 'Test Contact',
        'email' => $customer->email,
        'status' => 'active',
    ]);
    $key = $pluginClient->generateKey();
    $pluginClient->addDomain('example.com');

    $response = $this->postJson('/api/plugin/handshake', [
        'key' => $key->public_key,
        'domain' => 'example.com',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'allowed' => true,
    ]);
    $response->assertJsonStructure([
        'allowed',
        'config',
    ]);
});

it('logs usage event on successful handshake', function () {
    $customer = Customer::factory()->create();
    $pluginClient = PluginClient::create([
        'customer_id' => $customer->id,
        'company_name' => 'Test Company',
        'contact_name' => 'Test Contact',
        'email' => $customer->email,
        'status' => 'active',
    ]);
    $key = $pluginClient->generateKey();
    $pluginClient->addDomain('example.com');

    $this->postJson('/api/plugin/handshake', [
        'key' => $key->public_key,
        'domain' => 'example.com',
        'path' => '/test-page',
        'event_type' => 'page_load',
    ]);

    $event = PluginUsageEvent::where('plugin_client_id', $pluginClient->id)->first();
    expect($event)->not->toBeNull();
    expect($event->public_key)->toBe($key->public_key);
    expect($event->domain)->toBe('example.com');
    expect($event->path)->toBe('/test-page');
    expect($event->event_type)->toBe('page_load');
});

it('normalizes domain in handshake', function () {
    $customer = Customer::factory()->create();
    $pluginClient = PluginClient::create([
        'customer_id' => $customer->id,
        'company_name' => 'Test Company',
        'contact_name' => 'Test Contact',
        'email' => $customer->email,
        'status' => 'active',
    ]);
    $key = $pluginClient->generateKey();
    $pluginClient->addDomain('example.com');

    // Test with www prefix
    $response = $this->postJson('/api/plugin/handshake', [
        'key' => $key->public_key,
        'domain' => 'www.example.com',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['allowed' => true]);
});

it('accepts custom event types', function () {
    $customer = Customer::factory()->create();
    $pluginClient = PluginClient::create([
        'customer_id' => $customer->id,
        'company_name' => 'Test Company',
        'contact_name' => 'Test Contact',
        'email' => $customer->email,
        'status' => 'active',
    ]);
    $key = $pluginClient->generateKey();
    $pluginClient->addDomain('example.com');

    $response = $this->postJson('/api/plugin/handshake', [
        'key' => $key->public_key,
        'domain' => 'example.com',
        'event_type' => 'custom_click',
        'meta' => ['button' => 'submit'],
    ]);

    $response->assertStatus(200);

    $event = PluginUsageEvent::where('plugin_client_id', $pluginClient->id)->first();
    expect($event->event_type)->toBe('custom_click');
    expect($event->meta)->toBe(['button' => 'submit']);
});

it('validates required fields', function () {
    $response = $this->postJson('/api/plugin/handshake', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['key', 'domain']);
});

it('returns widget.js with correct content type', function () {
    $response = $this->get('/plugin/widget.js');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/javascript; charset=utf-8');
});

it('widget.js contains handshake logic', function () {
    $response = $this->get('/plugin/widget.js');

    $response->assertStatus(200);
    $content = $response->getContent();

    expect($content)->toContain('data-key');
    expect($content)->toContain('/api/plugin/handshake');
    expect($content)->toContain('GTMWidget');
});
