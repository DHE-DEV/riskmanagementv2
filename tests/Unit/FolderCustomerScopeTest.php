<?php

use App\Models\Customer;
use App\Models\Folder\Folder;
use App\Models\Folder\FolderImportLog;
use App\Models\Folder\FolderTimelineLocation;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * The customer guard uses the session driver, so it never applies to API requests
 * authenticated by a Sanctum token. Without the token fallback in BaseCustomerModel
 * the global scope silently drops out and every customer's folders are returned.
 *
 * These tests only inspect generated SQL and route resolution, so they need no DB.
 */
uses(Tests\UnitTestCase::class);

function customerScopedModels(): array
{
    return [Folder::class, FolderTimelineLocation::class, FolderImportLog::class];
}

test('a customer API token scopes folder queries to that customer', function () {
    $customer = new Customer;
    $customer->id = 42;
    auth('sanctum')->setUser($customer);

    foreach (customerScopedModels() as $class) {
        $query = $class::query();

        expect($query->toSql())->toContain('`customer_id` = ?');
        expect($query->getBindings())->toContain(42);
    }
});

test('a non-customer token does not silently widen folder queries', function () {
    $admin = new User;
    $admin->id = 7;
    auth('sanctum')->setUser($admin);

    // The scope cannot narrow to a customer here, which is exactly why the folder
    // routes reject such tokens in EnsureCustomerApiToken before any query runs.
    foreach (customerScopedModels() as $class) {
        expect($class::query()->toSql())->not->toContain('customer_id');
    }
});

test('unauthenticated contexts such as queued jobs stay unscoped', function () {
    foreach (customerScopedModels() as $class) {
        expect($class::query()->toSql())->not->toContain('customer_id');
    }
});

test('folder API routes are not shadowed by the wildcard route', function () {
    $expected = [
        'api/v1/folders' => 'customer.folders.index',
        'api/v1/folders/map-locations' => 'customer.folders.map-locations',
        'api/v1/folders/statistics' => 'customer.folders.statistics',
        'api/v1/folders/imports' => 'customer.folders.imports.list',
        'api/v1/folders/imports/abc/status' => 'customer.folders.imports.status',
        'api/v1/folders/019bef38-f2bc-73fc-bdbc-228ff5a8421e' => 'customer.folders.show',
    ];

    foreach ($expected as $uri => $name) {
        $route = app('router')->getRoutes()->match(Request::create($uri, 'GET'));

        expect($route->getName())->toBe($name);
    }
});

test('every folder API route requires a customer token', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1/folders'));

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        expect($route->gatherMiddleware())->toContain(App\Http\Middleware\EnsureCustomerApiToken::class);
    }
});
