<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reactions on comments and replies (new table; nothing existing is touched).
 *
 *  target_type : 'comment' (commentposts) or 'reply' (commentreplies)
 *  target_ref  : comment_uuid for a comment, the reply id for a reply
 *  UNIQUE (target_type, target_ref, code) => one reaction per user per comment
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('commentreactions')) {
            return;
        }

        Schema::create('commentreactions', function (Blueprint $table) {
            $table->id();
            $table->enum('target_type', ['comment', 'reply']);
            $table->string('target_ref', 64);
            $table->integer('code');                 // users.code of the person reacting
            $table->string('reaction', 32);
            $table->timestamps();

            $table->unique(['target_type', 'target_ref', 'code'], 'commentreactions_one_per_user');
            $table->index(['target_type', 'target_ref'], 'commentreactions_target_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commentreactions');
    }
};
