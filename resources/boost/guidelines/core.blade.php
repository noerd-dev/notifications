@verbatim
## Notifications Module

In-app notifications for noerd applications (Composer package `noerd/notifications`, namespace
`NoerdNotifications` — no `Noerd\` prefix; installed at `app-modules/noerd-notifications`). It is a
SUPPORT module: no tenant app, no `app-configs/`, no routes, no navigation. The framework rules come
from the `noerd/noerd` guideline — this block only adds what is specific to this module.

### Purpose
A bell in the top bar (right of the quick menu, left of the setup cog and the profile) with a red
badge for unread notifications, the latest 20 entries, "Mark all as read" and a click that marks an
entry read and opens its target. ANY module sends notifications through the API below — this
module never knows who sends them or what the targets are

### Sending
- `app(NotificationService::class)->send(userId:, tenantId:, title:, body:, target:, level:, icon:, type:)`
  — user AND tenant are always explicit (senders are often queued jobs without a signed-in user);
  `level` is `info|success|warning|error`, `icon` a heroicon name, `type` a free key of the
  sender (`liefertool.menu_imported`). Texts are stored already translated — translate with `__()`
  when sending
- Target: `NoerdNotifications\Support\NotificationTarget` — `route` + `component` + `arguments`
  for a RECORD (opened with `Noerd::modalFor()`: route first, component as fallback) or `url` for a
  plain page (navigated to). A notification without target is only marked read
- Laravel way: `NoerdUser` is `Notifiable`; a notification class whose `via()` returns
  `NoerdChannel::class` and whose `toNoerd($notifiable)` returns a `NoerdMessage` is stored the same
  way (tenant from the message, else the notifiable's `selected_tenant_id`)

### Structure
- `Models\Notification` (`noerd_notifications`, `BelongsToTenant`, `$guarded = []`): `tenant_id`,
  `user_id`, `type`, `title`, `body`, `icon`, `level`, `target` (json), `read_at`; scopes
  `forUser($userId, $tenantId)`, `unread()`; `markAsRead()`, `notificationTarget()`
- `Services\NotificationService` (singleton): `send()`, `unreadCount()`, `latest()`, `markAsRead()`,
  `markAllAsRead()`, `prune($days)`
- Livewire `notifications::notification-bell` (`resources/views/components/`), registered on the
  core `TopBarRegistry` in the provider through `callAfterResolving()` — no core change, no YAML.
  It polls (`wire:poll.30s`), uses `<x-noerd::action-menu>` with a custom trigger, and only ever
  loads notifications of the signed-in user in the selected tenant (`openNotification()` answers 404
  otherwise)
- Translations: `resources/lang/de.json` (English keys)

### Commands
- `php artisan noerd:install-notifications` — runs the migration (confirmation prompt); registers
  NO tenant app
- `php artisan noerd:update-notifications` — nothing to publish; exists for `noerd:update-all`
- `php artisan notifications:prune {--days=90}` — deletes READ notifications older than the
  retention; the host schedules it

### Tests
- Pest tests in `tests/Feature`, bound to `Tests\TestCase` (host-bound, MySQL, `RefreshDatabase`);
  run with `php artisan test --compact app-modules/noerd-notifications/tests`
- Targets are proven with a runtime route (`registerTestLivewireRoute()`) and `zz` components —
  never with a business module (`ModuleBoundaryTest`)
@endverbatim
