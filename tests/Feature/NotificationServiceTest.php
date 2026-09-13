<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification as LaravelNotification;
use Illuminate\Support\Facades\Event;
use Noerd\Models\NoerdUser;
use Noerd\Models\Tenant;
use NoerdNotifications\Channels\NoerdChannel;
use NoerdNotifications\Events\NotificationSent;
use NoerdNotifications\Models\Notification;
use NoerdNotifications\Services\NotificationService;
use NoerdNotifications\Support\NoerdMessage;
use NoerdNotifications\Support\NotificationTarget;

uses(NoerdNotifications\Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = NoerdUser::factory()->create();
    $this->tenant = Tenant::factory()->create();
    $this->otherTenant = Tenant::factory()->create();
    $this->service = app(NotificationService::class);
});

it('stores a notification for the user in the tenant', function (): void {
    $notification = $this->service->send(
        userId: $this->user->id,
        tenantId: $this->tenant->id,
        title: 'Zz done',
        body: 'Zz body',
        target: new NotificationTarget(route: 'zz.record', component: 'zz-record', arguments: ['modelId' => 7]),
        level: 'success',
        icon: 'sparkles',
        type: 'zz.done',
    );

    expect($notification->tenant_id)->toBe($this->tenant->id)
        ->and($notification->user_id)->toBe($this->user->id)
        ->and($notification->isUnread())->toBeTrue()
        ->and($notification->notificationTarget()->arguments)->toBe(['modelId' => 7])
        ->and($notification->notificationTarget()->route)->toBe('zz.record');
});

it('falls back to info for an unknown level and stores no empty target', function (): void {
    $notification = $this->service->send($this->user->id, $this->tenant->id, 'Zz', level: 'shouting', target: new NotificationTarget());

    expect($notification->level)->toBe('info')
        ->and($notification->target)->toBeNull();
});

it('counts and marks read only within the user and tenant', function (): void {
    $other = NoerdUser::factory()->create();
    $this->service->send($this->user->id, $this->tenant->id, 'Zz 1');
    $this->service->send($this->user->id, $this->tenant->id, 'Zz 2');
    $this->service->send($this->user->id, $this->otherTenant->id, 'Zz other tenant');
    $this->service->send($other->id, $this->tenant->id, 'Zz other user');

    expect($this->service->unreadCount($this->user->id, $this->tenant->id))->toBe(2);

    $this->service->markAllAsRead($this->user->id, $this->tenant->id);

    expect($this->service->unreadCount($this->user->id, $this->tenant->id))->toBe(0)
        ->and($this->service->unreadCount($this->user->id, $this->otherTenant->id))->toBe(1)
        ->and($this->service->unreadCount($other->id, $this->tenant->id))->toBe(1);
});

it('prunes only notifications read longer ago than the retention', function (): void {
    $readLongAgo = Notification::factory()->create(['user_id' => $this->user->id, 'tenant_id' => $this->tenant->id, 'read_at' => now()->subDays(100)]);
    $readRecently = Notification::factory()->create(['user_id' => $this->user->id, 'tenant_id' => $this->tenant->id, 'read_at' => now()->subDay()]);
    $oldButUnread = Notification::factory()->create(['user_id' => $this->user->id, 'tenant_id' => $this->tenant->id, 'created_at' => now()->subDays(200)]);

    $this->artisan('notifications:prune', ['--days' => 90])->assertExitCode(0);

    expect(Notification::withoutGlobalScopes()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$readRecently->id, $oldButUnread->id])->sort()->values()->all())
        ->and(Notification::withoutGlobalScopes()->find($readLongAgo->id))->toBeNull();
});

it('stores a Laravel notification sent through the NoerdChannel', function (): void {
    $this->user->selected_tenant_id = $this->tenant->id;

    $this->user->notify(new class extends LaravelNotification {
        public function via(object $notifiable): array
        {
            return [NoerdChannel::class];
        }

        public function toNoerd(object $notifiable): NoerdMessage
        {
            return new NoerdMessage(title: 'Zz via channel', level: 'warning', target: new NotificationTarget(url: '/zz'));
        }
    });

    $notification = Notification::withoutGlobalScopes()->sole();

    expect($notification->title)->toBe('Zz via channel')
        ->and($notification->tenant_id)->toBe($this->tenant->id)
        ->and($notification->user_id)->toBe($this->user->id)
        ->and($notification->notificationTarget()->url)->toBe('/zz');
});

it('keeps a known icon and drops an unknown one', function (): void {
    $known = $this->service->send($this->user->id, $this->tenant->id, 'Zz', icon: 'sparkles');
    $unknown = $this->service->send($this->user->id, $this->tenant->id, 'Zz', icon: 'zz-no-such-icon');

    expect($known->icon)->toBe('sparkles')
        ->and($unknown->icon)->toBeNull();
});

it('fires an event after the notification was stored', function (): void {
    Event::fake([NotificationSent::class]);

    $notification = $this->service->send($this->user->id, $this->tenant->id, 'Zz event');

    Event::assertDispatched(NotificationSent::class, fn(NotificationSent $event): bool => $event->notification->is($notification));
});

it('stores nothing through the NoerdChannel for a user without a tenant', function (): void {
    $this->user->selected_tenant_id = null;

    $this->user->notify(new class extends LaravelNotification {
        public function via(object $notifiable): array
        {
            return [NoerdChannel::class];
        }

        public function toNoerd(object $notifiable): NoerdMessage
        {
            return new NoerdMessage(title: 'Zz nowhere');
        }
    });

    expect(Notification::withoutGlobalScopes()->count())->toBe(0);
});

it('takes the tenant from the message before the notifiable', function (): void {
    $this->user->selected_tenant_id = $this->tenant->id;
    $otherTenant = $this->otherTenant;

    $this->user->notify(new class ($otherTenant->id) extends LaravelNotification {
        public function __construct(private readonly int $tenantId) {}

        public function via(object $notifiable): array
        {
            return [NoerdChannel::class];
        }

        public function toNoerd(object $notifiable): NoerdMessage
        {
            return new NoerdMessage(title: 'Zz explicit tenant', tenantId: $this->tenantId);
        }
    });

    expect(Notification::withoutGlobalScopes()->sole()->tenant_id)->toBe($otherTenant->id);
});

it('registers the install, update and prune commands', function (): void {
    expect(Artisan::all())
        ->toHaveKey('noerd:install-notifications')
        ->toHaveKey('noerd:update-notifications')
        ->toHaveKey('notifications:prune');
});
