<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive only: every new column is nullable or has a default, so existing jobs and their applications behave
 * exactly as before. Nothing is dropped or renamed.
 *
 *  jobposting        job details (type, level, salary period, education, skills, vacancies) and application
 *                    settings (deadline, CV on/off, limit, automatic screening). Drafts reuse `recordstatus`.
 *  applied_questions screening question type, options, required flag, correct answer and order.
 *                    Rows without question_type are the old questions and keep working unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobposting', function (Blueprint $table) {
            $table->unsignedInteger('vacancies')->nullable()->default(1);
            $table->string('employment_type', 30)->nullable();
            $table->string('experience_level', 40)->nullable();
            $table->string('salary_period', 15)->nullable();
            $table->string('education', 255)->nullable();
            $table->text('required_skills')->nullable();            // JSON array
            $table->text('preferred_qualifications')->nullable();
            $table->date('application_deadline')->nullable();
            $table->boolean('allow_cv')->default(true);
            $table->unsignedInteger('application_limit')->nullable();
            $table->unsignedTinyInteger('min_pass_score')->nullable();   // percent
            $table->boolean('auto_screen')->default(false);
        });

        // benefits and perks were capped at 255 characters
        DB::statement('ALTER TABLE `jobposting` MODIFY `benefits` TEXT NULL');

        Schema::table('applied_questions', function (Blueprint $table) {
            $table->string('question_type', 20)->nullable();          // yes_no | multiple_choice | short_text | long_text | numeric
            $table->text('options')->nullable();                      // JSON array, multiple choice only
            $table->boolean('is_required')->default(true);
            $table->string('correct_answer', 255)->nullable();        // objective questions only
            $table->unsignedInteger('sort_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('applied_questions', function (Blueprint $table) {
            $table->dropColumn(['question_type', 'options', 'is_required', 'correct_answer', 'sort_order']);
        });

        DB::statement('ALTER TABLE `jobposting` MODIFY `benefits` VARCHAR(255) NULL');

        Schema::table('jobposting', function (Blueprint $table) {
            $table->dropColumn(['vacancies', 'employment_type', 'experience_level', 'salary_period', 'education',
                'required_skills', 'preferred_qualifications', 'application_deadline', 'allow_cv',
                'application_limit', 'min_pass_score', 'auto_screen']);
        });
    }
};
