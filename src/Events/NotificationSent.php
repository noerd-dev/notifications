<?php

declare(strict_types=1);

namespace NoerdNotifications\Events;

use Illuminate\Foundation\Events\Dispatchable;
use NoerdNotifications\Models\Notification;

/**
 * Fired after a notification row was written. A host listens to it to add a
 * delivery channel the module does not ship — broadcasting, push, mail —
 * without extending the service.
 */
class NotificationSent
{
    use Dispatchable;

    public function __construct(public readonly Notification $notification) {}
}
