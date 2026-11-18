<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One reaction per user per post, enforced by the database.
 *
 * Additive and non-destructive: it only adds an index. It REFUSES to run (and changes nothing)
 * if duplicate rows already exist, so no existing reaction is ever deleted or merged silently.
 *
 * `code` is a TEXT column in this table, so the index uses a 64 character prefix
 * (user codes are numeric and far shorter).
 */
return new class extends Migration {
    public function up(): void
    {
        $dupes = DB::select('SELECT post_id, code, COUNT(*) c FROM reactionpost GROUP BY post_id, code HAVING c > 1 LIMIT 5');
        if ($dupes) {
            throw new RuntimeException(
                'Duplicate reactions already exist (e.g. post_id=' . $dupes[0]->post_id . ', code=' . $dupes[0]->code
                . '). Resolve them manually before adding the unique index; nothing was changed.'
            );
        }

        $exists = DB::select("SHOW INDEX FROM reactionpost WHERE Key_name = 'reactionpost_post_user_unique'");
        if (!$exists) {
            DB::statement('ALTER TABLE reactionpost ADD UNIQUE INDEX reactionpost_post_user_unique (post_id, code(64))');
        }
    }

    public function down(): void
    {
        $exists = DB::select("SHOW INDEX FROM reactionpost WHERE Key_name = 'reactionpost_post_user_unique'");
        if ($exists) {
            DB::statement('ALTER TABLE reactionpost DROP INDEX reactionpost_post_user_unique');
        }
    }
};
