<?php

declare(strict_types=1);

namespace NoerdNotifications\Commands;

use Illuminate\Console\Command;
use Noerd\Traits\HasModuleInstallation;
use Noerd\Traits\RequiresNoerdInstallation;

class NotificationsInstallCommand extends Command
{
    use HasModuleInstallation;
    use RequiresNoerdInstallation;

    protected $signature = 'noerd:install-notifications {--force : Kept for noerd:update-all, the module publishes nothing}';

    protected $description = 'Install the noerd-notifications module (runs its migration)';

    public function handle(): int
    {
        if (! $this->ensureNoerdInstalled()) {
            return 1;
        }

        $this->info('Installing Noerd Notifications...');

        $this->askForMigration();

        $this->info('Noerd Notifications successfully installed! The bell appears in the top bar.');

        return 0;
    }

    protected function getModuleName(): string
    {
        return 'Notifications';
    }

    protected function getModuleKey(): string
    {
        return 'noerd-notifications';
    }

    protected function getDefaultAppTitle(): string
    {
        return 'Notifications';
    }

    protected function getAppIcon(): string
    {
        return '';
    }

    protected function getAppRoute(): string
    {
        return '';
    }

    protected function getSourceDir(): string
    {
        return dirname(__DIR__, 2);
    }
}
