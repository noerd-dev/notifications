<?php

declare(strict_types=1);

namespace NoerdNotifications\Channels;

use Illuminate\Notifications\Notification as LaravelNotification;
use InvalidArgumentException;
use NoerdNotifications\Models\Notification;
use NoerdNotifications\Services\NotificationService;
use NoerdNotifications\Support\NoerdMessage;

/**
 * Laravel notification channel: `via()` returns NoerdChannel::class and
 * `toNoerd($notifiable)` returns a NoerdMessage. The same notification class
 * may additionally send a mail through the regular channels.
 */
class NoerdChannel
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function send(object $notifiable, LaravelNotification $notification): ?Notification
    {
        if (! method_exists($notification, 'toNoerd')) {
            throw new InvalidArgumentException($notification::class . ' must implement toNoerd() to use the NoerdChannel.');
        }

        $message = $notification->toNoerd($notifiable);

        if (! $message instanceof NoerdMessage) {
            throw new InvalidArgumentException($notification::class . '::toNoerd() must return a ' . NoerdMessage::class . '.');
        }

        $tenantId = $message->tenantId ?? ($notifiable->selected_tenant_id ?? null);

        // A user without a tenant has no bell to show the notification in.
        if ($tenantId === null) {
            return null;
        }

        return $this->notifications->send(
            userId: (int) $notifiable->getKey(),
            tenantId: (int) $tenantId,
            title: $message->title,
            body: $message->body,
            target: $message->target,
            level: $message->level,
            icon: $message->icon,
            type: $message->type,
        );
    }
}
