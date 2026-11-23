<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** GET /api/company/people/{code}: the people connected to a company, online / offline, like the search page. */
class CompanyPeopleTest extends TestCase
{
    use DatabaseTransactions;

    private array $u = [];

    protected function setUp(): void
    {
        parent::setUp();
        static $next = 895000;
        foreach (['company' => 'DEF-CLIENT', 'ann' => 'DEF-USERS', 'bob' => 'DEF-USERS', 'cy' => 'DEF-USERS', 'stranger' => 'DEF-USERS', 'viewer' => 'DEF-USERS', 'master' => 'DEF-MASTERADMIN'] as $k => $role) {
            $code = $next += random_int(1, 30);
            $this->u[$k] = User::forceCreate(['fname' => $k, 'lname' => 'T', 'fullname' => ucfirst($k) . ' T', 'role_code' => $role, 'email' => "$k$code@example.test",
                'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']);
        }
        DB::table('users')->where('code', $this->u['ann']->code)->update(['is_online' => 1]);
        // ann follows the company, the company follows bob, cy only asked (pending), stranger has nothing
        $this->follow($this->u['ann'], $this->u['company'], 'accepted');
        $this->follow($this->u['company'], $this->u['bob'], 'accepted');
        $this->follow($this->u['cy'], $this->u['company'], 'pending');
    }

    protected function tearDown(): void
    {
        // user_blocks is MyISAM here: no rollback, so remove what the test wrote
        DB::table('user_blocks')->whereIn('blocker_code', array_map(fn ($u) => $u->code, $this->u))->delete();
        $this->resetBlockCache();
        parent::tearDown();
    }

    /** BlockService keeps a per-request cache; a test makes several requests in one process */
    private function resetBlockCache(): void
    {
        $p = new \ReflectionProperty(\App\Services\BlockService::class, 'cache');
        $p->setAccessible(true);
        $p->setValue(null, []);
    }

    private function follow(User $from, User $to, string $status): void
    {
        DB::table('follows')->insert(['follower_code' => $from->code, 'following_code' => $to->code, 'follow_status' => $status, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function people(User $as, string $query = '', ?User $company = null)
    {
        $this->resetBlockCache();
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($as);
        return $this->getJson('/api/company/people/' . ($company ?? $this->u['company'])->code . $query);
    }

    public function test_it_needs_a_signed_in_user(): void
    {
        $this->getJson('/api/company/people/' . $this->u['company']->code)->assertStatus(401);
    }

    public function test_it_lists_accepted_connections_in_both_directions_split_by_online_state(): void
    {
        $r = $this->people($this->u['viewer'])->assertOk()->assertJsonPath('success', true);
        $this->assertSame([(int) $this->u['ann']->code], array_map(fn ($p) => (int) $p['code'], $r->json('online')));
        $this->assertSame([(int) $this->u['bob']->code], array_map(fn ($p) => (int) $p['code'], $r->json('offline')));
        $all = array_merge($r->json('online'), $r->json('offline'));
        $codes = array_map(fn ($p) => (int) $p['code'], $all);
        $this->assertNotContains((int) $this->u['cy']->code, $codes, 'a pending request is not a connection');
        $this->assertNotContains((int) $this->u['stranger']->code, $codes);
        $this->assertNotContains((int) $this->u['company']->code, $codes, 'the company is not its own connection');
        $this->assertArrayHasKey('follow_status', $all[0]);
    }

    public function test_the_master_admin_can_open_it_too(): void
    {
        $r = $this->people($this->u['master'])->assertOk();
        $this->assertCount(2, array_merge($r->json('online'), $r->json('offline')));
    }

    public function test_search_filters_by_name(): void
    {
        $r = $this->people($this->u['viewer'], '?search=Bob')->assertOk();
        $this->assertCount(0, $r->json('online'));
        $this->assertCount(1, $r->json('offline'));
    }

    public function test_people_the_viewer_blocked_are_left_out_and_a_blocked_company_is_not_available(): void
    {
        $r = $this->people($this->u['viewer']);
        $this->assertCount(2, array_merge($r->json('online'), $r->json('offline')));

        DB::table('user_blocks')->insert(['blocker_code' => $this->u['viewer']->code, 'blocked_code' => $this->u['ann']->code, 'created_at' => now(), 'updated_at' => now()]);
        $r = $this->people($this->u['viewer']);
        $this->assertCount(0, $r->json('online'));

        DB::table('user_blocks')->insert(['blocker_code' => $this->u['viewer']->code, 'blocked_code' => $this->u['company']->code, 'created_at' => now(), 'updated_at' => now()]);
        $this->people($this->u['viewer'])->assertStatus(404)->assertJsonPath('blocked', true);
    }

    public function test_per_page_is_capped_and_paging_works(): void
    {
        $r = $this->people($this->u['viewer'], '?per_page=1&page=1')->assertOk();
        $this->assertSame(1, $r->json('meta.per_page'));
        $huge = $this->people($this->u['viewer'], '?per_page=9999')->assertOk();
        $this->assertSame(50, $huge->json('meta.per_page'));
    }
}
