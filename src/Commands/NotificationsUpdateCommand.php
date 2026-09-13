<?php

declare(strict_types=1);

namespace NoerdNotifications\Commands;

class NotificationsUpdateCommand extends NotificationsInstallCommand
{
    protected $signature = 'noerd:update-notifications {--force : Kept for noerd:update-all, the module publishes nothing}';

    protected $description = 'Update the noerd-notifications module';

    /**
     * The module publishes no config and no app-configs — the migration arrives
     * through the provider. The command exists so noerd:update-all covers it.
     */
    public function handle(): int
    {
        if (! $this->ensureNoerdInstalled()) {
            return 1;
        }

        $this->info("{$this->getModuleName()}: nothing to publish.");

        return 0;
    }
}
