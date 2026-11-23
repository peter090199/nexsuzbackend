<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Settings > Profile & Privacy for DEF-ADMIN and DEF-MASTERADMIN: they have no CV, but they can create and edit the basic
 * profile (contact details, location, summary, visibility) without a photo and without any CV data.
 * Job seekers still need a photo; recruiters still cannot create a profile row; nobody but job seekers writes CV sections.
 */
class AdminProfileWithoutCvTest extends TestCase
{
    use DatabaseTransactions;

    /** @var int[] */
    private array $codes = [];

    private function makeUser(string $role): User
    {
        static $next = 960000;
        $code = $next += random_int(1, 40);
        $this->codes[] = $code;

        return User::forceCreate([
            'fname' => 'Pat', 'lname' => 'Tester', 'fullname' => 'Pat Tester', 'role_code' => $role,
            'email' => "pat{$code}@example.test", 'password' => bcrypt('secret-pass'),
            'status' => 'A', 'code' => $code, 'coverphoto' => '',
        ]);
    }

    /** the profile table may not roll back, so remove what the tests wrote */
    protected function tearDown(): void
    {
        DB::table('userprofiles')->whereIn('code', $this->codes)->delete();
        parent::tearDown();
    }

    private function as(User $u): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs(User::find($u->id));
    }

    private function payload(array $extra = []): array
    {
        return $extra + [
            'contact_no' => '+639171234567', 'date_birth' => '1/15/1990', 'current_location' => 'Manila',
            'summary' => 'Platform administrator', 'contact_visibility' => true, 'email_visibility' => false,
        ];
    }

    public static function admins(): array
    {
        return [['DEF-ADMIN'], ['DEF-MASTERADMIN']];
    }

    #[DataProvider('admins')]
    public function test_an_admin_can_create_the_basic_profile_without_cv_or_photo(string $role): void
    {
        $u = $this->makeUser($role);
        $this->as($u);

        $this->postJson('/api/saveProfile', $this->payload())->assertOk()->assertJsonPath('success', true);

        $row = DB::table('userprofiles')->where('code', $u->code)->first();
        $this->assertNotNull($row);
        $this->assertNull($row->photo_pic);
        $this->assertSame('Manila', $row->current_location);
        $this->assertSame('1990-01-15', $row->date_birth);
    }

    #[DataProvider('admins')]
    public function test_an_admin_can_edit_it_again_and_change_privacy(string $role): void
    {
        $u = $this->makeUser($role);
        $this->as($u);
        $this->postJson('/api/saveProfile', $this->payload())->assertOk();

        $this->postJson('/api/saveProfile', $this->payload(['summary' => 'Updated', 'email_visibility' => true]))
            ->assertOk()->assertJsonPath('success', true);

        $row = DB::table('userprofiles')->where('code', $u->code)->first();
        $this->assertSame('Updated', $row->summary);
        $this->assertSame(1, (int) $row->email_visibility);
        $this->assertSame(1, DB::table('userprofiles')->where('code', $u->code)->count(), 'edited, not duplicated');
    }

    #[DataProvider('admins')]
    public function test_an_admin_still_cannot_write_cv_sections(string $role): void
    {
        $this->as($this->makeUser($role));

        $this->postJson('/api/saveEducation', [])->assertStatus(403);
        $this->postJson('/api/saveLanguage', [])->assertStatus(403);
        $this->postJson('/api/saveSkills', [])->assertStatus(403);
    }

    public function test_a_recruiter_still_cannot_create_a_profile_row(): void
    {
        $u = $this->makeUser('DEF-CLIENT');
        $this->as($u);

        $this->postJson('/api/saveProfile', $this->payload())->assertStatus(403);

        $this->assertSame(0, DB::table('userprofiles')->where('code', $u->code)->count());
    }

    public function test_a_job_seeker_still_needs_a_photo_to_create_a_profile(): void
    {
        $u = $this->makeUser('DEF-USERS');
        $this->as($u);

        $this->postJson('/api/saveProfile', $this->payload(['home_state' => 'x', 'home_country' => 'y']))
            ->assertStatus(422);

        $this->assertSame(0, DB::table('userprofiles')->where('code', $u->code)->count());
    }
}
