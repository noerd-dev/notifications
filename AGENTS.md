# AGENTS.md — noerd/notifications

Contributor notes for humans and AI agents working on the Notifications module. The rules for
building WITH noerd come from the `noerd/noerd` Boost guideline and skills; the module-specific
rules are in `resources/boost/guidelines/core.blade.php`. Both are rendered into the host project's
agent files by `php artisan boost:update` (add `noerd/notifications` to the `packages` array in
`boost.json`).

## What this module is

A support module (no tenant app, no `app-configs/`, no routes) for in-app notifications: a bell in
the top bar with an unread badge and a dropdown, and one API (`NotificationService::send()` or the
Laravel `NoerdChannel`) that every other module sends through. The module never references a
business module — senders pass a title, a text and a target (`route`/`component`/`arguments` or
`url`). The PHP namespace is `NoerdNotifications`, the package lives in `noerd-dev/notifications`
and depends on `noerd/noerd` only.

## Layout

- `src/Models/Notification.php` (+ `database/factories/NotificationFactory.php`),
  `src/Services/NotificationService.php`, `src/Channels/NoerdChannel.php`,
  `src/Events/NotificationSent.php`, `src/Support/{NotificationTarget,NoerdMessage,HeroiconName}.php`
- `src/Commands/` — `noerd:install-notifications`, `noerd:update-notifications`,
  `notifications:prune`
- `src/Providers/NoerdNotificationsServiceProvider.php` — migrations, translations, Livewire
  namespace `notifications::`, the bell on `TopBarRegistry`
- `resources/views/components/notification-bell.blade.php`, `resources/lang/de.json`
- `database/migrations/`, `tests/` (Pest, standalone on Orchestra Testbench)

## Working on the module

- Tests bind `NoerdNotifications\Tests\TestCase` (Orchestra Testbench + sqlite `:memory:` via
  `Noerd\Tests\TestCase`). Standalone / CI: `composer install && vendor/bin/pest`. From a host
  project: `php artisan test --compact app-modules/noerd-notifications/tests` — the host lists the
  folder in its `Testbench` suite, which runs sequentially, never in parallel with other suites.
  Tests prove mechanics with `zz` routes/components, never with a business module
- Format with the module's own `pint.json`: `vendor/bin/pint` in the package root, or from a host
  `vendor/bin/pint --config app-modules/noerd-notifications/pint.json app-modules/noerd-notifications`
  (a plain `--dirty` run from the host skips submodule files)
- An icon name is validated on send AND on render (`HeroiconName`): a stored name that does not
  resolve to a heroicon view would otherwise throw on every page of its recipient
- When a feature changes: update `resources/lang/de.json`, the tests,
  `resources/boost/guidelines/core.blade.php` and `README.md`
- Releasing: bump `"version"` in `composer.json` to the tag in the tagged commit
