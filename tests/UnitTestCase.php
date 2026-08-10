<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Boots the application without RefreshDatabase.
 *
 * Tests\TestCase migrates on every test, so tests that only inspect generated
 * SQL, routes or container wiring cannot run without a reachable database.
 * Extend this instead when the test genuinely touches no data.
 */
abstract class UnitTestCase extends BaseTestCase
{
    //
}
