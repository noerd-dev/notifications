<?php

declare(strict_types=1);

namespace NoerdNotifications\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Noerd\Models\NoerdUser;
use Noerd\Traits\BelongsToTenant;
use NoerdNotifications\Database\Factories\NotificationFactory;
use NoerdNotifications\Support\NotificationTarget;

/**
 * One in-app notification of one recipient in one tenant. The recipient is a
 * morph (`notifiable`): a backend user, or any other model an app notifies.
 *
 * @property array{route?: ?string, component?: ?string, arguments?: array<string, mixed>, url?: ?string}|null $target
 */
class Notification extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'noerd_notifications';

    protected $guarded = [];

    protected $casts = [
        'target' => 'array',
        'read_at' => 'datetime',
    ];

    public static function userMorphClass(): string
    {
        return (new NoerdUser())->getMorphClass();
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForNotifiable(Builder $query, Model $notifiable, int $tenantId): Builder
    {
        return $query->whereMorphedTo('notifiable', $notifiable)->where('tenant_id', $tenantId);
    }

    /**
     * Short form for the backend user — the recipient of the bell.
     */
    public function scopeForUser(Builder $query, int $userId, int $tenantId): Builder
    {
        return $query
            ->where('notifiable_type', self::userMorphClass())
            ->where('notifiable_id', $userId)
            ->where('tenant_id', $tenantId);
    }

    public function isFor(Model $notifiable): bool
    {
        return $this->notifiable_type === $notifiable->getMorphClass()
            && (int) $this->notifiable_id === (int) $notifiable->getKey();
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markAsRead(): void
    {
        if ($this->isUnread()) {
            $this->update(['read_at' => now()]);
        }
    }

    public function notificationTarget(): NotificationTarget
    {
        return NotificationTarget::fromArray($this->target);
    }

    protected static function newFactory(): Factory
    {
        return NotificationFactory::new();
    }
}
