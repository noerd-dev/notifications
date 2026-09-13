<?php

declare(strict_types=1);

namespace NoerdNotifications\Services;

use Illuminate\Database\Eloquent\Collection;
use NoerdNotifications\Events\NotificationSent;
use NoerdNotifications\Models\Notification;
use NoerdNotifications\Support\HeroiconName;
use NoerdNotifications\Support\NotificationTarget;

/**
 * The one way a module sends an in-app notification. Every call carries the
 * user AND the tenant explicitly — senders are often queued jobs without a
 * signed-in user.
 */
class NotificationService
{
    public const array LEVELS = ['info', 'success', 'warning', 'error'];

    public function send(
        int $userId,
        int $tenantId,
        string $title,
        ?string $body = null,
        ?NotificationTarget $target = null,
        string $level = 'info',
        ?string $icon = null,
        ?string $type = null,
    ): Notification {
        $notification = Notification::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => $type,
            'title' => mb_substr($title, 0, 255),
            'body' => $body,
            // An unknown icon name is dropped rather than stored: it would throw
            // on every render of the bell.
            'icon' => HeroiconName::exists($icon) ? $icon : null,
            'level' => in_array($level, self::LEVELS, true) ? $level : 'info',
            'target' => $target === null || $target->isEmpty() ? null : $target->toArray(),
        ]);

        NotificationSent::dispatch($notification);

        return $notification;
    }

    public function unreadCount(int $userId, int $tenantId): int
    {
        return Notification::withoutGlobalScopes()->forUser($userId, $tenantId)->unread()->count();
    }

    /**
     * @return Collection<int, Notification>
     */
    public function latest(int $userId, int $tenantId, int $limit = 20): Collection
    {
        return Notification::withoutGlobalScopes()
            ->forUser($userId, $tenantId)
            ->latest()
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    public function markAllAsRead(int $userId, int $tenantId): int
    {
        return Notification::withoutGlobalScopes()
            ->forUser($userId, $tenantId)
            ->unread()
            ->update(['read_at' => now()]);
    }

    /**
     * Delete notifications that were READ more than the given number of days
     * ago; unread ones stay until the user saw them.
     */
    public function prune(int $days): int
    {
        return Notification::withoutGlobalScopes()
            ->whereNotNull('read_at')
            ->where('read_at', '<', now()->subDays($days))
            ->delete();
    }
}
