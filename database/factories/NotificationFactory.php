<?php

declare(strict_types=1);

namespace NoerdNotifications\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Noerd\Models\NoerdUser;
use Noerd\Models\Tenant;
use NoerdNotifications\Models\Notification;
use NoerdNotifications\Support\NotificationTarget;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'notifiable_type' => Notification::userMorphClass(),
            'notifiable_id' => NoerdUser::factory(),
            'type' => null,
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->sentence(),
            'icon' => null,
            'level' => 'info',
            'target' => null,
            'read_at' => null,
        ];
    }

    public function recipient(Model|int $recipient): static
    {
        if (is_int($recipient)) {
            return $this->state(['notifiable_type' => Notification::userMorphClass(), 'notifiable_id' => $recipient]);
        }

        return $this->state(['notifiable_type' => $recipient->getMorphClass(), 'notifiable_id' => $recipient->getKey()]);
    }

    public function read(): static
    {
        return $this->state(['read_at' => now()]);
    }

    public function level(string $level): static
    {
        return $this->state(['level' => $level]);
    }

    public function target(NotificationTarget $target): static
    {
        return $this->state(['target' => $target->toArray()]);
    }
}
