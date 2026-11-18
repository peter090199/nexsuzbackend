<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user notification preferences. The app had no settings storage at all, so this is the one new table:
 * a settings JSON (only keys the user changed; defaults live in App\Helpers\NotificationPrefs) and the moment
 * the user last looked at their activity notifications ("seen_at").
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_notification_settings')) {
            return;                       // already created by hand on this machine
        }

        Schema::create('user_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();   // users.id: exactly one row per user
            $table->json('settings')->nullable();
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_settings');
    }
};
