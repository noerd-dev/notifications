<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification as LaravelNotification;
use Noerd\Models\NoerdUser;
use Noerd\Models\Tenant;
use NoerdNotifications\Channels\NoerdChannel;
use NoerdNotifications\Models\Notification;
use NoerdNotifications\Services\NotificationService;
use NoerdNotifications\Support\NoerdMessage;
use NoerdNotifications\Support\NotificationTarget;

uses(Tests\TestCase::class, RefreshDatabase::class);

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

it('prunes only old read notifications', function (): void {
    $oldRead = $this->service->send($this->user->id, $this->tenant->id, 'Zz old read');
    $oldUnread = $this->service->send($this->user->id, $this->tenant->id, 'Zz old unread');
    $recentRead = $this->service->send($this->user->id, $this->tenant->id, 'Zz recent read');
    Notification::withoutGlobalScopes()->whereKey([$oldRead->id, $oldUnread->id])->update(['created_at' => now()->subDays(100)]);
    Notification::withoutGlobalScopes()->whereKey([$oldRead->id, $recentRead->id])->update(['read_at' => now()]);

    $this->artisan('notifications:prune', ['--days' => 90])->assertExitCode(0);

    expect(Notification::withoutGlobalScopes()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$oldUnread->id, $recentRead->id])->sort()->values()->all());
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

it('registers the install, update and prune commands', function (): void {
    expect(Artisan::all())
        ->toHaveKey('noerd:install-notifications')
        ->toHaveKey('noerd:update-notifications')
        ->toHaveKey('notifications:prune');
});
