<?php

declare(strict_types=1);

namespace NoerdNotifications\Tests;

use Noerd\Tests\TestCase as NoerdTestCase;
use NoerdNotifications\Providers\NoerdNotificationsServiceProvider;

abstract class TestCase extends NoerdTestCase
{
    /**
     * The parent class provides the full standalone testbench setup: the noerd
     * providers, the noerd guard environment, the sqlite :memory: database
     * (NOERD_TESTBENCH_DB) and the RefreshDatabaseState swap for mixed host/
     * testbench runs. This subclass only adds the notifications package on top —
     * it ships no YAML, so nothing has to be linked into the skeleton.
     */
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            NoerdNotificationsServiceProvider::class,
        ];
    }
}
