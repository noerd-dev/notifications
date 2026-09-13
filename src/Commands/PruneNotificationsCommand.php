<?php

declare(strict_types=1);

namespace NoerdNotifications\Commands;

use Illuminate\Console\Command;
use NoerdNotifications\Services\NotificationService;

class PruneNotificationsCommand extends Command
{
    protected $signature = 'notifications:prune {--days=90 : Delete notifications that were read more than this many days ago}';

    protected $description = 'Delete notifications that were read longer ago than the retention period';

    public function handle(NotificationService $notifications): int
    {
        $deleted = $notifications->prune(max(1, (int) $this->option('days')));

        $this->info("Deleted {$deleted} read notifications.");

        return 0;
    }
}
