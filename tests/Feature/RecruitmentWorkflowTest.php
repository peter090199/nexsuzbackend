<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Recruitment\EvaluationEngine;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Concerns\BuildsCvFiles;
use Tests\TestCase;

/**
 * Apply -> automatic evaluation -> stored score -> recruiter review -> status change -> applicant tracking, through the
 * real endpoints, with a real PDF. Synthetic accounts; rows roll back. The rules engine is forced (no external calls).
 */
class RecruitmentWorkflowTest extends TestCase
{
    use DatabaseTransactions, BuildsCvFiles;

    private User $recruiter;
    private User $seeker;
    private string $transNo;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();
        config(['recruitment.ai.provider' => 'local']);

        $this->recruiter = $this->account('DEF-CLIENT');
        $this->seeker = $this->account('DEF-USERS');
        $this->transNo = 'ZZ-W' . $this->seeker->code;
        DB::table('jobposting')->insert([
            'transNo' => $this->transNo, 'code' => $this->recruiter->code, 'role_code' => 'DEF-CLIENT', 'job_name' => 'PHP Developer',
            'job_position' => 'Developer', 'job_description' => 'Build web applications with PHP and Laravel', 'job_about' => 'Web team',
            'qualification' => "Experience building web applications\nAWS certification required", 'work_type' => 'Full-time',
            'education' => "Bachelor's degree", 'required_skills' => json_encode(['PHP', 'Laravel', 'Kubernetes']),
            'fullname' => 'Rec Ruiter', 'recordstatus' => 'active', 'allow_cv' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        $this->cleanCvFiles();
        parent::tearDown();
    }

    private function account(string $role): User
    {
        static $next = 898000;
        $code = $next += random_int(2, 40);

        return User::forceCreate(['fname' => 'Wf', 'lname' => 'Tester', 'fullname' => 'Wf Tester', 'role_code' => $role,
            'email' => "wf{$code}@example.test", 'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']);
    }

    private function actingAsUser(User $u): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs(User::find($u->id));
    }

    private function apply(string $cvText)
    {
        $this->actingAsUser($this->seeker);

        return $this->post('/api/saveAppliedJob', [
            'job_name' => 'PHP Developer', 'email' => $this->seeker->email, 'country_code' => '+63', 'phone_number' => '9171234567',
            'transNo' => $this->transNo, 'resume_pdf' => $this->pdf($cvText),
        ], ['Accept' => 'application/json']);
    }

    private function appliedId(): int
    {
        return (int) DB::table('applied_jobs')->where('transNo', $this->transNo)->value('applied_id');
    }

    public function test_applying_scores_the_application_and_the_recruiter_takes_it_through_to_the_applicant(): void
    {
        DB::table('userskills')->insert(['code' => $this->seeker->code, 'transNo' => 1, 'skills' => 'PHP, Laravel']);
        DB::table('useremploymentrecords')->insert(['code' => $this->seeker->code, 'transNo' => 1, 'company_name' => 'Acme',
            'position' => 'PHP Developer', 'job_description' => 'Building web applications with Laravel']);
        DB::table('usereducations')->insert(['code' => $this->seeker->code, 'transNo' => 1, 'highest_education' => 'Bachelor of Science', 'school_name' => 'CPC']);

        $this->apply("Wf Tester\nSkills\nPHP, Laravel")->assertStatus(201);
        $id = $this->appliedId();

        $row = DB::table('candidate_evaluations')->where('applied_id', $id)->get();
        $this->assertCount(1, $row, 'applying stores exactly one evaluation');
        $this->assertSame('local', $row[0]->provider);

        // the same job can't be applied to twice, and no second evaluation appears
        $this->apply("Skills\nPHP")->assertStatus(409);
        $this->assertSame(1, DB::table('candidate_evaluations')->where('applied_id', $id)->count());

        // another recruiter and the applicant cannot read the review
        $this->actingAsUser($this->account('DEF-CLIENT'));
        $this->getJson("/api/recruitment/applications/{$id}")->assertStatus(403);
        $this->actingAsUser($this->seeker);
        $this->getJson("/api/recruitment/applications/{$id}")->assertStatus(403);

        $this->actingAsUser($this->recruiter);
        $review = $this->getJson("/api/recruitment/applications/{$id}")->assertOk()->json();
        $ev = $review['evaluation'];
        $this->assertSame(['skills' => 35, 'experience' => 25, 'education' => 15, 'requirements' => 15, 'certifications' => 10], $ev['weights']);
        $this->assertSame(EvaluationEngine::recommend($ev['score']), $ev['recommendation']);
        $this->assertSame(67, $ev['categories']['skills']['score']);            // 2 of the 3 required skills
        $this->assertSame(100, $ev['categories']['education']['score']);
        $this->assertContains('Skill: Kubernetes', $ev['missing']);
        $this->assertContains('certifications', $ev['incomplete']);              // none listed: neutral, not zero
        $this->assertSame(50, $ev['categories']['certifications']['score']);
        $this->assertNotEmpty($review['resumes']);

        $pdf = $this->get("/api/recruitment/applications/{$id}/resume")->assertOk();
        $this->assertStringStartsWith('%PDF', file_get_contents($pdf->baseResponse->getFile()->getPathname()));

        $list = $this->getJson("/api/recruitment/jobs/{$this->transNo}/candidates")->assertOk()->json('data.0');
        $this->assertSame($ev['score'], $list['ai_score']);
        $this->assertSame($ev['recommendation'], $list['ai_recommendation']);
        $this->assertSame('applied_active', $list['applied_status'], 'scoring never decides anything');
        $this->getJson('/api/recruitment/reports')->assertOk()->assertJsonPath('success', true);

        $this->postJson("/api/recruitment/applications/{$id}/status", ['status' => 'review'])->assertOk();

        $this->actingAsUser($this->seeker);
        $mine = $this->getJson("/api/getAppliedJobByUsers/{$this->transNo}")->assertOk()->json('data');
        $this->assertSame('review', collect($mine)->firstWhere('applied_id', $id)['applied_status'] ?? null);
    }

    public function test_an_empty_profile_is_read_from_the_pdf_and_is_not_marked_unqualified(): void
    {
        $this->apply("Wf Tester\nSkills\nPHP, Laravel, Kubernetes\nCertifications\nAWS Certified Developer")->assertStatus(201);

        $ev = EvaluationEngine::format(DB::table('candidate_evaluations')->where('applied_id', $this->appliedId())->first());
        $this->assertSame(100, $ev['categories']['skills']['score'], 'skills come from the uploaded PDF');
        $this->assertSame(100, $ev['categories']['certifications']['score']);
        $this->assertSame(50, $ev['categories']['experience']['score']);
        $this->assertSame(50, $ev['categories']['education']['score']);
        $this->assertContains('experience', $ev['incomplete']);
        $this->assertStringContainsString('uploaded CV', $ev['explanation']);
    }

    public function test_score_levels(): void
    {
        foreach ([100 => 'excellent', 90 => 'excellent', 89 => 'strong', 80 => 'strong', 79 => 'good', 70 => 'good', 69 => 'moderate', 60 => 'moderate', 59 => 'low', 0 => 'low'] as $score => $level) {
            $this->assertSame($level, EvaluationEngine::recommend($score), "score {$score}");
        }
    }

    public function test_the_recruiter_can_save_weights_for_the_five_criteria(): void
    {
        $this->actingAsUser($this->recruiter);
        $weights = ['skills' => 40, 'experience' => 20, 'education' => 20, 'requirements' => 10, 'certifications' => 10];
        $this->putJson('/api/recruitment/evaluation-settings', ['transNo' => $this->transNo, 'weights' => $weights, 'passing_score' => 65])->assertOk();
        $this->assertSame($weights, EvaluationEngine::settings($this->transNo)['weights']);
    }
}
