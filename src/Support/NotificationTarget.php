<?php

declare(strict_types=1);

namespace NoerdNotifications\Support;

/**
 * Where a click on a notification leads — without the module knowing who owns
 * the target:
 *
 * - `route` + `component` + `arguments`: a record, opened like
 *   Noerd::modalFor() (the route wins when registered, the component is the
 *   fallback when the owning module has none)
 * - `url`: a plain page (a list, a report), navigated to
 */
final readonly class NotificationTarget
{
    /**
     * @param  array<string, mixed>  $arguments
     */
    public function __construct(
        public ?string $route = null,
        public ?string $component = null,
        public array $arguments = [],
        public ?string $url = null,
    ) {}

    /**
     * @param  array<string, mixed>|null  $target
     */
    public static function fromArray(?array $target): self
    {
        return new self(
            route: $target['route'] ?? null,
            component: $target['component'] ?? null,
            arguments: is_array($target['arguments'] ?? null) ? $target['arguments'] : [],
            url: $target['url'] ?? null,
        );
    }

    public function isEmpty(): bool
    {
        return $this->route === null && $this->component === null && $this->url === null;
    }

    /**
     * @return array{route: ?string, component: ?string, arguments: array<string, mixed>, url: ?string}
     */
    public function toArray(): array
    {
        return [
            'route' => $this->route,
            'component' => $this->component,
            'arguments' => $this->arguments,
            'url' => $this->url,
        ];
    }
}
