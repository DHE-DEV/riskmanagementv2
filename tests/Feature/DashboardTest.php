<?php

declare(strict_types=1);

use App\Models\{User, CustomEvent, DisasterEvent};

test('dashboard is accessible', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Risk Management System');
});

test('dashboard shows map container', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('id="map"');
});

test('dashboard shows legend', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Legende');
});

test('dashboard shows statistics section', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Statistiken');
});

test('dashboard shows weather information section', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Wetter');
});

test('dashboard shows timezone information section', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Zeitzone');
});

test('dashboard shows sidebar functionality', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Erweiterte Informationen');
});

test('dashboard shows collapsible legend', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('fa-chevron-up');
});

test('dashboard has proper layout structure', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('header')
        ->assertSee('footer')
        ->assertSee('main-content');
});
