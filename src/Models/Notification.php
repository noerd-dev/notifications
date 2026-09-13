<?php

declare(strict_types=1);

namespace NoerdNotifications\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Noerd\Models\NoerdUser;
use Noerd\Traits\BelongsToTenant;
use NoerdNotifications\Database\Factories\NotificationFactory;
use NoerdNotifications\Support\NotificationTarget;

/**
 * One in-app notification of one user in one tenant.
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(NoerdUser::class);
    }

    public function scopeForUser(Builder $query, int $userId, int $tenantId): Builder
    {
        return $query->where('user_id', $userId)->where('tenant_id', $tenantId);
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
