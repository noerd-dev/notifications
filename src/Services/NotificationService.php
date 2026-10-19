<?php

declare(strict_types=1);

namespace NoerdNotifications\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use NoerdNotifications\Events\NotificationSent;
use NoerdNotifications\Models\Notification;
use NoerdNotifications\Support\HeroiconName;
use NoerdNotifications\Support\NotificationTarget;

/**
 * The one way a module sends an in-app notification. Every call carries the
 * recipient AND the tenant explicitly — senders are often queued jobs without a
 * signed-in user. The recipient is a backend user (`userId`) or any other model
 * (`notifiable`, e.g. an employee account of an app) — exactly one of both.
 *
 * Every query method takes the recipient as a model or as a backend user id.
 */
class NotificationService
{
    public const array LEVELS = ['info', 'success', 'warning', 'error'];

    public function send(
        ?int $userId,
        int $tenantId,
        string $title,
        ?string $body = null,
        ?NotificationTarget $target = null,
        string $level = 'info',
        ?string $icon = null,
        ?string $type = null,
        ?Model $notifiable = null,
    ): Notification {
        if (($userId === null) === ($notifiable === null)) {
            throw new InvalidArgumentException('Pass exactly one recipient: userId or notifiable.');
        }

        $notification = Notification::create([
            'tenant_id' => $tenantId,
            'notifiable_type' => $notifiable?->getMorphClass() ?? Notification::userMorphClass(),
            'notifiable_id' => $notifiable?->getKey() ?? $userId,
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

    public function unreadCount(int|Model $recipient, int $tenantId): int
    {
        return $this->recipientQuery($recipient, $tenantId)->unread()->count();
    }

    /**
     * @return Collection<int, Notification>
     */
    public function latest(int|Model $recipient, int $tenantId, int $limit = 20): Collection
    {
        return $this->recipientQuery($recipient, $tenantId)
            ->latest()
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    public function markAllAsRead(int|Model $recipient, int $tenantId): int
    {
        return $this->recipientQuery($recipient, $tenantId)
            ->unread()
            ->update(['read_at' => now()]);
    }

    /**
     * Delete every notification of a recipient — called by the owner of a
     * notifiable model when it is deleted (there is no foreign key).
     */
    public function forget(Model $notifiable): int
    {
        return Notification::withoutGlobalScopes()->whereMorphedTo('notifiable', $notifiable)->delete();
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

    /**
     * @return Builder<Notification>
     */
    public function recipientQuery(int|Model $recipient, int $tenantId): Builder
    {
        $query = Notification::withoutGlobalScopes();

        return $recipient instanceof Model
            ? $query->forNotifiable($recipient, $tenantId)
            : $query->forUser($recipient, $tenantId);
    }
}
