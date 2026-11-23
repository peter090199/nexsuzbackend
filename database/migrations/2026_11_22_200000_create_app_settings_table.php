<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generic key/value store for settings an administrator changes from the UI (first user: the SMTP configuration).
 * The project had no settings table, so this one small table is the only schema change needed. Secrets inside a value
 * (the SMTP password) are encrypted by the application before they are stored.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('app_settings')) {
            return;
        }
        Schema::create('app_settings', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('setting_key', 120)->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
