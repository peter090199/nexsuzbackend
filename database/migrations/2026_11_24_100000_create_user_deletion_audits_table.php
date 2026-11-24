<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Who deleted which account, when, and how it went. Kept after the account is gone, so it holds no live foreign keys. */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('user_deletion_audits')) { return; }
        Schema::create('user_deletion_audits', function (Blueprint $t) {
            $t->engine = 'InnoDB';   // the audit row must roll back with the work it records
            $t->id();
            $t->string('target_code', 32)->index();
            $t->string('target_name')->nullable();
            $t->string('target_email_masked')->nullable();     // j***@example.com : enough to recognise, not to contact
            $t->string('target_role', 32)->nullable();
            $t->string('deleted_by_code', 32)->index();
            $t->string('deleted_by_name')->nullable();
            $t->string('status', 16)->default('started');      // started | completed | failed
            $t->unsignedInteger('rows_deleted')->default(0);
            $t->unsignedInteger('rows_anonymized')->default(0);
            $t->unsignedInteger('files_queued')->default(0);
            $t->unsignedInteger('files_deleted')->default(0);
            $t->string('backup')->nullable();
            $t->text('error')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_deletion_audits');
    }
};
