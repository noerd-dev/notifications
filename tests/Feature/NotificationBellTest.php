<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Livewire\Livewire;
use Noerd\Helpers\TenantHelper;
use Noerd\Models\NoerdUser;
use Noerd\Models\Tenant;
use Noerd\Services\TopBarRegistry;
use NoerdNotifications\Models\Notification;
use NoerdNotifications\Providers\NoerdNotificationsServiceProvider;
use NoerdNotifications\Services\NotificationService;
use NoerdNotifications\Support\NotificationTarget;

uses(NoerdNotifications\Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = NoerdUser::factory()->create();
    $this->tenant = Tenant::factory()->create();
    $this->user->tenants()->attach($this->tenant->id);
    TenantHelper::setSelectedTenantId($this->tenant->id);
    $this->actingAs($this->user);
    $this->service = app(NotificationService::class);
});

it('is registered in the top bar', function (): void {
    expect(app(TopBarRegistry::class)->all())->toContain(NoerdNotificationsServiceProvider::BELL_COMPONENT);
});

it('shows a badge with the number of unread notifications', function (): void {
    $this->service->send($this->user->id, $this->tenant->id, 'Zz first');
    $this->service->send($this->user->id, $this->tenant->id, 'Zz second');
    $this->service->send($this->user->id, Tenant::factory()->create()->id, 'Zz other tenant');

    Livewire::test('notifications::notification-bell')
        ->assertSeeHtml('data-unread-badge')
        ->assertViewHas('unreadCount', 2)
        ->assertSee('Zz second')
        ->assertDontSee('Zz other tenant');
});

it('shows no badge without unread notifications', function (): void {
    Livewire::test('notifications::notification-bell')
        ->assertDontSeeHtml('data-unread-badge')
        ->assertViewHas('unreadCount', 0);
});

it('clears the badge when everything is marked as read', function (): void {
    $this->service->send($this->user->id, $this->tenant->id, 'Zz first');

    Livewire::test('notifications::notification-bell')
        ->call('markAllAsRead')
        ->assertViewHas('unreadCount', 0)
        ->assertDontSeeHtml('data-unread-badge');
});

it('marks a notification read and opens its record target', function (): void {
    Livewire::component('zz-notification-record', new class extends Component {
        public ?int $modelId = null;

        public function render(): string
        {
            return '<div>zz record</div>';
        }
    });
    registerTestLivewireRoute('zz-notification-record/{modelId}', 'zz-notification-record', 'zz.notification-record');

    $notification = $this->service->send(
        $this->user->id,
        $this->tenant->id,
        'Zz done',
        target: new NotificationTarget(route: 'zz.notification-record', component: 'zz-notification-record', arguments: ['modelId' => 5]),
    );

    Livewire::test('notifications::notification-bell')
        ->call('openNotification', $notification->id)
        ->assertDispatched('noerdModal');

    expect($notification->refresh()->isUnread())->toBeFalse();
});

it('navigates to a page target', function (): void {
    $notification = $this->service->send($this->user->id, $this->tenant->id, 'Zz failed', target: new NotificationTarget(url: '/zz-page'));

    Livewire::test('notifications::notification-bell')
        ->call('openNotification', $notification->id)
        ->assertRedirect('/zz-page');
});

it('renders a fallback icon for a stored icon name that does not exist', function (): void {
    Notification::factory()->create(['user_id' => $this->user->id, 'tenant_id' => $this->tenant->id, 'title' => 'Zz broken icon', 'icon' => 'zz-no-such-icon']);

    Livewire::test('notifications::notification-bell')
        ->assertOk()
        ->assertSee('Zz broken icon');
});

it('does not open a notification of another user', function (): void {
    $foreign = $this->service->send(NoerdUser::factory()->create()->id, $this->tenant->id, 'Zz foreign');

    Livewire::test('notifications::notification-bell')
        ->call('openNotification', $foreign->id)
        ->assertStatus(404);

    expect(Notification::withoutGlobalScopes()->find($foreign->id)->isUnread())->toBeTrue();
});
