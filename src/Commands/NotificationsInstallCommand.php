<?php

declare(strict_types=1);

namespace NoerdNotifications\Commands;

use Illuminate\Console\Command;
use Noerd\Traits\InstallsNoerdModule;

/**
 * Notifications is a support module without a tenant app and without anything
 * to publish: installing it offers the migration and registers the agent
 * guideline.
 */
class NotificationsInstallCommand extends Command
{
    use InstallsNoerdModule;

    protected $signature = 'noerd:install-notifications
                            {--force : Overwrite existing files without asking}
                            {--migrate : Run migrations without asking (required to migrate in non-interactive runs)}';

    protected $description = 'Install the noerd-notifications module (offers its migration)';

    public function handle(): int
    {
        return $this->runSupportModuleInstallation();
    }

    protected function getModuleName(): string
    {
        return 'Noerd Notifications';
    }
}
