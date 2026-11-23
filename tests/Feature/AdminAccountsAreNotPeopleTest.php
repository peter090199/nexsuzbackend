<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * DEF-ADMIN and DEF-MASTERADMIN are staff accounts: not people to connect with, and they have no CV.
 * Only DEF-USERS (job seekers) and DEF-CLIENT (recruiters) show up in people lists and have CV data.
 */
class AdminAccountsAreNotPeopleTest extends TestCase
{
    use DatabaseTransactions;

    private array $u = [];

    protected function setUp(): void
    {
        parent::setUp();
        static $next = 897000;
        foreach (['seeker' => 'DEF-USERS', 'recruiter' => 'DEF-CLIENT', 'admin' => 'DEF-ADMIN', 'master' => 'DEF-MASTERADMIN', 'viewer' => 'DEF-USERS'] as $k => $role) {
            $code = $next += random_int(1, 30);
            $this->u[$k] = User::forceCreate(['fname' => 'Zq' . $k, 'lname' => 'Staffcheck', 'fullname' => "Zq$k Staffcheck", 'role_code' => $role, 'email' => "$k$code@example.test",
                'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']);
            DB::table('resources')->insert(['code' => $code, 'fname' => 'Zq' . $k, 'lname' => 'Staffcheck', 'fullname' => "Zq$k Staffcheck", 'email' => "$k$code@example.test",
                'profession' => 'ZqProfession', 'role_code' => $role, 'contact_no' => '09', 'current_location' => 'X']);
        }
    }

    protected function tearDown(): void
    {
        DB::table('resources')->whereIn('code', array_map(fn ($u) => $u->code, $this->u))->delete();   // MyISAM: no rollback
        parent::tearDown();
    }

    private function as(User $u): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($u);
    }

    private function codes(array $res): array
    {
        return array_map(fn ($p) => (int) $p['code'], array_merge($res['online'] ?? [], $res['offline'] ?? []));
    }

    public function test_search_lists_job_seekers_and_recruiters_but_not_admin_accounts(): void
    {
        $this->as($this->u['viewer']);
        $codes = $this->codes($this->getJson('/api/searchUsers?search=Staffcheck&per_page=50')->assertOk()->json());
        $this->assertContains((int) $this->u['seeker']->code, $codes);
        $this->assertContains((int) $this->u['recruiter']->code, $codes);
        $this->assertNotContains((int) $this->u['admin']->code, $codes);
        $this->assertNotContains((int) $this->u['master']->code, $codes);
    }

    public function test_public_search_leaves_admin_accounts_out_too(): void
    {
        $codes = $this->codes($this->getJson('/api/searchUsersBypublic?search=Staffcheck&per_page=50')->assertOk()->json());
        $this->assertContains((int) $this->u['seeker']->code, $codes);
        $this->assertNotContains((int) $this->u['admin']->code, $codes);
        $this->assertNotContains((int) $this->u['master']->code, $codes);
    }

    public function test_suggestions_never_include_admin_accounts(): void
    {
        DB::table('resources')->where('code', $this->u['viewer']->code)->update(['profession' => 'ZqProfession']);
        $this->as($this->u['viewer']);
        $codes = array_map(fn ($p) => (int) $p['code'], $this->getJson('/api/getPeopleyoumayknow')->assertOk()->json('data'));
        $this->assertContains((int) $this->u['seeker']->code, $codes);
        $this->assertNotContains((int) $this->u['admin']->code, $codes);
        $this->assertNotContains((int) $this->u['master']->code, $codes);
    }

    public function test_a_company_never_lists_an_admin_among_its_people(): void
    {
        foreach (['seeker', 'admin', 'master'] as $k) {
            DB::table('follows')->insert(['follower_code' => $this->u[$k]->code, 'following_code' => $this->u['recruiter']->code, 'follow_status' => 'accepted', 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->as($this->u['viewer']);
        $codes = $this->codes($this->getJson('/api/company/people/' . $this->u['recruiter']->code)->assertOk()->json());
        $this->assertSame([(int) $this->u['seeker']->code], $codes);
    }

    public function test_an_admin_account_profile_has_no_cv_data(): void
    {
        $this->as($this->u['viewer']);
        foreach (['admin', 'master'] as $k) {
            $m = $this->getJson('/api/profile/' . $this->u[$k]->code)->assertOk()->json('message');
            $this->assertFalse($m['cv_available'], $k);
            $this->assertSame([], $m['lines']['education']);
            $this->assertSame([], $m['lines']['skills']);
            $this->assertSame([], $m['lines']['employment']);
            $this->assertNull($m['summary']);
        }
        foreach (['seeker', 'recruiter'] as $k) {
            $this->assertTrue($this->getJson('/api/profile/' . $this->u[$k]->code)->assertOk()->json('message.cv_available'), $k);
        }
    }

    public function test_the_master_admin_looking_at_a_job_seeker_still_sees_the_cv(): void
    {
        $this->as($this->u['master']);
        $m = $this->getJson('/api/profile/' . $this->u['seeker']->code)->assertOk()->json('message');
        $this->assertTrue($m['cv_available']);
        $this->assertArrayHasKey('education', $m['lines']);
    }
}
