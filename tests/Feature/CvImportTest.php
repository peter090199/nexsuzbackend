<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\BuildsCvFiles;
use Tests\TestCase;

/**
 * Automatic CV / resume import for job seekers: upload -> extract (preview, saves nothing) -> the person edits and
 * approves -> import. Covers the extraction through the API, the field mapping, "never overwrite with empty",
 * duplicate prevention, validation, ownership, and that the profile API shows the result.
 * Fake private disk, rolled-back transactions (the resources rows are removed by hand).
 */
class CvImportTest extends TestCase
{
    use DatabaseTransactions;
    use BuildsCvFiles;

    /** @var int[] */
    private array $codes = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        DB::table('resources')->whereIn('code', $this->codes)->delete();
        $this->cleanCvFiles();
        parent::tearDown();
    }

    private function makeUser(string $role = 'DEF-USERS', array $profile = []): User
    {
        static $next = 900000;
        $code = $next += random_int(1, 40);
        $this->codes[] = $code;

        $u = User::forceCreate([
            'fname' => 'Sam', 'lname' => 'Seeker', 'fullname' => 'Sam Seeker', 'role_code' => $role,
            'email' => "sam{$code}@example.test", 'password' => bcrypt('secret-pass'),
            'status' => 'A', 'code' => $code, 'coverphoto' => '',
        ]);
        DB::table('resources')->insert([
            'code' => $code, 'fname' => 'Sam', 'lname' => 'Seeker', 'fullname' => 'Sam Seeker', 'email' => $u->email,
            'role_code' => $role, 'profession' => 'Tester', 'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($profile) {
            DB::table('userprofiles')->insert($profile + [
                'code' => $code, 'transNo' => (int) DB::table('userprofiles')->max('transNo') + 1, 'email' => $u->email,
                'contact_visibility' => 1, 'email_visibility' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $u;
    }

    private function as(User $u): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs(User::find($u->id));
    }

    /** upload a file as the signed-in job seeker; returns the resume id */
    private function upload($file): int
    {
        return (int) $this->post('/api/user-resumes', ['resume' => $file], ['Accept' => 'application/json'])->assertStatus(201)->json('id');
    }

    private function counts(int $code): array
    {
        $o = [];
        foreach (['usereducations', 'usercapabilities', 'userskills', 'usertrainings', 'userseminars', 'useremploymentrecords', 'usercertificates'] as $t) {
            $o[$t] = DB::table($t)->where('code', $code)->count();
        }

        return $o;
    }

    /** the whole extracted result, as the preview would send it back unchanged */
    private function approveAll(array $extracted, array $extra = []): array
    {
        $p = array_filter($extracted['profile'], fn ($v) => $v !== null);

        return ['profile' => $p] + array_intersect_key($extracted, array_flip(['education', 'languages', 'skills', 'training', 'seminar', 'employment', 'certificate'])) + $extra;
    }

    // ------------------------------------------------------------------ extract (the preview)

    public function test_extract_reads_the_uploaded_docx_and_saves_nothing(): void
    {
        $u = $this->makeUser();
        $this->as($u);
        $id = $this->upload($this->docx($this->sampleCv()));
        $before = $this->counts($u->code);

        $r = $this->postJson("/api/user-resumes/{$id}/extract")->assertOk()->assertJsonPath('success', true);

        $d = $r->json('data');
        $this->assertSame('Pedro', $d['profile']['fname']);
        $this->assertSame('pedro.yorpo@gmail.com', $d['profile']['email']);
        $this->assertCount(2, $d['employment']);
        $this->assertCount(2, $d['education']);
        $this->assertSame('docx', $d['resume']['type']);
        $this->assertSame($before, $this->counts($u->code), 'a preview never writes anything');
        $this->assertSame('Sam', DB::table('users')->where('id', $u->id)->value('fname'));
    }

    public function test_extract_reads_a_pdf_too(): void
    {
        $this->as($this->makeUser());
        $id = $this->upload($this->pdf($this->sampleCv()));

        $d = $this->postJson("/api/user-resumes/{$id}/extract")->assertOk()->json('data');

        $this->assertSame('Yorpo', $d['profile']['lname']);
        $this->assertNotEmpty($d['skills']);
    }

    public function test_extract_reads_a_canva_style_pdf_through_the_endpoint(): void
    {
        // a PDF with 2-byte glyph ids and a font table used to come back as 422 "scanned"
        $u = $this->makeUser();
        $this->as($u);
        $id = $this->upload($this->canvaPdf($this->sampleCv(), false, true, 'My Canva CV.pdf'));

        $r = $this->postJson("/api/user-resumes/{$id}/extract")->assertOk()->assertJsonPath('success', true);

        $d = $r->json('data');
        $this->assertSame(['Pedro', 'Yorpo', 'pedro.yorpo@gmail.com', 'Software Engineer'], [$d['profile']['fname'], $d['profile']['lname'], $d['profile']['email'], $d['profile']['profession']]);
        $this->assertCount(2, $d['employment']);
        $this->assertCount(2, $d['education']);
        $this->assertCount(7, $d['skills']);
        $this->assertSame('pdf', $d['resume']['type']);

        // and it imports like any other CV
        $this->postJson("/api/user-resumes/{$id}/import", $this->approveAll($d))->assertOk()->assertJsonPath('data.added.employment', 2);
    }

    public function test_extract_reads_a_pdf_with_object_streams_through_the_endpoint(): void
    {
        $this->as($this->makeUser());
        $id = $this->upload($this->canvaPdf($this->sampleCv(), true));

        $d = $this->postJson("/api/user-resumes/{$id}/extract")->assertOk()->json('data');

        $this->assertSame('Yorpo', $d['profile']['lname']);
        $this->assertCount(2, $d['education']);
    }

    public function test_the_preview_shows_what_is_on_the_profile_and_marks_items_already_saved(): void
    {
        $u = $this->makeUser('DEF-USERS', ['summary' => 'My old summary', 'contact_no' => '09170000000']);
        DB::table('userskills')->insert(['code' => $u->code, 'transNo' => 1, 'skills' => 'laravel', 'created_at' => now(), 'updated_at' => now()]);
        $this->as($u);
        $id = $this->upload($this->docx($this->sampleCv()));

        $d = $this->postJson("/api/user-resumes/{$id}/extract")->assertOk()->json('data');

        $this->assertSame('My old summary', $d['current']['summary']);
        $this->assertSame('Sam', $d['current']['fname']);
        $bySkill = array_column($d['skills'], 'exists', 'skills');
        $this->assertTrue($bySkill['Laravel'], '"laravel" is already saved: same skill whatever the case');
        $this->assertFalse($bySkill['Angular']);
    }

    public function test_an_unreadable_file_gives_a_clear_reason_not_an_error_page(): void
    {
        $this->as($this->makeUser());

        $scan = $this->upload($this->scannedPdf());
        $this->postJson("/api/user-resumes/{$scan}/extract")->assertStatus(422)->assertJsonPath('code', 'scanned')
            ->assertJsonPath('success', false);

        $broken = $this->upload($this->fakeDocx());
        $this->postJson("/api/user-resumes/{$broken}/extract")->assertStatus(422)->assertJsonPath('code', 'corrupt');

        $this->assertNotEmpty($this->postJson("/api/user-resumes/{$scan}/extract")->json('message'));
    }

    // ------------------------------------------------------------------ import: mapping and the profile

    public function test_import_writes_the_profile_fields_and_every_section(): void
    {
        $u = $this->makeUser();
        $this->as($u);
        $id = $this->upload($this->docx($this->sampleCv()));
        $d = $this->postJson("/api/user-resumes/{$id}/extract")->json('data');

        $r = $this->postJson("/api/user-resumes/{$id}/import", $this->approveAll($d))->assertOk()->assertJsonPath('success', true);

        $added = $r->json('data.added');
        $this->assertSame(['education' => 2, 'languages' => 2, 'skills' => 7, 'training' => 1, 'seminar' => 1, 'employment' => 2, 'certificate' => 2], $added);
        $this->assertSame([], $r->json('data.skipped'));

        $c = $this->counts($u->code);
        $this->assertSame([2, 2, 7, 1, 1, 2, 2], array_values($c));
        $edu = DB::table('usereducations')->where('code', $u->code)->orderBy('id')->first();
        $this->assertSame(['Bachelor of Science in Information Technology', 'Ateneo de Manila University', 2012, 2016, 'graduate'],
            [$edu->highest_education, $edu->school_name, (int) $edu->start_year, (int) $edu->end_year, $edu->status]);
        $job = DB::table('useremploymentrecords')->where('code', $u->code)->orderBy('id')->first();
        $this->assertSame(['Senior Software Engineer', 'Acme Technologies Inc.'], [$job->position, $job->company_name]);
        $this->assertNull($job->date_completed);
    }

    public function test_the_profile_api_shows_the_imported_data(): void
    {
        $u = $this->makeUser();
        $this->as($u);
        $id = $this->upload($this->docx($this->sampleCv()));
        $d = $this->postJson("/api/user-resumes/{$id}/extract")->json('data');
        $this->postJson("/api/user-resumes/{$id}/import", $this->approveAll($d))->assertOk();

        $p = $this->getJson("/api/profile/{$u->code}")->assertOk()->json('message');

        $this->assertSame('DEF-USERS', $p['role_code']);
        $this->assertSame(['Pedro', 'Yorpo', 'Software Engineer'], [$p['fname'], $p['lname'], $p['profession']]);
        $this->assertStringStartsWith('Software engineer with 6 years', $p['summary']);
        $this->assertSame('+63 917 123 4567', $p['contact_no']);
        $this->assertSame('1995-08-29', $p['date_birth']);
        $this->assertSame('Philippines', $p['home_country'], 'the profile used to return the date of birth here');
        $this->assertSame('Quezon City', $p['current_location']);
        $this->assertSame('Bulacan', $p['home_state']);
        $this->assertSame('Metro Manila', $p['current_state']);
        $this->assertCount(2, $p['lines']['education']);
        $this->assertCount(7, $p['lines']['skills']);
        $this->assertCount(2, $p['lines']['language']);
        $this->assertCount(2, $p['lines']['employment']);
        $this->assertCount(2, $p['lines']['certificate']);
        $this->assertCount(1, $p['lines']['training']);
        $this->assertCount(1, $p['lines']['seminar']);
        foreach (['success', 'message'] as $k) { $this->assertArrayHasKey($k, $this->getJson("/api/profile/{$u->code}")->json()); }
        foreach (['role_code', 'fname', 'lname', 'profession', 'summary', 'photo_pic', 'contact_no', 'lines', 'cv_available', 'cv_mode', 'resume'] as $k) {
            $this->assertArrayHasKey($k, $p, "the profile response keeps '{$k}'");
        }
        $this->assertSame('Pedro Yorpo', DB::table('users')->where('id', $u->id)->value('fullname'));
    }

    public function test_a_job_seeker_with_no_profile_row_gets_one(): void
    {
        $u = $this->makeUser();
        $this->as($u);
        $this->assertSame(0, DB::table('userprofiles')->where('code', $u->code)->count());
        $id = $this->upload($this->docx($this->sampleCv()));
        $d = $this->postJson("/api/user-resumes/{$id}/extract")->json('data');

        $this->postJson("/api/user-resumes/{$id}/import", $this->approveAll($d))->assertOk();

        $row = DB::table('userprofiles')->where('code', $u->code)->first();
        $this->assertNotNull($row);
        $this->assertSame('Quezon City', $row->current_location);
        $this->assertNull($row->photo_pic, 'no photo is invented');
    }

    public function test_the_imported_email_is_a_contact_email_and_never_the_sign_in_email(): void
    {
        $u = $this->makeUser('DEF-USERS', ['summary' => 'x']);
        $this->as($u);
        $id = $this->upload($this->docx($this->sampleCv()));
        $d = $this->postJson("/api/user-resumes/{$id}/extract")->json('data');

        $this->postJson("/api/user-resumes/{$id}/import", $this->approveAll($d))->assertOk();

        $this->assertSame($u->email, User::find($u->id)->email, 'the account can still sign in with its own email');
        $this->assertSame($u->email, DB::table('resources')->where('code', $u->code)->value('email'));
        $this->assertSame('pedro.yorpo@gmail.com', DB::table('userprofiles')->where('code', $u->code)->value('email'));
        $this->assertSame('pedro.yorpo@gmail.com', $this->getJson("/api/profile/{$u->code}")->json('message.email'));
    }

    // ------------------------------------------------------------------ the data rules

    public function test_empty_values_never_overwrite_what_is_saved(): void
    {
        $u = $this->makeUser('DEF-USERS', ['summary' => 'Keep this summary', 'contact_no' => '09171112222', 'current_location' => 'Cebu']);
        $this->as($u);
        $id = $this->upload($this->docx($this->sampleCv()));

        $this->postJson("/api/user-resumes/{$id}/import", [
            'profile' => ['summary' => '', 'contact_no' => null, 'current_location' => '   ', 'profession' => 'Senior Tester'],
        ])->assertOk()->assertJsonPath('data.profile_fields', ['profession']);

        $row = DB::table('userprofiles')->where('code', $u->code)->first();
        $this->assertSame(['Keep this summary', '09171112222', 'Cebu'], [$row->summary, $row->contact_no, $row->current_location]);
        $this->assertSame('Senior Tester', DB::table('resources')->where('code', $u->code)->value('profession'));
        $this->assertSame(['Sam', 'Seeker'], [User::find($u->id)->fname, User::find($u->id)->lname], 'a name that was not sent is not touched');
    }

    public function test_only_the_fields_the_person_sent_are_changed(): void
    {
        $u = $this->makeUser('DEF-USERS', ['summary' => 'Old']);
        $this->as($u);
        $id = $this->upload($this->docx($this->sampleCv()));

        $this->postJson("/api/user-resumes/{$id}/import", ['profile' => ['summary' => 'A new summary from my CV']])->assertOk();

        $this->assertSame('A new summary from my CV', DB::table('userprofiles')->where('code', $u->code)->value('summary'));
        $this->assertSame('Sam', User::find($u->id)->fname);
        $this->assertSame([0, 0, 0, 0, 0, 0, 0], array_values($this->counts($u->code)), 'no section was sent, so none was added');
    }

    public function test_importing_twice_creates_no_duplicates(): void
    {
        $u = $this->makeUser();
        $this->as($u);
        $id = $this->upload($this->docx($this->sampleCv()));
        $d = $this->postJson("/api/user-resumes/{$id}/extract")->json('data');
        $payload = $this->approveAll($d);

        $this->postJson("/api/user-resumes/{$id}/import", $payload)->assertOk();
        $after = $this->counts($u->code);
        $second = $this->postJson("/api/user-resumes/{$id}/import", $payload)->assertOk();

        $this->assertSame($after, $this->counts($u->code), 'the second import added nothing');
        $this->assertSame(0, $second->json('data.total_added'));
        $this->assertSame(7, $second->json('data.duplicates.skills'));
        $this->assertSame(2, $second->json('data.duplicates.employment'));
    }

    public function test_an_item_already_on_the_profile_is_not_added_again_whatever_its_spelling(): void
    {
        $u = $this->makeUser();
        DB::table('usereducations')->insert([
            'code' => $u->code, 'transNo' => 1, 'highest_education' => 'bachelor of science in information technology',
            'school_name' => 'ATENEO DE MANILA UNIVERSITY.', 'start_year' => 2012, 'end_year' => 2016, 'status' => 'graduate',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('useremploymentrecords')->insert([
            'code' => $u->code, 'transNo' => 1, 'company_name' => 'globex corporation', 'position' => 'SOFTWARE DEVELOPER',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->as($u);
        $id = $this->upload($this->docx($this->sampleCv()));
        $d = $this->postJson("/api/user-resumes/{$id}/extract")->json('data');

        $r = $this->postJson("/api/user-resumes/{$id}/import", $this->approveAll($d))->assertOk();

        $this->assertSame(1, $r->json('data.added.education'), 'only the high school was new');
        $this->assertSame(1, $r->json('data.duplicates.education'));
        $this->assertSame(1, $r->json('data.added.employment'));
        $this->assertSame(2, DB::table('usereducations')->where('code', $u->code)->count());
        $this->assertSame(2, DB::table('useremploymentrecords')->where('code', $u->code)->count());
    }

    public function test_the_same_item_twice_in_one_import_is_saved_once(): void
    {
        $u = $this->makeUser();
        $this->as($u);
        $id = $this->upload($this->docx($this->sampleCv()));

        $r = $this->postJson("/api/user-resumes/{$id}/import", ['skills' => [['skills' => 'Go'], ['skills' => 'go '], ['skills' => 'Rust']]])->assertOk();

        $this->assertSame(2, $r->json('data.added.skills'));
        $this->assertSame(1, $r->json('data.duplicates.skills'));
    }

    public function test_an_invalid_item_is_skipped_with_a_reason_and_the_rest_still_import(): void
    {
        $u = $this->makeUser();
        $this->as($u);
        $id = $this->upload($this->docx($this->sampleCv()));

        $r = $this->postJson("/api/user-resumes/{$id}/import", [
            'education' => [
                ['highest_education' => 'BS Nursing', 'school_name' => 'Valid University', 'start_year' => 2010, 'end_year' => 2014, 'status' => 'graduate'],
                ['highest_education' => 'BS Law', 'school_name' => '', 'status' => 'graduate'],                                   // no school
                ['highest_education' => 'BS Art', 'school_name' => 'Bad Status College', 'status' => 'whatever'],                  // bad status
                ['highest_education' => 'BS Fun', 'school_name' => 'Bad Month Institute', 'start_month' => 'Smarch', 'status' => 'graduate'],
            ],
            'certificate' => [['certificate_title' => 'Future Cert', 'date_completed' => date('Y-m-d', strtotime('+2 years'))]],
            'profile' => ['contact_no' => 'call me maybe', 'date_birth' => '2999-01-01'],
        ])->assertOk();

        $this->assertSame(1, $r->json('data.added.education'));
        $skipped = collect($r->json('data.skipped'));
        $this->assertCount(4, $skipped->where('section', '!=', 'profile'), '3 bad education items + the future certificate');
        $this->assertCount(2, $skipped->where('section', 'profile'), 'the bad phone number and the future date of birth');
        $this->assertNotEmpty($skipped->first()['reason']);
        $this->assertTrue($skipped->contains('item', 'Bad Status College'));
        $this->assertTrue($skipped->contains('section', 'certificate'));
        $this->assertTrue($skipped->contains(fn ($s) => $s['section'] === 'profile' && $s['item'] === 'contact_no'));
        $this->assertTrue($skipped->contains(fn ($s) => $s['section'] === 'profile' && $s['item'] === 'date_birth'));
        $this->assertSame(1, DB::table('usereducations')->where('code', $u->code)->count());
        $this->assertSame([], $r->json('data.profile_fields'), 'nothing valid was sent for the profile');
    }

    public function test_only_what_was_sent_is_imported_the_person_chooses(): void
    {
        $u = $this->makeUser();
        $this->as($u);
        $id = $this->upload($this->docx($this->sampleCv()));
        $d = $this->postJson("/api/user-resumes/{$id}/extract")->json('data');
        $payload = $this->approveAll($d);
        // the person unticked everything except the first skill and the first job
        $only = ['skills' => [$payload['skills'][0]], 'employment' => [$payload['employment'][0]]];

        $this->postJson("/api/user-resumes/{$id}/import", $only)->assertOk();

        $this->assertSame([0, 0, 1, 0, 0, 1, 0], array_values($this->counts($u->code)));
    }

    public function test_an_edited_value_is_what_gets_saved_not_what_was_extracted(): void
    {
        $u = $this->makeUser();
        $this->as($u);
        $id = $this->upload($this->docx($this->sampleCv()));

        $this->postJson("/api/user-resumes/{$id}/import", ['employment' => [
            ['company_name' => 'The Real Company Ltd', 'position' => 'Lead Engineer', 'job_description' => "line one\nline two", 'date_completed' => '2022-05-01'],
        ]])->assertOk();

        $j = DB::table('useremploymentrecords')->where('code', $u->code)->first();
        $this->assertSame(['The Real Company Ltd', 'Lead Engineer', "line one\nline two", '2022-05-01'], [$j->company_name, $j->position, $j->job_description, $j->date_completed]);
    }

    // ------------------------------------------------------------------ resumes: default, download, ownership

    public function test_the_import_can_make_the_resume_the_default(): void
    {
        $u = $this->makeUser();
        $this->as($u);
        $first = $this->upload($this->docx($this->sampleCv(), 'first.docx'));
        $second = $this->upload($this->pdf($this->sampleCv(), 'second.pdf'));
        $this->assertTrue((bool) DB::table('user_resumes')->where('id', $first)->value('is_default'));

        $r = $this->postJson("/api/user-resumes/{$second}/import", ['skills' => [['skills' => 'Rust']], 'set_default' => true])->assertOk();

        $this->assertTrue((bool) DB::table('user_resumes')->where('id', $second)->value('is_default'));
        $this->assertFalse((bool) DB::table('user_resumes')->where('id', $first)->value('is_default'));
        $this->assertSame(['second.pdf'], array_column(array_filter($r->json('data.resumes'), fn ($x) => $x['is_default']), 'name'));
    }

    public function test_without_set_default_the_default_does_not_change(): void
    {
        $u = $this->makeUser();
        $this->as($u);
        $first = $this->upload($this->docx($this->sampleCv(), 'first.docx'));
        $second = $this->upload($this->docx($this->sampleCv(), 'second.docx'));

        $this->postJson("/api/user-resumes/{$second}/import", ['skills' => [['skills' => 'Rust']]])->assertOk();

        $this->assertTrue((bool) DB::table('user_resumes')->where('id', $first)->value('is_default'));
    }

    public function test_the_original_file_stays_downloadable_after_an_import(): void
    {
        $u = $this->makeUser();
        $this->as($u);
        $id = $this->upload($this->docx($this->sampleCv(), 'my-cv.docx'));
        $this->postJson("/api/user-resumes/{$id}/import", ['skills' => [['skills' => 'Rust']]])->assertOk();

        $res = $this->get("/api/user-resumes/{$id}/file")->assertOk();

        $this->assertStringContainsString('wordprocessingml.document', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $res->headers->get('Content-Disposition'));
        $this->assertStringContainsString('my-cv.docx', $res->headers->get('Content-Disposition'));
    }

    public function test_a_pdf_still_opens_in_the_browser(): void
    {
        $this->as($this->makeUser());
        $id = $this->upload($this->pdf($this->sampleCv(), 'cv.pdf'));

        $res = $this->get("/api/user-resumes/{$id}/file")->assertOk();

        $this->assertSame('application/pdf', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $res->headers->get('Content-Disposition'));
    }

    public function test_the_list_tells_each_files_type(): void
    {
        $this->as($this->makeUser());
        $this->upload($this->docx($this->sampleCv(), 'a.docx'));
        $this->upload($this->pdf($this->sampleCv(), 'b.pdf'));

        $types = array_column($this->getJson('/api/user-resumes')->json('data'), 'type', 'name');

        $this->assertSame(['a.docx' => 'docx', 'b.pdf' => 'pdf'], $types);
    }

    public function test_nobody_can_extract_or_import_from_someone_elses_resume(): void
    {
        $owner = $this->makeUser();
        $this->as($owner);
        $id = $this->upload($this->docx($this->sampleCv()));

        $intruder = $this->makeUser();
        $this->as($intruder);

        $this->postJson("/api/user-resumes/{$id}/extract")->assertStatus(404);
        $this->postJson("/api/user-resumes/{$id}/import", ['skills' => [['skills' => 'Rust']], 'set_default' => true])->assertStatus(404);
        $this->assertSame(0, $this->counts($intruder->code)['userskills']);
        $this->assertSame(0, $this->counts($owner->code)['userskills']);
    }

    public function test_the_import_always_writes_to_the_signed_in_account(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $this->as($a);
        $id = $this->upload($this->docx($this->sampleCv()));

        // a code smuggled into the body is ignored: items and profile land on the caller
        $this->postJson("/api/user-resumes/{$id}/import", ['skills' => [['skills' => 'Rust', 'code' => $b->code]], 'profile' => ['code' => $b->code, 'summary' => 'mine']])->assertOk();

        $this->assertSame(1, $this->counts($a->code)['userskills']);
        $this->assertSame(0, $this->counts($b->code)['userskills']);
        $this->assertSame(0, DB::table('userprofiles')->where('code', $b->code)->count());
    }

    #[DataProvider('otherRoles')]
    public function test_only_job_seekers_can_use_the_import(string $role): void
    {
        $this->as($this->makeUser($role));

        $this->postJson('/api/user-resumes/1/extract')->assertStatus(403);
        $this->postJson('/api/user-resumes/1/import', [])->assertStatus(403);
    }

    public static function otherRoles(): array
    {
        return [['DEF-CLIENT'], ['DEF-ADMIN'], ['DEF-MASTERADMIN']];
    }

    public function test_it_needs_a_signed_in_user(): void
    {
        $this->postJson('/api/user-resumes/1/extract')->assertStatus(401);
        $this->postJson('/api/user-resumes/1/import', [])->assertStatus(401);
    }

    // ------------------------------------------------------------------ uploads

    public function test_doc_docx_and_pdf_are_accepted_and_other_files_are_not(): void
    {
        $this->as($this->makeUser());

        $this->upload($this->docx($this->sampleCv()));
        $this->upload($this->pdf($this->sampleCv()));
        $this->post('/api/user-resumes', ['resume' => \Illuminate\Http\UploadedFile::fake()->create('notes.txt', 5, 'text/plain')], ['Accept' => 'application/json'])->assertStatus(422);
        $this->post('/api/user-resumes', ['resume' => \Illuminate\Http\UploadedFile::fake()->create('virus.exe', 5, 'application/octet-stream')], ['Accept' => 'application/json'])->assertStatus(422);
        // a PDF extension on something that is not a PDF is refused
        $fake = $this->docx($this->sampleCv(), 'pretending.pdf');
        $this->post('/api/user-resumes', ['resume' => $fake], ['Accept' => 'application/json'])->assertStatus(422);
    }
}
