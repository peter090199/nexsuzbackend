<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Apply Job + Settings > Resume / CV: with no file uploaded, the applicant's default resume is used.
 * The application gets its own COPY (deleting the account resume later never changes what the recruiter received).
 * Fake private disk, rolled-back transactions.
 */
class ApplyWithDefaultResumeTest extends TestCase
{
    use DatabaseTransactions;

    private User $seeker;
    private string $transNo;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        static $next = 930000;
        $code = $next += random_int(1, 40);
        $this->seeker = User::forceCreate([
            'fname' => 'Sam', 'lname' => 'Seeker', 'fullname' => 'Sam Seeker', 'role_code' => 'DEF-USERS',
            'email' => "sam{$code}@example.test", 'password' => bcrypt('secret-pass'),
            'status' => 'A', 'code' => $code, 'coverphoto' => '',
        ]);

        $this->transNo = 'ZZ-' . $code;
        DB::table('jobposting')->insert([
            'transNo' => $this->transNo, 'code' => $code + 1, 'role_code' => 'DEF-CLIENT', 'job_name' => 'ZZ Test Job',
            'job_position' => 'Dev', 'job_description' => 'x', 'job_about' => 'x', 'qualification' => 'x', 'work_type' => 'Full-time',
            'fullname' => 'Rec Ruiter', 'recordstatus' => 'active', 'allow_cv' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->app['auth']->forgetGuards();
        Sanctum::actingAs(User::find($this->seeker->id));
    }

    private function saveResume(string $name = 'default.pdf'): void
    {
        $this->post('/api/user-resumes', ['resume' => UploadedFile::fake()->create($name, 100, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertStatus(201);
    }

    private function apply(array $extra = [])
    {
        return $this->post('/api/saveAppliedJob', $extra + [
            'job_name' => 'ZZ Test Job', 'email' => $this->seeker->email, 'country_code' => '+63', 'phone_number' => '9171234567',
            'transNo' => $this->transNo,
        ], ['Accept' => 'application/json']);
    }

    public function test_the_default_resume_is_used_when_no_file_is_sent(): void
    {
        $this->saveResume();
        $defaultPath = DB::table('user_resumes')->where('code', $this->seeker->code)->value('path');

        $this->apply(['use_default_resume' => 1])->assertStatus(201)->assertJsonPath('success', true);

        $row = DB::table('applied_resumes')->where('transNo', $this->transNo)->where('code', $this->seeker->code)->first();
        $this->assertNotNull($row, 'the application has a resume');
        $this->assertNotSame($defaultPath, $row->resume_pdf, 'a copy, not the account file itself');
        Storage::disk('local')->assertExists($row->resume_pdf);
        Storage::disk('local')->assertExists($defaultPath);
    }

    public function test_deleting_the_account_resume_later_keeps_what_the_recruiter_received(): void
    {
        $this->saveResume();
        $id = DB::table('user_resumes')->where('code', $this->seeker->code)->value('id');
        $this->apply(['use_default_resume' => 1])->assertStatus(201);

        $this->deleteJson("/api/user-resumes/{$id}")->assertOk();

        $sent = DB::table('applied_resumes')->where('transNo', $this->transNo)->value('resume_pdf');
        Storage::disk('local')->assertExists($sent);
    }

    public function test_an_uploaded_file_wins_over_the_default(): void
    {
        $this->saveResume();

        $this->apply(['use_default_resume' => 1, 'resume_pdf' => UploadedFile::fake()->create('other.pdf', 50, 'application/pdf')])
            ->assertStatus(201);

        $this->assertSame(1, DB::table('applied_resumes')->where('transNo', $this->transNo)->count());
    }

    public function test_without_a_default_or_a_file_the_cv_is_still_required(): void
    {
        $this->apply(['use_default_resume' => 1])->assertStatus(422);

        $this->assertSame(0, DB::table('applied_jobs')->where('transNo', $this->transNo)->count());
    }

    public function test_a_word_file_as_default_is_never_sent_to_a_recruiter(): void
    {
        $this->post('/api/user-resumes', ['resume' => UploadedFile::fake()->create('cv.docx', 80, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')], ['Accept' => 'application/json'])
            ->assertStatus(201);

        // applications are PDF only: the Word default is not used, so a PDF is still required
        $this->apply(['use_default_resume' => 1])->assertStatus(422);
        $this->assertSame(0, DB::table('applied_jobs')->where('transNo', $this->transNo)->count());

        $this->apply(['resume_pdf' => UploadedFile::fake()->create('mine.pdf', 50, 'application/pdf')])->assertStatus(201);
    }

    public function test_the_default_is_not_used_unless_asked_for(): void
    {
        $this->saveResume();

        $this->apply()->assertStatus(422);
    }
}
