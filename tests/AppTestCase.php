<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Bootet die Laravel-App (aus bootstrap/app.php) OHNE RefreshDatabase.
 *
 * Fuer Tests, die nur das HTTP-/View-Verhalten pruefen und keine Datenbank
 * brauchen – laeuft damit auch ohne erreichbaren MySQL-Container.
 */
abstract class AppTestCase extends BaseTestCase {}
