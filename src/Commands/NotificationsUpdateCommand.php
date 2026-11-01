<?php

declare(strict_types=1);

namespace NoerdNotifications\Commands;

/**
 * Nothing to republish besides the agent guideline — the command exists so
 * noerd:update-all covers the module.
 */
class NotificationsUpdateCommand extends NotificationsInstallCommand
{
    protected $signature = 'noerd:update-notifications';

    protected $description = 'Update the noerd-notifications module';
}
