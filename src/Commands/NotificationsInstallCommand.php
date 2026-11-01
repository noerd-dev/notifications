<?php

declare(strict_types=1);

namespace NoerdNotifications\Commands;

use Illuminate\Console\Command;
use Noerd\Commands\Concerns\InstallsNoerdModule;

/**
 * Notifications is a support module without a tenant app and without anything
 * to publish: installing it offers the migration and registers the agent
 * guideline.
 */
class NotificationsInstallCommand extends Command
{
    use InstallsNoerdModule;

    protected $signature = 'noerd:install-notifications';

    protected $description = 'Install the noerd-notifications module (offers its migration)';

    protected function getModuleName(): string
    {
        return 'Noerd Notifications';
    }
}
