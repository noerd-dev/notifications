# noerd/notifications

In-app notifications for noerd applications: a bell in the top bar with a red badge for unread
entries, a dropdown with the latest notifications, "Mark all as read", and a click that opens the
notification's target. Any module sends notifications through one API.

## Installation

```bash
composer require noerd/notifications
php artisan noerd:install-notifications
```

The bell registers itself in the top bar — no configuration needed.

## Sending a notification

```php
use NoerdNotifications\Services\NotificationService;
use NoerdNotifications\Support\NotificationTarget;

app(NotificationService::class)->send(
    userId: $user->id,
    tenantId: $tenantId,
    title: __('Menu imported'),
    body: __('Lunch menu was imported from menu.pdf.'),
    target: new NotificationTarget(route: 'menu.detail', component: 'liefertool::menu-detail', arguments: ['modelId' => $menu->id]),
    level: 'success',
    icon: 'sparkles',
    type: 'liefertool.menu_imported',
);
```

A target opens a record as a modal (`route` first, `component` as fallback) or navigates to a page
(`url`). Alternatively return `NoerdChannel::class` from a Laravel notification's `via()` and a
`NoerdMessage` from its `toNoerd($notifiable)`.

## Commands

- `php artisan noerd:update-notifications` — covered by `noerd:update-all`
- `php artisan notifications:prune --days=90` — deletes read notifications older than 90 days
  (schedule it in the host)
