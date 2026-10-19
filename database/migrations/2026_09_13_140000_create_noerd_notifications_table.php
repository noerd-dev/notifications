<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('noerd_notifications')) {
            return;
        }

        Schema::create('noerd_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            // Whoever receives it: a backend user or any other model (an employee
            // account of an app). No foreign key — the owner of the model cleans up.
            $table->morphs('notifiable');
            $table->string('type')->nullable();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('icon')->nullable();
            $table->string('level', 20)->default('info');
            $table->json('target')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'tenant_id', 'read_at'], 'noerd_notifications_notifiable_read_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('noerd_notifications');
    }
};
