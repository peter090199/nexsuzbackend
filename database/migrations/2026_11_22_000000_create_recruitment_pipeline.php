<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recruitment pipeline: AI-assisted candidate evaluation, status history, notes and interviews.
 *
 * Additive only. The existing applied_jobs / applied_questions / applied_resumes tables are untouched.
 *
 *  applied_answers             each applicant's own answers. Until now an answer was written onto the question row
 *                              itself, so the next applicant overwrote the previous one. (The model already existed;
 *                              the table did not.)
 *  application_status_history  every status change: who, when, why (rejections need a reason).
 *  candidate_notes             internal recruiter notes.
 *  candidate_evaluations       one row per evaluation run, so the history is kept (scores, explanations, criteria and
 *                              the provider / model that produced them).
 *  evaluation_overrides        every time a recruiter changes an AI score, with the reason.
 *  evaluation_settings         scoring weights, thresholds, passing score and job-specific criteria
 *                              (transNo NULL = the global defaults).
 *  interviews                  scheduled interviews and their feedback.
 *
 * InnoDB on purpose (this database defaults to MyISAM, which has no transactions).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applied_answers', function (Blueprint $t) {
            $t->engine = 'InnoDB';
            $t->id();
            $t->unsignedBigInteger('applied_id')->index();
            $t->unsignedBigInteger('question_id');
            $t->string('transNo', 50)->index();
            $t->string('code', 100);                        // applicant
            $t->text('answer_text')->nullable();
            $t->timestamps();
            $t->unique(['applied_id', 'question_id']);
        });

        Schema::create('application_status_history', function (Blueprint $t) {
            $t->engine = 'InnoDB';
            $t->id();
            $t->unsignedBigInteger('applied_id')->index();
            $t->string('transNo', 50)->index();
            $t->string('from_status', 20)->nullable();
            $t->string('to_status', 20);
            $t->text('reason')->nullable();                 // required for rejections
            $t->string('source', 20)->default('recruiter'); // recruiter | auto_screen | applicant | system
            $t->string('changed_by', 100)->nullable();      // users.code
            $t->string('changed_by_name', 255)->nullable();
            $t->text('ai_snapshot')->nullable();            // JSON: the AI recommendation at decision time (advisory only)
            $t->boolean('notified')->default(false);
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('candidate_notes', function (Blueprint $t) {
            $t->engine = 'InnoDB';
            $t->id();
            $t->unsignedBigInteger('applied_id')->index();
            $t->text('note');
            $t->string('created_by', 100);
            $t->string('author_name', 255)->nullable();
            $t->timestamps();
        });

        Schema::create('candidate_evaluations', function (Blueprint $t) {
            $t->engine = 'InnoDB';
            $t->id();
            $t->unsignedBigInteger('applied_id')->index();
            $t->string('transNo', 50)->index();
            $t->unsignedTinyInteger('overall_score');
            $t->unsignedTinyInteger('final_score')->nullable();   // after recruiter overrides
            $t->string('recommendation', 20);                     // strong | qualified | review | low
            $t->text('category_scores');                          // JSON {education, experience, skills, answers, competencies}
            $t->text('strengths')->nullable();                    // JSON
            $t->text('weaknesses')->nullable();                   // JSON
            $t->text('matched')->nullable();                      // JSON
            $t->text('missing')->nullable();                      // JSON
            $t->longText('answer_evals')->nullable();             // JSON, one entry per screening question
            $t->text('explanation')->nullable();
            $t->text('interview_questions')->nullable();          // JSON
            $t->string('provider', 30);                           // anthropic | local
            $t->string('model', 80)->nullable();
            $t->string('version', 20)->default('1');
            $t->text('weights');                                  // JSON used for this run
            $t->text('criteria')->nullable();                     // JSON snapshot of the job criteria used
            $t->text('thresholds')->nullable();                   // JSON
            $t->string('status', 12)->default('ai');              // ai | reviewed
            $t->string('evaluated_by', 100)->nullable();
            $t->string('reviewed_by', 100)->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('evaluation_overrides', function (Blueprint $t) {
            $t->engine = 'InnoDB';
            $t->id();
            $t->unsignedBigInteger('evaluation_id')->index();
            $t->unsignedBigInteger('applied_id')->index();
            $t->unsignedBigInteger('question_id')->nullable();    // NULL = the overall score
            $t->unsignedTinyInteger('ai_score');
            $t->unsignedTinyInteger('new_score');
            $t->string('reason', 500);
            $t->string('changed_by', 100);
            $t->string('changed_by_name', 255)->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('evaluation_settings', function (Blueprint $t) {
            $t->engine = 'InnoDB';
            $t->id();
            $t->string('transNo', 50)->nullable()->unique();      // NULL = global defaults
            $t->text('weights');                                  // JSON
            $t->text('thresholds');                               // JSON {strong, qualified, review}
            $t->unsignedTinyInteger('passing_score')->default(70);
            $t->text('competencies')->nullable();                 // job-specific criteria, one per line
            $t->string('updated_by', 100)->nullable();
            $t->timestamps();
        });

        Schema::create('interviews', function (Blueprint $t) {
            $t->engine = 'InnoDB';
            $t->id();
            $t->unsignedBigInteger('applied_id')->index();
            $t->string('transNo', 50)->index();
            $t->dateTime('scheduled_at');
            $t->unsignedSmallInteger('duration_min')->default(30);
            $t->string('type', 10);                               // online | onsite
            $t->string('location', 255)->nullable();              // onsite address
            $t->string('meeting_link', 500)->nullable();          // online link
            $t->string('interviewer_name', 120);
            $t->string('interviewer_code', 100)->nullable();
            $t->string('status', 12)->default('scheduled');       // scheduled | completed | cancelled
            $t->text('feedback')->nullable();
            $t->unsignedTinyInteger('score')->nullable();         // 0-100
            $t->string('result', 10)->nullable();                 // pass | hold | fail
            $t->text('notes')->nullable();
            $t->string('created_by', 100);
            $t->timestamps();
        });

        // sensible global defaults
        DB::table('evaluation_settings')->insert([
            'transNo'       => null,
            'weights'       => json_encode(['education' => 15, 'experience' => 25, 'skills' => 30, 'answers' => 20, 'competencies' => 10]),
            'thresholds'    => json_encode(['strong' => 85, 'qualified' => 70, 'review' => 50]),
            'passing_score' => 70,
            'competencies'  => null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // Answers were stored on the question row, so only the LAST applicant's answers still exist. Copy them to the
        // new table ONLY where that is certain: the job has exactly one applicant.
        $single = DB::table('applied_jobs')->select('transNo', DB::raw('MIN(applied_id) as applied_id'), DB::raw('MIN(code) as code'))
            ->groupBy('transNo')->havingRaw('COUNT(*) = 1')->get();
        foreach ($single as $j) {
            $rows = DB::table('applied_questions')->where('transNo', $j->transNo)
                ->whereNotNull('answer_text')->where('answer_text', '<>', '')->get(['question_id', 'answer_text']);
            foreach ($rows as $r) {
                DB::table('applied_answers')->insertOrIgnore([
                    'applied_id' => $j->applied_id, 'question_id' => $r->question_id, 'transNo' => $j->transNo,
                    'code' => $j->code, 'answer_text' => $r->answer_text, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        // the history starts with where every application is today
        foreach (DB::table('applied_jobs')->get(['applied_id', 'transNo', 'applied_status', 'code', 'fullname', 'created_at']) as $a) {
            DB::table('application_status_history')->insert([
                'applied_id' => $a->applied_id, 'transNo' => $a->transNo, 'from_status' => null,
                'to_status' => $a->applied_status ?: 'applied_active', 'reason' => 'Application received',
                'source' => 'applicant', 'changed_by' => $a->code, 'changed_by_name' => $a->fullname,
                'created_at' => $a->created_at ?: now(),
            ]);
        }
    }

    public function down(): void
    {
        foreach (['interviews', 'evaluation_settings', 'evaluation_overrides', 'candidate_evaluations',
                  'candidate_notes', 'application_status_history', 'applied_answers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
