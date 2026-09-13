<?php

declare(strict_types=1);

uses(NoerdNotifications\Tests\TestCase::class);

it('declares every module dependency it uses', function (): void {
    assertModuleDependenciesDeclared(dirname(__DIR__, 2));
});
