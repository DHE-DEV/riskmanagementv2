<?php

use App\Models\Customer;
use App\Support\DeployInfo;
use Illuminate\Support\Facades\Cache;

it('gibt den Stand des Servers fuer die App-Info aus – nur mit Zugang', function () {
    $this->getJson('/api/v1/app-info')->assertUnauthorized();

    $token = Customer::factory()->create(['gtm_api_enabled' => true])->createToken('Test', ['gtm:read'])->plainTextToken;
    $git = DeployInfo::read();

    $this->withToken($token)->getJson('/api/v1/app-info')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.server.commit', $git['commit'])
        ->assertJsonPath('data.server.branch', $git['branch'])
        ->assertJsonStructure(['data' => ['server' => ['branch', 'commit', 'commit_short', 'deployed_at', 'environment']]]);
});

it('nimmt Commit, Branch und Deploy-Zeit aus der Konfiguration, wenn das Deploy-Skript sie setzt', function () {
    config(['app.deploy' => ['branch' => 'main', 'commit' => 'ABCDEF1234567890', 'deployed_at' => '2026-10-13 16:05:00']]);
    Cache::flush();

    expect(DeployInfo::get())->toMatchArray([
        'branch' => 'main',
        'commit' => 'abcdef1234567890',
        'commit_short' => 'abcdef12',
    ])->and(DeployInfo::get()['deployed_at'])->toStartWith('2026-10-13T16:05:00');

    config(['app.deploy.commit' => 'kein-commit']);
    expect(DeployInfo::read()['commit'])->toBeNull();
});
