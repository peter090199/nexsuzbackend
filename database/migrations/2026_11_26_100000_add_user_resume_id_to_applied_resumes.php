<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which saved CV (user_resumes.id) an application was sent with. Nullable: older applications, and applications sent with a new PDF
 * uploaded on the Apply page, have none. No foreign key on purpose: the owner may delete the saved CV later, and the application
 * must keep its record (the file itself is kept while an application still uses it, see App\Helpers\StoredCv).
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('applied_resumes') && !Schema::hasColumn('applied_resumes', 'user_resume_id')) {
            Schema::table('applied_resumes', function (Blueprint $t) {
                $t->unsignedBigInteger('user_resume_id')->nullable()->after('resume_pdf')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('applied_resumes', 'user_resume_id')) {
            Schema::table('applied_resumes', function (Blueprint $t) {
                $t->dropIndex(['user_resume_id']);
                $t->dropColumn('user_resume_id');
            });
        }
    }
};
