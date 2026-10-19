<?php

declare(strict_types=1);

namespace NoerdNotifications\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Noerd\Models\NoerdUser;
use Noerd\Services\TopBarRegistry;
use NoerdNotifications\Commands\NotificationsInstallCommand;
use NoerdNotifications\Commands\NotificationsUpdateCommand;
use NoerdNotifications\Commands\PruneNotificationsCommand;
use NoerdNotifications\Services\NotificationService;

class NoerdNotificationsServiceProvider extends ServiceProvider
{
    public const string BELL_COMPONENT = 'notifications::notification-bell';

    public function register(): void
    {
        $this->app->singleton(NotificationService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadJsonTranslationsFrom(__DIR__ . '/../../resources/lang');

        Livewire::addNamespace('notifications', viewPath: __DIR__ . '/../../resources/views/components');

        // After resolving instead of app()->register(): the registry is filled on
        // whichever instance the top bar resolves, independent of provider order.
        $this->callAfterResolving(
            TopBarRegistry::class,
            fn(TopBarRegistry $registry) => $registry->register(self::BELL_COMPONENT),
        );

        // The recipient is a morph without a foreign key: a deleted backend user
        // takes their notifications along here. Other notifiables are cleaned up
        // by the module that owns them (NotificationService::forget()).
        NoerdUser::deleted(fn(NoerdUser $user) => $this->app->make(NotificationService::class)->forget($user));

        if ($this->app->runningInConsole()) {
            $this->commands([
                NotificationsInstallCommand::class,
                NotificationsUpdateCommand::class,
                PruneNotificationsCommand::class,
            ]);
        }
    }
}
