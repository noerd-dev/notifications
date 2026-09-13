<?php

use Livewire\Attributes\Computed;
use Livewire\Component;
use Noerd\Facades\Noerd;
use Noerd\Helpers\FormatHelper;
use Noerd\Helpers\NoerdAuth;
use NoerdNotifications\Models\Notification;
use NoerdNotifications\Services\NotificationService;
use NoerdNotifications\Support\HeroiconName;

/**
 * The bell in the top bar: unread badge, the latest notifications, "mark all as
 * read" and opening a notification's target. Polls — no broadcasting needed.
 */
new class extends Component {
    #[Computed]
    public function userId(): ?int
    {
        $id = NoerdAuth::id();

        return $id === null ? null : (int) $id;
    }

    #[Computed]
    public function tenantId(): ?int
    {
        $tenantId = NoerdAuth::user()?->selected_tenant_id;

        return $tenantId === null ? null : (int) $tenantId;
    }

    public function markAllAsRead(NotificationService $notifications): void
    {
        if ($this->userId && $this->tenantId) {
            $notifications->markAllAsRead($this->userId, $this->tenantId);
        }
    }

    public function openNotification(int $notificationId, NotificationService $notifications)
    {
        abort_unless($this->userId && $this->tenantId, 404);

        // Another user's or another tenant's notification does not exist here.
        $notification = Notification::withoutGlobalScopes()
            ->forUser($this->userId, $this->tenantId)
            ->find($notificationId);

        abort_unless($notification, 404);

        $notifications->markAsRead($notification);

        $target = $notification->notificationTarget();

        if ($target->url !== null) {
            return $this->redirect($target->url);
        }

        if ($target->route !== null || $target->component !== null) {
            Noerd::modalFor($target->route, $target->component, $target->arguments);
        }

        return null;
    }

    public function with(NotificationService $notifications): array
    {
        if (! $this->userId || ! $this->tenantId) {
            return ['unreadCount' => 0, 'notifications' => collect()];
        }

        return [
            'unreadCount' => $notifications->unreadCount($this->userId, $this->tenantId),
            'notifications' => $notifications->latest($this->userId, $this->tenantId),
        ];
    }
}; ?>

@php
    $levelColors = [
        'success' => 'text-green-600',
        'warning' => 'text-amber-500',
        'error' => 'text-red-600',
        'info' => 'text-brand-primary',
    ];
@endphp

<div wire:poll.30s class="shrink-0">
    @if($this->tenantId)
        <x-noerd::action-menu align="right" width="w-80" wrapperClass="relative shrink-0" panelClass="max-h-[28rem] overflow-y-auto !py-0" :label="__('Notifications')">
            <x-slot:trigger>
                <button type="button" x-on:click="open = ! open" :aria-expanded="open" aria-haspopup="true"
                        aria-label="{{ __('Notifications') }}" title="{{ __('Notifications') }}"
                        class="relative flex cursor-pointer items-center justify-center rounded-full p-1 text-gray-500 hover:text-gray-800 focus:outline-hidden focus:ring-2 focus:ring-brand-border">
                    <x-icon name="bell" class="h-6 w-6" />
                    @if($unreadCount > 0)
                        <span data-unread-badge
                              class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold leading-none text-white">
                            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                        </span>
                    @endif
                </button>
            </x-slot:trigger>

            <div class="sticky top-0 flex items-center justify-between gap-2 border-b border-gray-100 bg-white px-4 py-2">
                <span class="text-sm font-semibold text-gray-900">{{ __('Notifications') }}</span>
                @if($unreadCount > 0)
                    <button type="button" wire:click="markAllAsRead" x-on:click.stop
                            class="cursor-pointer text-xs text-brand-primary hover:underline">
                        {{ __('Mark all as read') }}
                    </button>
                @endif
            </div>

            @forelse($notifications as $notification)
                <button type="button" role="menuitem" wire:key="notification-{{ $notification->id }}"
                        wire:click="openNotification({{ $notification->id }})" x-on:click="open = false"
                        @class([
                            'flex w-full cursor-pointer gap-3 border-b border-gray-50 px-4 py-3 text-left hover:bg-gray-50',
                            'bg-brand-primary/5' => $notification->isUnread(),
                        ])>
                    <x-icon :name="HeroiconName::orFallback($notification->icon)" @class(['mt-0.5 h-5 w-5 shrink-0', $levelColors[$notification->level] ?? $levelColors['info']]) />
                    <span class="min-w-0 flex-1">
                        <span @class(['block truncate text-sm text-gray-900', 'font-semibold' => $notification->isUnread()])>{{ $notification->title }}</span>
                        @if($notification->body)
                            <span class="block text-xs text-gray-600 line-clamp-2">{{ $notification->body }}</span>
                        @endif
                        <span class="mt-0.5 block text-xs text-gray-400">{{ $notification->created_at->locale(FormatHelper::locale())->diffForHumans() }}</span>
                    </span>
                    @if($notification->isUnread())
                        <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-red-600"></span>
                    @endif
                </button>
            @empty
                <div class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No notifications') }}</div>
            @endforelse
        </x-noerd::action-menu>
    @endif
</div>
