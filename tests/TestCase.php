<?php

declare(strict_types=1);

namespace NoerdNotifications\Tests;

use Noerd\Tests\ModuleTestCase;
use NoerdNotifications\Providers\NoerdNotificationsServiceProvider;

/**
 * The standalone testbench setup of the core (providers, noerd guard, sqlite
 * :memory:, RefreshDatabaseState swap) plus the notifications package.
 */
abstract class TestCase extends ModuleTestCase
{
    protected array $moduleProviders = [NoerdNotificationsServiceProvider::class];
}
