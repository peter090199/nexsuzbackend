<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Who may see a profile (App\Helpers\ProfilePrivacy). Null = the defaults, which are how profiles behaved before. */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'privacy_settings')) {
            Schema::table('users', fn (Blueprint $t) => $t->json('privacy_settings')->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'privacy_settings')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn('privacy_settings'));
        }
    }
};
