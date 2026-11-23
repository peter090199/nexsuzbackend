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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Settings (master admin) > how job seekers provide their CV: the builder (default) or "upload a CV / resume only".
 * In upload-only mode one uploaded resume satisfies the "set up your CV first" rule, so nobody is locked out.
 * Rolled-back transactions; the saved setting is put back to what it was.
 */
class CvModeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        AppSetting::forget(CvModeService::KEY);       // start every test from "nothing saved" (inside the rolled-back transaction)
    }

    private function makeUser(string $role): User
    {
        static $next = 920000;
        $code = $next += random_int(1, 40);

        return User::forceCreate([
            'fname' => 'Cy', 'lname' => 'Tester', 'fullname' => 'Cy Tester', 'role_code' => $role,
            'email' => "cy{$code}@example.test", 'password' => bcrypt('secret-pass'),
            'status' => 'A', 'code' => $code, 'coverphoto' => '',
        ]);
    }

    private function as(User $u): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs(User::find($u->id));
    }

    public function test_the_builder_is_the_default(): void
    {
        $this->as($this->makeUser('DEF-USERS'));

        $this->getJson('/api/cv/mode')->assertOk()
            ->assertJsonPath('data.upload_only', false)->assertJsonPath('data.mode', 'builder');
    }

    public function test_the_master_admin_can_switch_it_and_everyone_sees_it(): void
    {
        $this->as($this->makeUser('DEF-MASTERADMIN'));
        $this->putJson('/api/cv/mode', ['upload_only' => true])->assertOk()->assertJsonPath('data.mode', 'upload');

        $this->as($this->makeUser('DEF-USERS'));
        $this->getJson('/api/cv/mode')->assertOk()->assertJsonPath('data.upload_only', true);

        $this->as($this->makeUser('DEF-MASTERADMIN'));
        $this->putJson('/api/cv/mode', ['upload_only' => false])->assertOk()->assertJsonPath('data.mode', 'builder');
    }

    #[DataProvider('others')]
    public function test_nobody_else_can_change_it(string $role): void
    {
        $this->as($this->makeUser($role));

        $this->putJson('/api/cv/mode', ['upload_only' => true])->assertStatus(403);

        $this->assertFalse(CvModeService::uploadOnly());
    }

    public static function others(): array
    {
        return [['DEF-USERS'], ['DEF-CLIENT'], ['DEF-ADMIN']];
    }

    public function test_the_value_must_be_on_or_off(): void
    {
        $this->as($this->makeUser('DEF-MASTERADMIN'));

        $this->putJson('/api/cv/mode', [])->assertStatus(422);
        $this->putJson('/api/cv/mode', ['upload_only' => 'maybe'])->assertStatus(422);
    }

    public function test_it_needs_a_signed_in_user(): void
    {
        $this->getJson('/api/cv/mode')->assertStatus(401);
    }

    public function test_builder_mode_still_asks_for_the_full_cv_even_with_an_uploaded_resume(): void
    {
        $seeker = $this->makeUser('DEF-USERS');
        $this->as($seeker);
        $this->post('/api/user-resumes', ['resume' => UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf')], ['Accept' => 'application/json'])->assertStatus(201);

        $r = $this->getJson('/api/cv/status')->assertOk();

        $r->assertJsonPath('data.complete', false)->assertJsonPath('data.mode', 'builder');
        $this->assertContains('Summary', $r->json('data.missing'));
    }

    public function test_upload_only_mode_needs_just_one_uploaded_resume(): void
    {
        CvModeService::set(true);
        $seeker = $this->makeUser('DEF-USERS');
        $this->as($seeker);

        $before = $this->getJson('/api/cv/status')->assertOk();
        $before->assertJsonPath('data.complete', false)->assertJsonPath('data.mode', 'upload')
            ->assertJsonPath('data.missing', ['Upload your CV or resume']);

        $this->post('/api/user-resumes', ['resume' => UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf')], ['Accept' => 'application/json'])->assertStatus(201);

        $this->getJson('/api/cv/status')->assertOk()->assertJsonPath('data.complete', true)->assertJsonPath('data.missing', []);
    }

    public function test_deleting_the_last_resume_makes_it_incomplete_again_in_upload_mode(): void
    {
        CvModeService::set(true);
        $this->as($this->makeUser('DEF-USERS'));
        $id = $this->post('/api/user-resumes', ['resume' => UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf')], ['Accept' => 'application/json'])->json('id');

        $this->deleteJson("/api/user-resumes/{$id}")->assertOk();

        $this->getJson('/api/cv/status')->assertJsonPath('data.complete', false);
    }

    public function test_recruiters_and_admins_are_never_affected(): void
    {
        CvModeService::set(true);
        foreach (['DEF-CLIENT', 'DEF-ADMIN', 'DEF-MASTERADMIN'] as $role) {
            $this->as($this->makeUser($role));
            $this->getJson('/api/cv/status')->assertOk()->assertJsonPath('data.applies', false)->assertJsonPath('data.complete', true);
        }
    }
}
