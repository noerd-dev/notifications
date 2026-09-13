<?php

declare(strict_types=1);

namespace NoerdNotifications\Support;

/**
 * Whether a heroicon of the given name exists in the configured variant. A
 * notification is stored data: an icon name that does not resolve to a view
 * would throw on EVERY render of the bell for its recipient, so the name is
 * checked when the notification is sent and again when it is shown.
 */
final class HeroiconName
{
    public const string FALLBACK = 'bell';

    public static function exists(?string $name): bool
    {
        if ($name === null || preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $name) !== 1) {
            return false;
        }

        $variant = (string) config('wireui.heroicons.variant', 'outline');

        return view()->exists("heroicons::components.{$variant}.{$name}");
    }

    public static function orFallback(?string $name): string
    {
        return self::exists($name) ? (string) $name : self::FALLBACK;
    }
}
