<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Optional image on a comment / reply, plus lookup indexes for the paginated comment list.
 * Additive only: two nullable columns and two indexes; no existing data is modified.
 */
return new class extends Migration {
    public function up(): void
    {
        foreach (['commentposts', 'commentreplies'] as $table) {
            if (!Schema::hasColumn($table, 'attachment_url')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->text('attachment_url')->nullable()->after('comment');
                });
            }
        }

        if (!DB::select("SHOW INDEX FROM commentposts WHERE Key_name = 'commentposts_post_idx'")) {
            DB::statement('ALTER TABLE commentposts ADD INDEX commentposts_post_idx (post_uuidOrUind(64), id)');
        }
        if (!DB::select("SHOW INDEX FROM commentreplies WHERE Key_name = 'commentreplies_comment_idx'")) {
            DB::statement('ALTER TABLE commentreplies ADD INDEX commentreplies_comment_idx (comment_uuid, id)');
        }
    }

    public function down(): void
    {
        if (DB::select("SHOW INDEX FROM commentposts WHERE Key_name = 'commentposts_post_idx'")) {
            DB::statement('ALTER TABLE commentposts DROP INDEX commentposts_post_idx');
        }
        if (DB::select("SHOW INDEX FROM commentreplies WHERE Key_name = 'commentreplies_comment_idx'")) {
            DB::statement('ALTER TABLE commentreplies DROP INDEX commentreplies_comment_idx');
        }
        foreach (['commentposts', 'commentreplies'] as $table) {
            if (Schema::hasColumn($table, 'attachment_url')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn('attachment_url'));
            }
        }
    }
};
