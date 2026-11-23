<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DataResetService;
use App\Services\DatabaseAdminService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * "Reset all data". The real endpoint would reset the whole database, so these tests never run it to completion.
 * They check every refusal, the read-only preview, and run the purge ONLY against synthetic accounts created here
 * (the service accepts the list of accounts to remove), so real rows cannot be touched.
 */
class DataResetTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<string, User> */
    private array $u = [];
    private int $backupsBefore = 0;

    protected function setUp(): void
    {
        parent::setUp();
        static $next = 897000;
        foreach (['master' => 'DEF-MASTERADMIN', 'admin' => 'DEF-ADMIN', 'seeker' => 'DEF-USERS', 'recruiter' => 'DEF-CLIENT'] as $k => $role) {
            $code = $next += random_int(1, 40);
            $this->u[$k] = User::forceCreate([
                'fname' => $k, 'lname' => 'Tester', 'fullname' => "$k Tester", 'role_code' => $role,
                'email' => "$k$code@example.test", 'password' => bcrypt('right-password'), 'status' => 'A', 'code' => $code, 'coverphoto' => '',
            ]);
        }
        $this->backupsBefore = count(app(DatabaseAdminService::class)->listBackups());
    }

    /** MyISAM tables do not roll back, so remove what the tests wrote by hand. */
    protected function tearDown(): void
    {
        $codes = collect($this->u)->pluck('code')->map(fn ($c) => (string) $c);
        DB::table('usersubscription')->whereIn('code', $codes)->delete();
        DB::table('usersubscription_details')->whereIn('code', $codes)->delete();
        foreach (glob(DatabaseAdminService::backupDir() . '/db-*.sql.gz') ?: [] as $f) {
            if (filemtime($f) >= time() - 120 && count(app(DatabaseAdminService::class)->listBackups()) > $this->backupsBefore) { /* none expected */ }
        }
        parent::tearDown();
    }

    private function as(string $who): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($this->u[$who]);
    }

    private function backupCount(): int { return count(app(DatabaseAdminService::class)->listBackups()); }

    // ------------------------------------------------------------ refusals

    public function test_nobody_but_a_master_admin_can_even_preview_or_reset(): void
    {
        foreach (['admin', 'seeker', 'recruiter'] as $who) {
            $this->as($who);
            $this->getJson('/api/database-admin/reset/preview')->assertStatus(403);
            $this->postJson('/api/database-admin/reset', ['password' => 'right-password', 'confirm' => 'RESET ALL DATA'])->assertStatus(403);
        }
        $this->assertSame($this->backupsBefore, $this->backupCount());
    }

    public function test_reset_is_refused_unless_every_check_passes_and_no_backup_is_made_when_refused(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);   // the 3-per-5-minutes limit is tested below
        $this->as('master');
        config(['databaseadmin.allow_reset' => true]);

        $this->postJson('/api/database-admin/reset', [])->assertStatus(422);
        $this->postJson('/api/database-admin/reset', ['password' => 'right-password'])->assertStatus(422);
        $this->postJson('/api/database-admin/reset', ['password' => 'right-password', 'confirm' => 'reset all data'])->assertStatus(422)
            ->assertJsonPath('message', 'Type RESET ALL DATA exactly to confirm.');
        $this->postJson('/api/database-admin/reset', ['password' => 'wrong', 'confirm' => 'RESET ALL DATA'])->assertStatus(422)
            ->assertJsonPath('message', 'Your password is incorrect.');

        config(['databaseadmin.allow_reset' => false]);
        $this->postJson('/api/database-admin/reset', ['password' => 'right-password', 'confirm' => 'RESET ALL DATA'])->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertSame($this->backupsBefore, $this->backupCount(), 'a refused reset must not create backups');
        $this->assertSame(1, DB::table('users')->where('id', $this->u['seeker']->id)->count(), 'a refused reset must not delete anyone');
    }

    public function test_reset_attempts_are_rate_limited(): void
    {
        $this->as('master');
        config(['databaseadmin.allow_reset' => true]);
        $codes = [];
        for ($i = 0; $i < 5; $i++) {
            $codes[] = $this->postJson('/api/database-admin/reset', ['password' => 'wrong', 'confirm' => 'nope'])->getStatusCode();
        }
        $this->assertSame([422, 422, 422, 429, 429], $codes);
    }

    public function test_preview_is_read_only_and_names_the_protected_accounts(): void
    {
        $this->as('master');
        $before = DB::table('users')->count();
        $d = $this->getJson('/api/database-admin/reset/preview')->assertOk()
            ->assertJsonStructure(['data' => ['accounts_removed', 'rows_removed', 'steps', 'protected', 'kept_tables', 'files', 'allowed', 'phrase']])
            ->json('data');

        $this->assertSame($before, DB::table('users')->count());
        $this->assertSame('RESET ALL DATA', $d['phrase']);
        $roles = array_unique(array_column($d['protected'], 'role_code'));
        sort($roles);
        $this->assertSame(['DEF-ADMIN', 'DEF-MASTERADMIN'], $roles);
        $codes = array_column($d['protected'], 'code');
        $this->assertContains((string) $this->u['admin']->code, array_map('strval', $codes));
        $this->assertContains((string) $this->u['master']->code, array_map('strval', $codes));
        // system configuration is never in the plan
        $kept = array_column($d['kept_tables'], 'table');
        foreach (['menus', 'roles', 'userplanheader', 'userplandetails', 'subscription_features', 'migrations', 'control_numbers'] as $t) {
            $this->assertContains($t, $kept);
        }
        $planned = array_column($d['steps'], 'table');
        $this->assertContains('users', $planned);
        $this->assertNotContains('menus', $planned);
    }

    // ------------------------------------------------------- who is selected

    public function test_protected_roles_are_never_selected_for_removal(): void
    {
        $svc = app(DataResetService::class);
        $doomed = $svc->doomedUsers();
        $protected = DB::table('users')->whereIn('role_code', ['DEF-ADMIN', 'DEF-MASTERADMIN'])->get(['id', 'code']);

        $this->assertGreaterThan(0, $protected->count());
        foreach ($protected as $p) {
            $this->assertNotContains((int) $p->id, $doomed['ids']);
            $this->assertNotContains((string) $p->code, $doomed['codes']);
        }
        $this->assertContains((int) $this->u['seeker']->id, $doomed['ids']);
        $this->assertContains((int) $this->u['recruiter']->id, $doomed['ids']);
    }

    public function test_file_targets_only_ever_use_numeric_codes(): void
    {
        $t = app(DataResetService::class)->fileTargets(['../../etc', '12/../34', 'abc', '', '%2e%2e']);
        $this->assertSame(0, $t['count']);
    }

    // ------------------------------------------------- the purge, on test data

    public function test_purge_removes_only_the_given_accounts_and_what_they_own_and_keeps_admins(): void
    {
        $svc = app(DataResetService::class);
        [$seeker, $recruiter, $admin, $master] = [$this->u['seeker'], $this->u['recruiter'], $this->u['admin'], $this->u['master']];

        // rows for the accounts we will remove AND for a protected admin (so we can prove the admin's survive)
        $sub = fn (User $x) => DB::table('usersubscription')->insert([
            'transNo' => 'ZZ' . $x->code, 'planId' => 'PLN000001', 'plan_name' => 'Free', 'amount' => 0, 'code' => (string) $x->code,
            'is_active' => 'active', 'start_date' => now(), 'end_date' => now()->addMonth()]);
        $sub($seeker); $sub($recruiter); $sub($admin);
        DB::table('follows')->insert([
            ['follower_code' => $seeker->code, 'following_code' => $admin->code, 'follow_status' => 'accepted', 'created_at' => now(), 'updated_at' => now()],
            ['follower_code' => $admin->code, 'following_code' => $master->code, 'follow_status' => 'accepted', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('messages')->insert([
            ['code' => (string) $seeker->code, 'sender_id' => $seeker->id, 'receiver_id' => $admin->id, 'message' => 'hi admin', 'created_at' => now(), 'updated_at' => now()],
            ['code' => (string) $admin->code, 'sender_id' => $admin->id, 'receiver_id' => $master->id, 'message' => 'hi master', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $seeker->createToken('t'); $recruiter->createToken('t'); $admin->createToken('t');

        $realCounts = fn () => collect(['posts', 'commentposts', 'jobposting', 'applied_jobs', 'userplanheader', 'menus', 'roles'])
            ->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()])->all();
        $realBefore = $realCounts();
        $realUsersBefore = DB::table('users')->whereNotIn('id', collect($this->u)->pluck('id'))->count();

        $doomed = ['codes' => [(string) $seeker->code, (string) $recruiter->code], 'ids' => [$seeker->id, $recruiter->id],
            'emails' => [$seeker->email, $recruiter->email], 'phones' => []];
        $result = $svc->purge(false, $doomed);

        // the removed accounts are gone with their data
        $this->assertSame(0, DB::table('users')->whereIn('id', [$seeker->id, $recruiter->id])->count());
        $this->assertSame(0, DB::table('usersubscription')->whereIn('code', [(string) $seeker->code, (string) $recruiter->code])->count());
        $this->assertSame(0, DB::table('follows')->where('follower_code', $seeker->code)->count());
        $this->assertSame(0, DB::table('messages')->where('sender_id', $seeker->id)->count());
        $this->assertSame(0, DB::table('personal_access_tokens')->whereIn('tokenable_id', [$seeker->id, $recruiter->id])->count());

        // the protected accounts and everything they own survive
        $this->assertSame(2, DB::table('users')->whereIn('id', [$admin->id, $master->id])->count());
        $this->assertSame(1, DB::table('usersubscription')->where('code', (string) $admin->code)->count());
        $this->assertSame(1, DB::table('follows')->where('follower_code', $admin->code)->where('following_code', $master->code)->count());
        $this->assertSame(1, DB::table('messages')->where('sender_id', $admin->id)->where('receiver_id', $master->id)->count());
        $this->assertSame(1, DB::table('personal_access_tokens')->where('tokenable_id', $admin->id)->count());

        // nothing else moved: other people's data and all configuration are untouched
        $this->assertSame($realBefore, $realCounts());
        $this->assertSame($realUsersBefore, DB::table('users')->whereNotIn('id', collect($this->u)->pluck('id'))->count());
        $this->assertGreaterThan(0, $result['rows']);
    }

    public function test_purge_with_nobody_to_remove_deletes_nothing(): void
    {
        $before = DB::table('users')->count();
        $r = app(DataResetService::class)->purge(false, ['codes' => [], 'ids' => [], 'emails' => [], 'phones' => []]);
        $this->assertSame(0, $r['rows']);
        $this->assertSame($before, DB::table('users')->count());
    }
}
