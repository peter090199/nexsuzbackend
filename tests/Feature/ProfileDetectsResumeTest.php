<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use App\Services\CvModeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * GET /api/profile/{code}: the profile response detects the job seeker's uploaded resume / CV (Settings > Resume / CV).
 * Only the owner gets the details (a resume is personal data); everybody else gets `resume: null`.
 * The resources rows written here are removed again; everything else rolls back.
 */
class ProfileDetectsResumeTest extends TestCase
{
    use DatabaseTransactions;

    /** @var int[] */
    private array $codes = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        AppSetting::forget(CvModeService::KEY);
    }

    protected function tearDown(): void
    {
        DB::table('resources')->whereIn('code', $this->codes)->delete();
        parent::tearDown();
    }

    private function makeUser(string $role = 'DEF-USERS'): User
    {
        static $next = 910000;
        $code = $next += random_int(1, 40);
        $this->codes[] = $code;

        $u = User::forceCreate([
            'fname' => 'Pia', 'lname' => 'Tester', 'fullname' => 'Pia Tester', 'role_code' => $role,
            'email' => "pia{$code}@example.test", 'password' => bcrypt('secret-pass'),
            'status' => 'A', 'code' => $code, 'coverphoto' => '',
        ]);
        DB::table('resources')->insert([
            'code' => $code, 'fname' => 'Pia', 'lname' => 'Tester', 'fullname' => 'Pia Tester', 'email' => $u->email,
            'role_code' => $role, 'profession' => 'Tester', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $u;
    }

    private function as(User $u): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs(User::find($u->id));
    }

    private function upload(string $name): void
    {
        $this->post('/api/user-resumes', ['resume' => UploadedFile::fake()->create($name, 80, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertStatus(201);
    }

    public function test_the_owner_without_an_upload_sees_none(): void
    {
        $u = $this->makeUser();
        $this->as($u);

        $r = $this->getJson("/api/profile/{$u->code}")->assertOk();

        $r->assertJsonPath('message.resume.has_resume', false)->assertJsonPath('message.resume.count', 0)
            ->assertJsonPath('message.resume.default', null);
    }

    public function test_the_owner_sees_the_uploaded_resume_and_which_one_is_the_default(): void
    {
        $u = $this->makeUser();
        $this->as($u);
        $this->upload('first.pdf');
        $this->upload('second.pdf');

        $r = $this->getJson("/api/profile/{$u->code}")->assertOk();

        $r->assertJsonPath('message.resume.has_resume', true)->assertJsonPath('message.resume.count', 2)
            ->assertJsonPath('message.resume.default.name', 'first.pdf');
        $this->assertGreaterThan(0, $r->json('message.resume.default.size'));
    }

    public function test_the_default_follows_a_change_of_default(): void
    {
        $u = $this->makeUser();
        $this->as($u);
        $this->upload('first.pdf');
        $this->post('/api/user-resumes', ['resume' => UploadedFile::fake()->create('second.pdf', 80, 'application/pdf')], ['Accept' => 'application/json']);
        $second = DB::table('user_resumes')->where('code', $u->code)->where('original_name', 'second.pdf')->value('id');
        $this->patchJson("/api/user-resumes/{$second}/default")->assertOk();

        $this->getJson("/api/profile/{$u->code}")->assertJsonPath('message.resume.default.name', 'second.pdf');
    }

    public function test_other_people_never_see_someones_resume(): void
    {
        $owner = $this->makeUser();
        $this->as($owner);
        $this->upload('private.pdf');

        foreach (['DEF-USERS', 'DEF-CLIENT', 'DEF-ADMIN', 'DEF-MASTERADMIN'] as $role) {
            $this->as($this->makeUser($role));
            $r = $this->getJson("/api/profile/{$owner->code}")->assertOk();
            $this->assertNull($r->json('message.resume'), "a {$role} must not learn about the resume");
            $this->assertStringNotContainsString('private.pdf', $r->getContent());
        }
    }

    public function test_the_cv_mode_is_reported(): void
    {
        $u = $this->makeUser();
        $this->as($u);

        $this->getJson("/api/profile/{$u->code}")->assertJsonPath('message.cv_mode', 'builder');

        CvModeService::set(true);
        $this->getJson("/api/profile/{$u->code}")->assertJsonPath('message.cv_mode', 'upload');
    }

    public function test_recruiters_have_no_resume_block(): void
    {
        $u = $this->makeUser('DEF-CLIENT');
        $this->as($u);

        $this->getJson("/api/profile/{$u->code}")->assertOk()->assertJsonPath('message.resume', null);
    }
}
