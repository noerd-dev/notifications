<?php

declare(strict_types=1);

namespace NoerdNotifications\Support;

/**
 * What a Laravel notification returns from toNoerd() for the NoerdChannel.
 * Without a tenant id the notifiable's selected tenant is used.
 */
final readonly class NoerdMessage
{
    public function __construct(
        public string $title,
        public ?string $body = null,
        public ?NotificationTarget $target = null,
        public string $level = 'info',
        public ?string $icon = null,
        public ?string $type = null,
        public ?int $tenantId = null,
    ) {}
}
