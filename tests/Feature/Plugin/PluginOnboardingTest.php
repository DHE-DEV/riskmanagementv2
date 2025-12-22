<?php

use App\Models\Customer;
use App\Models\PluginClient;
use App\Models\PluginKey;
use App\Models\PluginDomain;
use App\Mail\PluginKeyMail;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get(route('plugin.onboarding'));

    $response->assertRedirect();
});

it('shows onboarding form for authenticated customer without plugin client', function () {
    $customer = Customer::factory()->create();

    $response = $this->actingAs($customer, 'customer')
        ->get(route('plugin.onboarding'));

    $response->assertStatus(200);
    $response->assertViewIs('plugin.onboarding');
});

it('redirects to dashboard if customer already has plugin client', function () {
    $customer = Customer::factory()->create();
    $pluginClient = PluginClient::create([
        'customer_id' => $customer->id,
        'company_name' => 'Test Company',
        'contact_name' => 'Test Contact',
        'email' => $customer->email,
        'status' => 'active',
    ]);
    $pluginClient->generateKey();

    $response = $this->actingAs($customer, 'customer')
        ->get(route('plugin.onboarding'));

    $response->assertRedirect(route('plugin.dashboard'));
});

it('validates required fields on onboarding submit', function () {
    $customer = Customer::factory()->create();

    $response = $this->actingAs($customer, 'customer')
        ->post(route('plugin.onboarding.store'), []);

    $response->assertSessionHasErrors(['company_name', 'contact_name', 'domain']);
});

it('validates domain format', function () {
    $customer = Customer::factory()->create();

    $response = $this->actingAs($customer, 'customer')
        ->post(route('plugin.onboarding.store'), [
            'company_name' => 'Test Company',
            'contact_name' => 'Test Contact',
            'domain' => 'invalid-domain',
        ]);

    $response->assertSessionHasErrors(['domain']);
});

it('creates plugin client with key and domain on successful onboarding', function () {
    $customer = Customer::factory()->create();

    $response = $this->actingAs($customer, 'customer')
        ->post(route('plugin.onboarding.store'), [
            'company_name' => 'Test Company GmbH',
            'contact_name' => 'Max Mustermann',
            'domain' => 'example.com',
        ]);

    $response->assertRedirect(route('plugin.dashboard'));
    $response->assertSessionHas('success');

    // Check plugin client was created
    $pluginClient = PluginClient::where('customer_id', $customer->id)->first();
    expect($pluginClient)->not->toBeNull();
    expect($pluginClient->company_name)->toBe('Test Company GmbH');
    expect($pluginClient->contact_name)->toBe('Max Mustermann');
    expect($pluginClient->email)->toBe($customer->email);
    expect($pluginClient->status)->toBe('active');

    // Check key was generated
    $key = $pluginClient->activeKey;
    expect($key)->not->toBeNull();
    expect($key->public_key)->toStartWith('pk_live_');
    expect(strlen($key->public_key))->toBeGreaterThanOrEqual(40);
    expect($key->is_active)->toBeTrue();

    // Check domain was added
    $domain = $pluginClient->domains()->first();
    expect($domain)->not->toBeNull();
    expect($domain->domain)->toBe('example.com');
});

it('normalizes domain input', function () {
    $customer = Customer::factory()->create();

    $response = $this->actingAs($customer, 'customer')
        ->post(route('plugin.onboarding.store'), [
            'company_name' => 'Test Company',
            'contact_name' => 'Test Contact',
            'domain' => 'https://www.example.com/path',
        ]);

    $response->assertRedirect(route('plugin.dashboard'));

    $pluginClient = PluginClient::where('customer_id', $customer->id)->first();
    $domain = $pluginClient->domains()->first();
    expect($domain->domain)->toBe('example.com');
});

it('sends email with plugin key after onboarding', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($customer, 'customer')
        ->post(route('plugin.onboarding.store'), [
            'company_name' => 'Test Company',
            'contact_name' => 'Test Contact',
            'domain' => 'example.com',
        ]);

    Mail::assertSent(PluginKeyMail::class, function ($mail) use ($customer) {
        return $mail->hasTo($customer->email);
    });
});

it('shows dashboard with plugin client data', function () {
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

    $response = $this->actingAs($customer, 'customer')
        ->get(route('plugin.dashboard'));

    $response->assertStatus(200);
    $response->assertViewIs('plugin.dashboard');
    $response->assertViewHas('pluginClient');
    $response->assertViewHas('activeKey');
    $response->assertViewHas('domains');
    $response->assertSee($key->public_key);
    $response->assertSee('example.com');
});

it('can add a new domain', function () {
    $customer = Customer::factory()->create();
    $pluginClient = PluginClient::create([
        'customer_id' => $customer->id,
        'company_name' => 'Test Company',
        'contact_name' => 'Test Contact',
        'email' => $customer->email,
        'status' => 'active',
    ]);
    $pluginClient->generateKey();
    $pluginClient->addDomain('example.com');

    $response = $this->actingAs($customer, 'customer')
        ->post(route('plugin.add-domain'), [
            'domain' => 'another-example.com',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($pluginClient->domains()->count())->toBe(2);
    expect($pluginClient->hasDomain('another-example.com'))->toBeTrue();
});

it('prevents adding duplicate domain', function () {
    $customer = Customer::factory()->create();
    $pluginClient = PluginClient::create([
        'customer_id' => $customer->id,
        'company_name' => 'Test Company',
        'contact_name' => 'Test Contact',
        'email' => $customer->email,
        'status' => 'active',
    ]);
    $pluginClient->generateKey();
    $pluginClient->addDomain('example.com');

    $response = $this->actingAs($customer, 'customer')
        ->post(route('plugin.add-domain'), [
            'domain' => 'example.com',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');

    expect($pluginClient->domains()->count())->toBe(1);
});

it('can regenerate API key', function () {
    $customer = Customer::factory()->create();
    $pluginClient = PluginClient::create([
        'customer_id' => $customer->id,
        'company_name' => 'Test Company',
        'contact_name' => 'Test Contact',
        'email' => $customer->email,
        'status' => 'active',
    ]);
    $oldKey = $pluginClient->generateKey();
    $oldKeyValue = $oldKey->public_key;

    $response = $this->actingAs($customer, 'customer')
        ->post(route('plugin.regenerate-key'));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // Old key should be deactivated
    $oldKey->refresh();
    expect($oldKey->is_active)->toBeFalse();

    // New key should be active
    $newKey = $pluginClient->fresh()->activeKey;
    expect($newKey)->not->toBeNull();
    expect($newKey->public_key)->not->toBe($oldKeyValue);
    expect($newKey->is_active)->toBeTrue();
});

it('prevents removing last domain', function () {
    $customer = Customer::factory()->create();
    $pluginClient = PluginClient::create([
        'customer_id' => $customer->id,
        'company_name' => 'Test Company',
        'contact_name' => 'Test Contact',
        'email' => $customer->email,
        'status' => 'active',
    ]);
    $pluginClient->generateKey();
    $domain = $pluginClient->addDomain('example.com');

    $response = $this->actingAs($customer, 'customer')
        ->delete(route('plugin.remove-domain', $domain->id));

    $response->assertRedirect();
    $response->assertSessionHas('error');

    expect($pluginClient->domains()->count())->toBe(1);
});

it('can remove domain when multiple exist', function () {
    $customer = Customer::factory()->create();
    $pluginClient = PluginClient::create([
        'customer_id' => $customer->id,
        'company_name' => 'Test Company',
        'contact_name' => 'Test Contact',
        'email' => $customer->email,
        'status' => 'active',
    ]);
    $pluginClient->generateKey();
    $domain1 = $pluginClient->addDomain('example.com');
    $domain2 = $pluginClient->addDomain('example2.com');

    $response = $this->actingAs($customer, 'customer')
        ->delete(route('plugin.remove-domain', $domain1->id));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($pluginClient->domains()->count())->toBe(1);
    expect($pluginClient->hasDomain('example2.com'))->toBeTrue();
    expect($pluginClient->hasDomain('example.com'))->toBeFalse();
});
