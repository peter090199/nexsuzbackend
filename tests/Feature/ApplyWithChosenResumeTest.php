<?php

namespace Tests\Feature;

use App\Helpers\StoredCv;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Apply Job: the applicant keeps several saved CVs (Settings > Resume / CV) and picks exactly ONE for each application.
 * The application stores a reference to that saved CV and uses its own file (nothing is uploaded or copied again); the CV must be the
 * caller's own PDF. Fake private disk, rolled-back transactions.
 */
class ApplyWithChosenResumeTest extends TestCase
{
    use DatabaseTransactions;

    private User $seeker;
    private User $other;
    private User $master;
    private int $base;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        static $next = 920000;
        $this->base = $next += random_int(10, 50);
        foreach (['seeker' => 'DEF-USERS', 'other' => 'DEF-USERS', 'master' => 'DEF-MASTERADMIN'] as $k => $role) {
            $code = $this->base + array_search($k, ['seeker', 'other', 'master']);
            $this->$k = User::forceCreate(['fname' => $k, 'lname' => 'T', 'fullname' => "$k T", 'role_code' => $role, 'email' => "$k$code@example.test",
                'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']);
        }
        $this->as($this->seeker);
    }

    private function as(User $u): void { $this->app['auth']->forgetGuards(); Sanctum::actingAs(User::find($u->id)); }

    private function job(int $n, array $over = []): string
    {
        $transNo = "ZZ-{$this->base}-{$n}";
        DB::table('jobposting')->insert($over + [
            'transNo' => $transNo, 'code' => $this->master->code, 'role_code' => 'DEF-MASTERADMIN', 'job_name' => "ZZ Job $n",
            'job_position' => 'Dev', 'job_description' => 'x', 'job_about' => 'x', 'qualification' => 'x', 'work_type' => 'Full-time',
            'fullname' => 'Owner', 'recordstatus' => 'active', 'allow_cv' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $transNo;
    }

    /** a saved CV, through the real endpoint; returns its id */
    private function save(string $name, string $mime = 'application/pdf'): int
    {
        return $this->post('/api/user-resumes', ['resume' => UploadedFile::fake()->create($name, 100, $mime)], ['Accept' => 'application/json'])
            ->assertStatus(201)->json('id');
    }

    private function apply(string $transNo, array $extra = [])
    {
        return $this->post('/api/saveAppliedJob', $extra + [
            'job_name' => 'ZZ Job', 'email' => $this->seeker->email, 'country_code' => '+63', 'phone_number' => '9171234567', 'transNo' => $transNo,
        ], ['Accept' => 'application/json']);
    }

    private function sent(string $transNo): array
    {
        return DB::table('applied_resumes')->where('transNo', $transNo)->where('code', $this->seeker->code)->get()->all();
    }

    private function files(): int { return count(Storage::disk('local')->allFiles("uploads/{$this->seeker->code}")); }

    // ------------------------------------------------------------------ choosing one of several CVs

    public function test_each_application_is_sent_with_the_one_cv_chosen_for_it(): void
    {
        $a = $this->save('default.pdf');            // the first one saved is the default
        $b = $this->save('designer.pdf');
        $c = $this->save('manager.pdf');
        $this->assertSame(3, $this->files());
        $path = fn (int $id) => DB::table('user_resumes')->where('id', $id)->value('path');

        [$j1, $j2, $j3] = [$this->job(1), $this->job(2), $this->job(3)];
        $this->apply($j1, ['resume_id' => $b])->assertStatus(201);
        $this->apply($j2, ['resume_id' => $c])->assertStatus(201);
        $this->apply($j3, ['resume_id' => $a])->assertStatus(201);

        foreach ([$j1 => $b, $j2 => $c, $j3 => $a] as $job => $expected) {
            $rows = $this->sent($job);
            $this->assertCount(1, $rows, "exactly one CV on $job");
            $this->assertSame($expected, (int) $rows[0]->user_resume_id);
            $this->assertSame($path($expected), $rows[0]->resume_pdf, 'the saved CV\'s own file');
        }
        $this->assertSame(3, $this->files(), 'selecting an existing CV writes no new file');
    }

    public function test_a_cv_other_than_the_default_can_be_chosen(): void
    {
        $default = $this->save('default.pdf');
        $other = $this->save('other.pdf');
        $this->assertTrue((bool) DB::table('user_resumes')->where('id', $default)->value('is_default'));

        $this->apply($this->job(1), ['resume_id' => $other])->assertStatus(201);
        $this->assertSame($other, (int) $this->sent("ZZ-{$this->base}-1")[0]->user_resume_id);
        $this->assertTrue((bool) DB::table('user_resumes')->where('id', $default)->value('is_default'), 'choosing for one application never changes the default');
    }

    public function test_the_same_cv_can_be_used_for_many_applications_and_the_older_default_call_still_works(): void
    {
        $a = $this->save('one.pdf');
        $this->apply($this->job(1), ['resume_id' => $a])->assertStatus(201);
        $this->apply($this->job(2), ['use_default_resume' => 1])->assertStatus(201);
        $this->assertSame($a, (int) $this->sent("ZZ-{$this->base}-2")[0]->user_resume_id);
        $this->assertSame(1, $this->files());
    }

    // ------------------------------------------------------------------ it must be the applicant's own CV

    public function test_a_cv_that_belongs_to_somebody_else_is_refused_and_nothing_is_saved(): void
    {
        $this->as($this->other);
        $foreign = $this->save('theirs.pdf');
        $this->as($this->seeker);
        $job = $this->job(1);

        $this->apply($job, ['resume_id' => $foreign])->assertStatus(422)->assertJsonValidationErrors('resume_id')
            ->assertJsonPath('errors.resume_id.0', 'Choose one of your own saved CVs.');
        $this->assertSame(0, DB::table('applied_jobs')->where('transNo', $job)->count());
        $this->assertSame([], $this->sent($job));
    }

    public function test_a_cv_that_does_not_exist_gets_the_same_answer_as_somebody_elses(): void
    {
        $job = $this->job(1);
        $this->apply($job, ['resume_id' => 99999999])->assertStatus(422)->assertJsonPath('errors.resume_id.0', 'Choose one of your own saved CVs.');
        $this->apply($job, ['resume_id' => 'abc'])->assertStatus(422)->assertJsonPath('errors.resume_id.0', 'Choose one of your own saved CVs.');
        $this->assertSame(0, DB::table('applied_jobs')->where('transNo', $job)->count());
    }

    // ------------------------------------------------------------------ exactly one, and a usable one

    public function test_a_saved_cv_and_a_new_upload_cannot_be_sent_together(): void
    {
        $id = $this->save('saved.pdf');
        $job = $this->job(1);
        $this->apply($job, ['resume_id' => $id, 'resume_pdf' => UploadedFile::fake()->create('new.pdf', 50, 'application/pdf')])
            ->assertStatus(422)->assertJsonValidationErrors('resume_id');
        $this->assertSame(0, DB::table('applied_jobs')->where('transNo', $job)->count());
        $this->assertSame(1, $this->files(), 'the refused upload was not stored');
    }

    public function test_a_new_pdf_alone_still_works_and_is_stored_once(): void
    {
        $this->save('saved.pdf');
        $job = $this->job(1);
        $this->apply($job, ['resume_pdf' => UploadedFile::fake()->create('fresh.pdf', 50, 'application/pdf')])->assertStatus(201);
        $row = $this->sent($job)[0];
        $this->assertNull($row->user_resume_id, 'an upload made on the Apply page has no saved-CV reference');
        $this->assertStringContainsString('AppliedJobsResume', $row->resume_pdf);
        $this->assertSame(2, $this->files());
    }

    public function test_no_cv_chosen_is_refused_when_the_job_needs_one(): void
    {
        $this->save('saved.pdf');
        $job = $this->job(1);
        $this->apply($job)->assertStatus(422)->assertJsonPath('errors.resume_pdf.0', 'Select one of your saved CVs or upload a PDF.');
        $this->assertSame(0, DB::table('applied_jobs')->where('transNo', $job)->count());
    }

    public function test_a_word_cv_cannot_be_sent_even_when_chosen_by_id(): void
    {
        $doc = $this->save('cv.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $job = $this->job(1);
        $this->apply($job, ['resume_id' => $doc])->assertStatus(422)->assertJsonValidationErrors('resume_id');
        $this->assertSame(0, DB::table('applied_jobs')->where('transNo', $job)->count());
    }

    public function test_a_cv_whose_file_is_gone_cannot_be_sent(): void
    {
        $id = $this->save('lost.pdf');
        Storage::disk('local')->delete(DB::table('user_resumes')->where('id', $id)->value('path'));
        $job = $this->job(1);
        $this->apply($job, ['resume_id' => $id])->assertStatus(422)->assertJsonValidationErrors('resume_id');
        $this->assertSame(0, DB::table('applied_jobs')->where('transNo', $job)->count());
    }

    public function test_a_job_that_does_not_ask_for_a_cv_takes_none_and_needs_none(): void
    {
        $id = $this->save('saved.pdf');
        $job = $this->job(1, ['allow_cv' => 0]);
        $this->apply($job)->assertStatus(201);                                   // nothing needed
        $job2 = $this->job(2, ['allow_cv' => 0]);
        $this->apply($job2, ['resume_id' => $id])->assertStatus(201);            // nothing attached
        $this->assertSame([], $this->sent($job));
        $this->assertSame([], $this->sent($job2));
    }

    public function test_a_second_application_to_the_same_job_changes_nothing(): void
    {
        $a = $this->save('one.pdf'); $b = $this->save('two.pdf');
        $job = $this->job(1);
        $this->apply($job, ['resume_id' => $a])->assertStatus(201);
        $this->apply($job, ['resume_id' => $b])->assertStatus(409);
        $rows = $this->sent($job);
        $this->assertCount(1, $rows);
        $this->assertSame($a, (int) $rows[0]->user_resume_id);
    }

    // ------------------------------------------------------------------ the file is kept while an application uses it

    public function test_deleting_a_saved_cv_keeps_the_file_the_recruiter_received_and_says_so(): void
    {
        $id = $this->save('sent.pdf');
        $unused = $this->save('unused.pdf');
        $this->apply($this->job(1), ['resume_id' => $id])->assertStatus(201);
        $sentPath = DB::table('user_resumes')->where('id', $id)->value('path');
        $unusedPath = DB::table('user_resumes')->where('id', $unused)->value('path');

        $r = $this->deleteJson("/api/user-resumes/{$id}")->assertOk();
        $this->assertStringContainsString('1 application', $r->json('message'));
        Storage::disk('local')->assertExists($sentPath);
        $this->assertSame($id, (int) $this->sent("ZZ-{$this->base}-1")[0]->user_resume_id, 'the application keeps its record');

        $this->deleteJson("/api/user-resumes/{$unused}")->assertOk();
        Storage::disk('local')->assertMissing($unusedPath);                      // nothing used it: the file goes with it
    }

    public function test_a_recruiter_deleting_an_application_never_removes_the_applicants_saved_cv(): void
    {
        $id = $this->save('keep.pdf');
        $path = DB::table('user_resumes')->where('id', $id)->value('path');
        $job = $this->job(1);
        $this->apply($job, ['resume_id' => $id])->assertStatus(201);
        $appId = DB::table('applied_jobs')->where('transNo', $job)->value('applied_id');

        $this->as($this->master);                                                // the job's owner
        $this->deleteJson("/api/recruitment/applications/{$appId}")->assertOk();

        $this->assertSame(0, DB::table('applied_jobs')->where('transNo', $job)->count());
        Storage::disk('local')->assertExists($path);                             // still the applicant's saved CV
        $this->assertTrue(DB::table('user_resumes')->where('id', $id)->exists());
    }

    public function test_the_file_goes_when_nothing_uses_it_any_more(): void
    {
        $id = $this->save('once.pdf');
        $path = DB::table('user_resumes')->where('id', $id)->value('path');
        $job = $this->job(1);
        $this->apply($job, ['resume_id' => $id])->assertStatus(201);
        $appId = DB::table('applied_jobs')->where('transNo', $job)->value('applied_id');

        $this->deleteJson("/api/user-resumes/{$id}")->assertOk();                // the saved CV goes, the application still uses the file
        Storage::disk('local')->assertExists($path);
        $this->assertTrue(StoredCv::inUse($path));

        $this->as($this->master);
        $this->deleteJson("/api/recruitment/applications/{$appId}")->assertOk();  // now nothing uses it
        Storage::disk('local')->assertMissing($path);
        $this->assertFalse(StoredCv::inUse($path));
    }

    // ------------------------------------------------------------------ viewing a CV (the Apply page's Preview / Download)

    public function test_the_owner_can_open_each_saved_cv_and_nobody_else_can(): void
    {
        $a = $this->save('first.pdf'); $b = $this->save('second.pdf');
        foreach ([$a => 'first.pdf', $b => 'second.pdf'] as $id => $name) {
            $r = $this->get("/api/user-resumes/{$id}/file");
            $r->assertOk();
            $this->assertStringContainsString('application/pdf', $r->headers->get('Content-Type'));
            $this->assertStringContainsString($name, $r->headers->get('Content-Disposition'));
        }
        $this->as($this->other);
        $this->get("/api/user-resumes/{$a}/file")->assertStatus(404);
        $this->assertSame([], $this->getJson('/api/user-resumes')->json('data'), 'and a list shows only the caller\'s own');
    }

    public function test_the_list_the_apply_page_shows_has_name_date_size_type_and_default(): void
    {
        $this->save('first.pdf');
        $this->save('second.pdf');
        $list = $this->getJson('/api/user-resumes')->assertOk()->json('data');
        $this->assertCount(2, $list);
        foreach ($list as $r) {
            foreach (['id', 'name', 'size', 'is_default', 'type', 'created_at'] as $k) { $this->assertArrayHasKey($k, $r, $k); }
            $this->assertSame('pdf', $r['type']);
        }
        $this->assertSame(1, collect($list)->where('is_default', true)->count());
        $this->assertSame('first.pdf', collect($list)->firstWhere('is_default', true)['name']);
    }

    // ------------------------------------------------------------------ country code and phone number must match

    public function test_the_country_code_and_the_number_are_accepted_in_their_usual_forms(): void
    {
        $id = $this->save('a.pdf');
        foreach ([['63', '9171234567'], ['+63', '917 123 4567'], ['1-242', '555-1234'], ['44', '(7911) 123456']] as $i => [$cc, $no]) {
            $this->apply($this->job($i + 1), ['resume_id' => $id, 'country_code' => $cc, 'phone_number' => $no])->assertStatus(201);
        }
        $this->assertSame(4, DB::table('applied_resumes')->where('code', $this->seeker->code)->count());
    }

    public function test_a_number_that_still_carries_its_country_code_is_refused(): void
    {
        $id = $this->save('a.pdf');
        $job = $this->job(1);
        $this->apply($job, ['resume_id' => $id, 'country_code' => '63', 'phone_number' => '+639171234567'])
            ->assertStatus(422)->assertJsonValidationErrors('phone_number');
        $this->assertCount(0, $this->sent($job));
    }

    public function test_a_bad_country_code_or_number_is_refused(): void
    {
        $id = $this->save('a.pdf');
        $job = $this->job(1);
        $bad = [
            ['country_code' => 'PH', 'phone_number' => '9171234567', 'field' => 'country_code'],
            ['country_code' => '', 'phone_number' => '9171234567', 'field' => 'country_code'],
            ['country_code' => '63', 'phone_number' => 'call me', 'field' => 'phone_number'],
            ['country_code' => '63', 'phone_number' => '123', 'field' => 'phone_number'],
            ['country_code' => '63', 'phone_number' => '1234567890123456', 'field' => 'phone_number'],
        ];
        foreach ($bad as $b) {
            $this->apply($job, ['resume_id' => $id, 'country_code' => $b['country_code'], 'phone_number' => $b['phone_number']])
                ->assertStatus(422)->assertJsonValidationErrors($b['field']);
        }
        $this->assertCount(0, $this->sent($job));
    }
}
