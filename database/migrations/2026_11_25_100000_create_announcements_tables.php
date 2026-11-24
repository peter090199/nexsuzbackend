<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Global announcements written by DEF-ADMIN / DEF-MASTERADMIN and shown to every job seeker and recruiter.
 * A separate table from `posts` on purpose: posts.status already means privacy (private / public / connections), so it cannot also
 * carry Draft / Published / Archived, and a draft must never be reachable through any of the post queries.
 * Comments reuse commentposts (keyed by announcement_uuid) and reactions reuse reactionpost (post_id = -announcements.id, a range
 * real posts never use).
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $t) {
                $t->engine = 'InnoDB';
                $t->id();
                $t->char('announcement_uuid', 36)->unique();
                $t->integer('code')->index();                         // users.code of the administrator who wrote it
                $t->string('title', 200);
                $t->text('content');
                $t->string('priority', 10)->default('normal');         // normal | important | urgent
                $t->boolean('is_pinned')->default(false);
                $t->string('status', 10)->default('draft');            // draft | published | archived
                $t->timestamp('published_at')->nullable();
                $t->timestamp('expires_at')->nullable();
                $t->string('created_by')->nullable();
                $t->string('updated_by')->nullable();
                $t->timestamps();
                $t->index(['status', 'published_at']);
            });
        }
        if (!Schema::hasTable('announcement_attachments')) {
            Schema::create('announcement_attachments', function (Blueprint $t) {
                $t->engine = 'InnoDB';
                $t->id();
                $t->unsignedBigInteger('announcement_id')->index();
                $t->string('kind', 10);                                // image | file
                $t->text('path_url');
                $t->string('original_name')->nullable();
                $t->string('mime', 100)->nullable();
                $t->unsignedInteger('size')->default(0);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_attachments');
        Schema::dropIfExists('announcements');
    }
};
