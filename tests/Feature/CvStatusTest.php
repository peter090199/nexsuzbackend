<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** GET /api/cv/status: has a job seeker finished their CV? Synthetic accounts only; InnoDB rows roll back. */
class CvStatusTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // these tests describe the CV builder: pin that mode whatever the master admin has chosen in the live settings
        // (done inside the rolled-back transaction, so the real setting is never touched)
        \App\Models\AppSetting::forget(\App\Services\CvModeService::KEY);
    }

    private function account(string $role, ?string $profession = 'Engineer'): User
    {
        static $next = 896000;
        $code = $next += random_int(1, 40);
        $u = User::forceCreate(['fname' => 'Cv', 'lname' => 'Tester', 'fullname' => 'Cv Tester', 'role_code' => $role,
            'email' => "cv$code@example.test", 'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']);
        DB::table('resources')->insert(['code' => $code, 'fname' => 'Cv', 'lname' => 'Tester', 'fullname' => 'Cv Tester',
            'email' => $u->email, 'profession' => $profession, 'role_code' => $role]);
        return $u;
    }

    private function profile(User $u, array $over = []): void
    {
        DB::table('userprofiles')->insert($over + ['code' => $u->code, 'transNo' => random_int(100000, 999999), 'email' => $u->email,
            'contact_no' => '+639171234567', 'date_birth' => '2000-01-31', 'home_country' => 'Philippines', 'home_state' => 'Cebu',
            'current_location' => 'Philippines', 'current_state' => 'Cebu', 'summary' => 'Backend developer']);
    }

    private function cvStatus(User $u): array
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($u);
        return $this->getJson('/api/cv/status')->assertOk()->assertJsonPath('success', true)->json('data');
    }

    public function test_it_needs_a_signed_in_user(): void
    {
        $this->getJson('/api/cv/status')->assertStatus(401);
    }

    public function test_a_new_job_seeker_with_no_profile_has_not_finished_and_is_told_what_is_missing(): void
    {
        $s = $this->cvStatus($this->account('DEF-USERS'));
        $this->assertTrue($s['applies']);
        $this->assertFalse($s['complete']);
        $this->assertSame(['Contact number', 'Date of birth', 'Home country', 'Home city / state', 'Current location', 'Current city / state', 'Summary'], $s['missing']);
    }

    public function test_it_lists_exactly_the_blank_fields_and_treats_spaces_as_blank(): void
    {
        $u = $this->account('DEF-USERS');
        $this->profile($u, ['summary' => '   ', 'current_state' => '']);
        $s = $this->cvStatus($u);
        $this->assertFalse($s['complete']);
        $this->assertSame(['Current city / state', 'Summary'], $s['missing']);
    }

    public function test_the_profession_from_sign_up_is_part_of_it(): void
    {
        $u = $this->account('DEF-USERS', '');
        $this->profile($u);
        $s = $this->cvStatus($u);
        $this->assertFalse($s['complete']);
        $this->assertSame(['Profession'], $s['missing']);
    }

    public function test_a_fully_filled_cv_is_finished(): void
    {
        $u = $this->account('DEF-USERS');
        $this->profile($u);
        $s = $this->cvStatus($u);
        $this->assertTrue($s['applies']);
        $this->assertTrue($s['complete']);
        $this->assertSame([], $s['missing']);
    }

    public function test_it_follows_the_profile_as_it_is_filled_in(): void
    {
        $u = $this->account('DEF-USERS');
        $this->assertFalse($this->cvStatus($u)['complete']);
        $this->profile($u, ['summary' => '']);
        $this->assertSame(['Summary'], $this->cvStatus($u)['missing']);
        DB::table('userprofiles')->where('code', $u->code)->update(['summary' => 'Done']);
        $this->assertTrue($this->cvStatus($u)['complete']);
    }

    public function test_recruiters_and_admins_have_no_cv_so_it_never_applies_to_them(): void
    {
        foreach (['DEF-CLIENT', 'DEF-ADMIN', 'DEF-MASTERADMIN'] as $role) {
            $s = $this->cvStatus($this->account($role));
            $this->assertFalse($s['applies'], $role);
            $this->assertTrue($s['complete'], $role);
            $this->assertSame([], $s['missing'], $role);
        }
    }
}
