<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Job posting templates & themes (managed by DEF-MASTERADMIN, chosen by job posters).
 *
 *  job_templates      one row per template: name, theme (colours, font, buttons, layout) and the ordered job
 *                     sections. Soft deleted, so a job that was posted with a template keeps looking the same.
 *  jobposting         gets a nullable template_id. Existing jobs have NULL and render exactly as before.
 *
 * Additive only. A default "Classic" template is seeded so there is always something to pick.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('description', 255)->nullable();
            $table->text('theme');                         // JSON
            $table->text('sections');                      // JSON: ordered [{key,label,enabled}]
            $table->boolean('is_default')->default(false);
            $table->string('status', 12)->default('draft'); // draft | published
            $table->string('created_by', 50)->nullable();   // users.code
            $table->string('updated_by', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'deleted_at']);
        });

        Schema::table('jobposting', function (Blueprint $table) {
            $table->unsignedBigInteger('template_id')->nullable()->index();
        });

        $now = now();
        DB::table('job_templates')->insert([
            'name'        => 'Classic',
            'description' => 'The standard NEXSUZ job layout.',
            'theme'       => json_encode([
                'preset' => 'classic', 'primary' => '#0a66c2', 'accent' => '#7a5af8', 'background' => '#f3f2ef',
                'surface' => '#ffffff', 'text' => '#1b2a4a', 'font' => 'verdana', 'radius' => 14,
                'buttonStyle' => 'filled', 'buttonShape' => 'pill', 'headerStyle' => 'gradient',
                'backgroundStyle' => 'solid', 'layout' => 'single',
            ]),
            'sections'    => json_encode([
                ['key' => 'summary', 'label' => 'About the job', 'enabled' => true],
                ['key' => 'responsibilities', 'label' => 'Job description', 'enabled' => true],
                ['key' => 'qualifications', 'label' => 'Qualifications', 'enabled' => true],
                ['key' => 'education', 'label' => 'Education', 'enabled' => true],
                ['key' => 'skills', 'label' => 'Required skills', 'enabled' => true],
                ['key' => 'benefits', 'label' => 'Benefits and perks', 'enabled' => true],
                ['key' => 'questions', 'label' => 'Screening questions', 'enabled' => true],
            ]),
            'is_default'  => true,
            'status'      => 'published',
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
    }

    public function down(): void
    {
        Schema::table('jobposting', function (Blueprint $table) {
            $table->dropIndex(['template_id']);
            $table->dropColumn('template_id');
        });
        Schema::dropIfExists('job_templates');
    }
};
