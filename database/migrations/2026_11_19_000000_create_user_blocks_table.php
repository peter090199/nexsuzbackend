<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row = "blocker_code blocked blocked_code". Keyed by users.code, the identifier profiles,
 * follows and posts already use. The pair is unique, so blocking twice is a no-op.
 * Nothing else is touched: messages, posts and accounts are never deleted by a block.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('blocker_code');
            $table->unsignedBigInteger('blocked_code');
            $table->timestamps();

            $table->unique(['blocker_code', 'blocked_code']);
            $table->index('blocked_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_blocks');
    }
};
