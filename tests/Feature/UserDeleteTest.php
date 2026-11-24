<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DatabaseAdminService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Delete one account and everything it created. Only synthetic accounts are ever deleted here, so real data is untouched.
 * The endpoint takes (or reuses) a real database backup: files created by a test are removed again.
 */
class UserDeleteTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<string, User> */
    private array $u = [];
    private array $backupsBefore = [];

    protected function setUp(): void
    {
        parent::setUp();
        static $next = 898000;
        foreach (['master' => 'DEF-MASTERADMIN', 'admin' => 'DEF-ADMIN', 'seeker' => 'DEF-USERS', 'other' => 'DEF-USERS', 'recruiter' => 'DEF-CLIENT'] as $k => $role) {
            $code = $next += random_int(1, 40);
            $this->u[$k] = User::forceCreate([
                'fname' => $k, 'lname' => 'Tester', 'fullname' => "$k Tester", 'role_code' => $role,
                'email' => "$k$code@example.test", 'password' => bcrypt('right-password'), 'status' => 'A', 'code' => $code, 'coverphoto' => '',
            ]);
        }
        $this->backupsBefore = array_column(app(DatabaseAdminService::class)->listBackups(), 'name');
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);   // the limit has its own test
    }

    /** MyISAM tables do not roll back, so remove what the tests wrote by hand, plus any backup / folder they created. */
    protected function tearDown(): void
    {
        $codes = collect($this->u)->pluck('code')->map(fn ($c) => (string) $c);
        DB::table('usersubscription')->whereIn('code', $codes->merge($codes->map(fn ($c) => 'DELETED-' . $c)))->delete();
        DB::table('usersubscription_details')->whereIn('code', $codes)->delete();
        foreach ($codes as $c) { foreach (["app/public/uploads/$c", "app/public/uploads/posts/$c", "app/public/uploads/comments/$c", "app/uploads/$c"] as $p) { File::deleteDirectory(storage_path($p)); } }
        foreach (app(DatabaseAdminService::class)->listBackups() as $b) {
            if (!in_array($b['name'], $this->backupsBefore, true)) { @unlink(DatabaseAdminService::backupDir() . DIRECTORY_SEPARATOR . $b['name']); }
        }
        parent::tearDown();
    }

    private function as(string $who): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($this->u[$who]);
    }

    private function del(string $who, array $body = [], ?string $by = 'master')
    {
        $this->as($by);
        $t = $this->u[$who];
        return $this->deleteJson("/api/users/{$t->code}", $body + ['password' => 'right-password', 'confirm' => 'DELETE']);
    }

    private function exists(string $who): bool { return DB::table('users')->where('id', $this->u[$who]->id)->exists(); }
    private function backupCount(): int { return count(app(DatabaseAdminService::class)->listBackups()); }

    private function giveData(string $who): void
    {
        $x = $this->u[$who];
        DB::table('usersubscription')->insert(['transNo' => 'ZZ' . $x->code, 'planId' => 'PLN000001', 'plan_name' => 'Free', 'amount' => 0,
            'code' => (string) $x->code, 'is_active' => 'active', 'start_date' => now(), 'end_date' => now()->addMonth()]);
        DB::table('follows')->insert(['follower_code' => $x->code, 'following_code' => $this->u['admin']->code, 'follow_status' => 'accepted', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('messages')->insert(['code' => (string) $x->code, 'sender_id' => $x->id, 'receiver_id' => $this->u['admin']->id, 'message' => 'hello', 'created_at' => now(), 'updated_at' => now()]);
        $x->createToken('t');
    }

    // ------------------------------------------------------------------ who may

    public function test_only_a_master_admin_may_preview_or_delete(): void
    {
        foreach (['admin', 'seeker', 'recruiter'] as $by) {
            $this->as($by);
            $code = $this->u['other']->code;
            $this->getJson("/api/users/$code/delete-preview")->assertStatus(403);
            $this->deleteJson("/api/users/$code", ['password' => 'right-password', 'confirm' => 'DELETE'])->assertStatus(403);
        }
        $this->assertTrue($this->exists('other'));
        $this->assertSame(count($this->backupsBefore), $this->backupCount());
    }

    public function test_only_you_cannot_delete_yourself_and_admins_and_other_masters_can_be_deleted(): void
    {
        $this->del('master')->assertStatus(422)->assertJsonPath('message', 'You can\'t delete your own account.');
        $this->assertTrue($this->exists('master'));
        $this->assertSame(count($this->backupsBefore), $this->backupCount());

        // an admin is refused by the route and by the service
        $this->del('seeker', [], 'admin')->assertStatus(403);
        $this->assertTrue($this->exists('seeker'));

        // a master admin may delete an admin and another master admin
        $this->as('master');
        $this->getJson("/api/users/{$this->u['admin']->code}/delete-preview")->assertOk()->assertJsonPath('data.can_delete', true);
        $this->del('admin')->assertOk();
        $this->assertFalse($this->exists('admin'));

        $other = User::forceCreate(['fname' => 'm2', 'lname' => 'T', 'fullname' => 'm2 T', 'role_code' => 'DEF-MASTERADMIN', 'email' => 'm2' . random_int(1, 99999) . '@example.test',
            'password' => bcrypt('pw'), 'status' => 'A', 'code' => 899123, 'coverphoto' => '']);
        $this->u['m2'] = $other;
        $this->del('m2')->assertOk();
        $this->assertFalse($this->exists('m2'));
    }

    public function test_the_last_active_master_admin_is_protected(): void
    {
        DB::table('users')->where('role_code', 'DEF-MASTERADMIN')->where('id', '!=', $this->u['master']->id)->update(['status' => 'I']);   // rolled back
        $actor = (object) ['code' => 'x', 'role_code' => 'DEF-MASTERADMIN'];
        $this->assertSame('The last active master admin can\'t be deleted.', app(\App\Services\UserDeletionService::class)->blocker($this->u['master'], $actor));
    }
    public function test_unknown_account_is_404(): void
    {
        $this->as('master');
        $this->getJson('/api/users/999999999/delete-preview')->assertStatus(404);
        $this->deleteJson('/api/users/999999999', ['password' => 'right-password', 'confirm' => 'x'])->assertStatus(404);
    }

    // ------------------------------------------------------------------ checks before anything is touched

    public function test_a_wrong_password_or_wrong_confirmation_deletes_nothing_and_makes_no_backup(): void
    {
        $this->giveData('seeker');

        $this->del('seeker', ['password' => 'wrong'])->assertStatus(422)->assertJsonPath('message', 'Your password is incorrect.');
        $this->del('seeker', ['confirm' => 'delete me'])->assertStatus(422)
            ->assertJsonPath('message', 'Type DELETE exactly to confirm.');
        $this->as('master');
        $this->deleteJson("/api/users/{$this->u['seeker']->code}", [])->assertStatus(422);

        $this->assertTrue($this->exists('seeker'));
        $this->assertSame(1, DB::table('usersubscription')->where('code', (string) $this->u['seeker']->code)->count());
        $this->assertSame(count($this->backupsBefore), $this->backupCount(), 'a refused delete must not make backups');
    }

    public function test_preview_shows_what_would_go_and_changes_nothing(): void
    {
        $this->giveData('seeker');
        $this->as('master');
        $d = $this->getJson("/api/users/{$this->u['seeker']->code}/delete-preview")->assertOk()
            ->assertJsonPath('data.can_delete', true)->assertJsonPath('data.user.email', $this->u['seeker']->email)
            ->assertJsonStructure(['data' => ['rows_removed', 'steps', 'others_applications', 'jobs', 'files']])->json('data');

        $byTable = collect($d['steps'])->pluck('rows', 'table');
        $this->assertSame(1, $byTable['usersubscription']);
        $this->assertSame(1, $byTable['follows']);
        $this->assertSame(1, $byTable['messages']);
        $this->assertSame(1, $byTable['users']);
        $this->assertTrue($this->exists('seeker'));
    }

    // ------------------------------------------------------------------ the delete

    public function test_it_deletes_that_account_and_all_its_data_but_nobody_elses(): void
    {
        $this->giveData('seeker');
        $this->giveData('other');
        $this->giveData('admin');

        $before = collect(['posts', 'commentposts', 'jobposting', 'applied_jobs', 'userplanheader', 'menus', 'roles'])
            ->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()])->all();
        $realUsers = DB::table('users')->whereNotIn('id', collect($this->u)->pluck('id'))->count();

        $r = $this->del('seeker')->assertOk()->assertJsonPath('success', true);
        $this->assertStringContainsString('seeker Tester', $r->json('message'));
        $this->assertGreaterThan(0, $r->json('data.rows'));
        $this->assertNotEmpty($r->json('backup.name'));

        // gone
        $c = (string) $this->u['seeker']->code;
        $this->assertFalse($this->exists('seeker'));
        // financial rows are kept but no longer point at the person
        $this->assertSame(0, DB::table('usersubscription')->where('code', $c)->count());
        $this->assertSame(1, DB::table('usersubscription')->where('code', 'DELETED-' . $c)->count());
        $this->assertSame(0, DB::table('usersubscription_details')->where('code', $c)->count());
        // the audit trail
        $audit = DB::table('user_deletion_audits')->where('target_code', $c)->first();
        $this->assertSame('completed', $audit->status);
        $this->assertSame((string) $this->u['master']->code, $audit->deleted_by_code);
        $this->assertStringNotContainsString($this->u['seeker']->email, (string) $audit->target_email_masked);
        $this->assertSame(0, DB::table('follows')->where('follower_code', $c)->count());
        $this->assertSame(0, DB::table('messages')->where('sender_id', $this->u['seeker']->id)->count());
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $this->u['seeker']->id)->count());

        // untouched: another user, an admin, and everything else
        foreach (['other', 'admin'] as $keep) {
            $k = $this->u[$keep];
            $this->assertTrue($this->exists($keep));
            $this->assertSame(1, DB::table('usersubscription')->where('code', (string) $k->code)->count(), "$keep subscription");
            $this->assertSame(1, DB::table('follows')->where('follower_code', $k->code)->count(), "$keep follows");
            $this->assertSame(1, DB::table('messages')->where('sender_id', $k->id)->count(), "$keep messages");
            $this->assertSame(1, DB::table('personal_access_tokens')->where('tokenable_id', $k->id)->count(), "$keep token");
        }
        $this->assertSame($before, collect(array_keys($before))->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()])->all());
        $this->assertSame($realUsers, DB::table('users')->whereNotIn('id', collect($this->u)->pluck('id'))->count());
    }

    public function test_a_recruiter_can_be_deleted_too(): void
    {
        $this->del('recruiter')->assertOk();
        $this->assertFalse($this->exists('recruiter'));
    }

    public function test_a_backup_is_made_first_and_a_recent_one_is_reused(): void
    {
        $first = $this->del('seeker')->assertOk()->json('backup');
        $this->assertNotEmpty($first['name']);
        $this->assertSame($first['name'], collect(app(DatabaseAdminService::class)->listBackups())->first()['name']);

        $second = $this->del('other')->assertOk()->json('backup');
        $this->assertTrue($second['reused']);
        $this->assertSame($first['name'], $second['name'], 'deleting a second account within 10 minutes reuses the backup');
    }

    public function test_the_accounts_own_files_are_removed_on_every_local_disk_and_nobody_elses(): void
    {
        $mk = function (string $rel) { $d = storage_path($rel); File::ensureDirectoryExists($d); file_put_contents("$d/f.bin", 'x'); return $d; };
        $s = $this->u['seeker']->code; $o = $this->u['other']->code;
        $mine = [$mk("app/public/uploads/$s/cvphoto"), $mk("app/public/uploads/posts/$s/u1"), $mk("app/uploads/$s/resumes"), $mk("app/public/uploads/comments/$s")];
        $theirs = [$mk("app/public/uploads/$o"), $mk("app/public/uploads/posts/$o/u1")];

        $r = $this->del('seeker')->assertOk();
        $this->assertGreaterThan(0, $r->json('data.files'));
        foreach ($mine as $d) { $this->assertDirectoryDoesNotExist($d); }
        foreach ($theirs as $d) { $this->assertDirectoryExists($d); }
        $this->assertSame(1, (int) DB::table('user_deletion_audits')->where('target_code', (string) $s)->where('files_deleted', '>', 0)->count());
        File::deleteDirectory(storage_path("app/public/uploads/posts/$o"));
        File::deleteDirectory(storage_path("app/public/uploads/comments/$s"));
        File::deleteDirectory(storage_path("app/uploads/$s"));
    }

    public function test_deleting_is_rate_limited(): void
    {
        $this->withMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $codes = [];
        for ($i = 0; $i < 12; $i++) {
            $codes[] = $this->del('seeker', ['password' => 'wrong'])->getStatusCode();
        }
        $this->assertContains(429, $codes);
        $this->assertTrue($this->exists('seeker'));
    }
}
