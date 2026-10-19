<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Noerd\Models\NoerdUser;

/**
 * Installations created before v0.2.0 carry a `user_id` bound to noerd_users.
 * The recipient becomes a morph (`notifiable`), so an app can notify models
 * that are not backend users. Every existing row belonged to a NoerdUser.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('noerd_notifications') || ! Schema::hasColumn('noerd_notifications', 'user_id')) {
            return;
        }

        Schema::table('noerd_notifications', function (Blueprint $table): void {
            $table->string('notifiable_type')->nullable()->after('tenant_id');
            $table->unsignedBigInteger('notifiable_id')->nullable()->after('notifiable_type');
        });

        DB::table('noerd_notifications')->update([
            'notifiable_type' => (new NoerdUser())->getMorphClass(),
            'notifiable_id' => DB::raw('user_id'),
        ]);

        Schema::table('noerd_notifications', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });

        Schema::table('noerd_notifications', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'tenant_id', 'read_at']);
            $table->dropColumn('user_id');
        });

        Schema::table('noerd_notifications', function (Blueprint $table): void {
            $table->string('notifiable_type')->nullable(false)->change();
            $table->unsignedBigInteger('notifiable_id')->nullable(false)->change();
            $table->index(['notifiable_type', 'notifiable_id'], 'noerd_notifications_notifiable_type_notifiable_id_index');
            $table->index(['notifiable_type', 'notifiable_id', 'tenant_id', 'read_at'], 'noerd_notifications_notifiable_read_index');
        });
    }

    public function down(): void
    {
        // One-way: rows of other notifiable types have no user to go back to.
    }
};
