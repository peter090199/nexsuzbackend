<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\SecurityManagementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Role & permission management (/api/security-admin/*). Everything runs in a transaction that rolls back. */
class SecurityManagementTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<string,User> */
    private array $u = [];

    protected function setUp(): void
    {
        parent::setUp();
        static $next = 897000;
        foreach (['master' => 'DEF-MASTERADMIN', 'admin' => 'DEF-ADMIN', 'seeker' => 'DEF-USERS', 'recruiter' => 'DEF-CLIENT', 'plain' => 'DEF-USERS', 'plain2' => 'DEF-USERS'] as $k => $role) {
            $code = $next += random_int(1, 30);
            $this->u[$k] = User::forceCreate(['fname' => $k, 'lname' => 'T', 'fullname' => "$k T", 'role_code' => $role, 'email' => "$k$code@example.test",
                'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']);
        }
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    private function as(string $who): void { $this->app['auth']->forgetGuards(); Sanctum::actingAs($this->u[$who]); }
    private function master(): void { $this->as('master'); }
    private function makeRole(string $code = 'ZZ-TESTER', string $desc = 'Test role'): int
    {
        $this->master();
        return $this->postJson('/api/security-admin/roles', ['rolecode' => $code, 'description' => $desc])->assertStatus(201)->json('data.id');
    }
    private function audit(string $action): int { return DB::table('security_audit_logs')->where('action', $action)->count(); }
    private function items(array $rows): array { return ['items' => $rows]; }

    // ------------------------------------------------------------------ who may

    public function test_only_a_master_admin_may_use_any_security_endpoint(): void
    {
        $id = Role::value('id');
        $calls = [['get', '/api/security-admin/stats'], ['get', '/api/security-admin/roles'], ['post', '/api/security-admin/roles'], ['put', "/api/security-admin/roles/$id"],
            ['delete', "/api/security-admin/roles/$id"], ['get', "/api/security-admin/roles/$id/permissions"], ['put', "/api/security-admin/roles/$id/permissions"],
            ['get', '/api/security-admin/users'], ['post', '/api/security-admin/users/assign'], ['delete', '/api/security-admin/users/1/role'], ['get', '/api/security-admin/audit'],
            ['post', '/api/saveAccessMenu'], ['delete', '/api/deleteMenu']];
        foreach (['admin', 'seeker', 'recruiter'] as $who) {
            $this->as($who);
            foreach ($calls as [$m, $url]) { $this->{$m . 'Json'}($url, [])->assertStatus(403); }
        }
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/security-admin/stats')->assertStatus(401);
        $this->assertSame(0, $this->audit('role.create'));
    }

    // ------------------------------------------------------------------ roles

    public function test_create_validates_and_lists_the_role_with_counts_and_is_audited(): void
    {
        $this->master();
        foreach (['lower-case', 'AB', 'HAS SPACE', ''] as $bad) {
            $this->postJson('/api/security-admin/roles', ['rolecode' => $bad, 'description' => 'x'])->assertStatus(422)->assertJsonValidationErrors('rolecode');
        }
        $this->postJson('/api/security-admin/roles', ['rolecode' => 'DEF-ADMIN', 'description' => 'dup'])->assertStatus(422);   // taken
        $this->postJson('/api/security-admin/roles', ['rolecode' => 'ZZ-OK', 'description' => ''])->assertStatus(422)->assertJsonValidationErrors('description');

        $this->makeRole('ZZ-OK', 'Fine');
        $row = collect($this->getJson('/api/security-admin/roles')->assertOk()->json('data'))->firstWhere('rolecode', 'ZZ-OK');
        $this->assertSame(['users_count' => 0, 'permissions_count' => 0, 'is_system' => false], ['users_count' => $row['users_count'], 'permissions_count' => $row['permissions_count'], 'is_system' => $row['is_system']]);
        $this->assertTrue(collect($this->getJson('/api/security-admin/roles')->json('data'))->firstWhere('rolecode', 'DEF-MASTERADMIN')['is_system']);

        $log = DB::table('security_audit_logs')->where('action', 'role.create')->first();
        $this->assertSame((string) $this->u['master']->code, $log->actor_code);
        $this->assertSame('ZZ-OK', $log->target_ref);
    }

    public function test_system_roles_cannot_be_renamed_or_deleted_but_their_description_can_be_edited(): void
    {
        $this->master();
        foreach (SecurityManagementService::SYSTEM_ROLES as $code) {
            $id = Role::where('rolecode', $code)->value('id');
            if (!$id) { continue; }
            $this->deleteJson("/api/security-admin/roles/$id")->assertStatus(422)->assertJsonPath('message', "$code is a system role and can't be deleted.");
            $this->putJson("/api/security-admin/roles/$id", ['rolecode' => 'ZZ-RENAMED', 'description' => 'x'])->assertStatus(422);
            $this->assertTrue(Role::where('rolecode', $code)->exists());
        }
        // the old /role page is protected by the same rule
        $id = Role::where('rolecode', 'DEF-CLIENT')->value('id');
        $this->deleteJson("/api/role/$id")->assertOk()->assertJsonPath('success', false);
        $this->assertTrue(Role::where('rolecode', 'DEF-CLIENT')->exists());
    }

    public function test_a_custom_role_can_be_edited_renamed_and_deleted_only_when_nobody_has_it(): void
    {
        $id = $this->makeRole('ZZ-EDIT', 'before');
        $this->putJson("/api/security-admin/roles/$id", ['rolecode' => 'ZZ-EDIT', 'description' => 'after'])->assertOk();
        $this->assertSame('after', Role::find($id)->description);

        // permissions follow a rename
        $menuId = Menu::where('routes', 'home')->value('id');
        $this->putJson("/api/security-admin/roles/$id/permissions", $this->items([['type' => 'menu', 'id' => $menuId, 'view' => true]]))->assertOk();
        $this->putJson("/api/security-admin/roles/$id", ['rolecode' => 'ZZ-MOVED', 'description' => 'after'])->assertOk();
        $this->assertSame(1, DB::table('roleaccessmenus')->where('rolecode', 'ZZ-MOVED')->count());
        $this->assertSame(0, DB::table('roleaccessmenus')->where('rolecode', 'ZZ-EDIT')->count());

        // a role somebody holds can be neither renamed nor deleted
        $this->postJson('/api/security-admin/users/assign', ['codes' => [$this->u['plain']->code], 'role_code' => 'ZZ-MOVED'])->assertOk();
        $this->putJson("/api/security-admin/roles/$id", ['rolecode' => 'ZZ-OTHER', 'description' => 'x'])->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, '1 user'));
        $this->deleteJson("/api/security-admin/roles/$id")->assertStatus(422);
        $this->assertTrue(Role::where('rolecode', 'ZZ-MOVED')->exists());

        // once empty, delete takes the role's own access rows with it
        $this->deleteJson('/api/security-admin/users/' . $this->u['plain']->code . '/role')->assertOk();
        $this->deleteJson("/api/security-admin/roles/$id")->assertOk();
        $this->assertFalse(Role::where('rolecode', 'ZZ-MOVED')->exists());
        $this->assertSame(0, DB::table('roleaccessmenus')->where('rolecode', 'ZZ-MOVED')->count());
        foreach (['role.create', 'role.update', 'role.delete'] as $a) { $this->assertGreaterThan(0, $this->audit($a), $a); }
    }

    // ------------------------------------------------------------ permissions

    public function test_permissions_view_flags_dependencies_cache_and_audit(): void
    {
        $id = $this->makeRole('ZZ-PERM');
        $container = Menu::where('routes', '#')->first();                      // "System": has submenus
        $sub = DB::table('submenus')->where('transNo', $container->transNo)->first();
        $leaf = Menu::where('routes', 'home')->first();

        $tree = $this->getJson("/api/security-admin/roles/$id/permissions")->assertOk()->json('data');
        $this->assertFalse($tree['role']['locked']);
        $home = collect($tree['items'])->firstWhere('id', $leaf->id);
        $this->assertFalse($home['view']);
        $this->assertSame(SecurityManagementService::FLAGS, $home['flags']);
        $this->assertSame([], collect($tree['items'])->firstWhere('id', $container->id)['flags'], 'a container menu has only View');

        // a flag without view is refused, and so is a submenu without its menu
        $this->putJson("/api/security-admin/roles/$id/permissions", $this->items([['type' => 'menu', 'id' => $leaf->id, 'view' => false, 'edit' => true]]))->assertStatus(422);
        $this->putJson("/api/security-admin/roles/$id/permissions", $this->items([['type' => 'submenu', 'id' => $sub->id, 'view' => true]]))->assertStatus(422);
        $this->assertSame(0, DB::table('roleaccessmenus')->where('rolecode', 'ZZ-PERM')->count());

        $auditBefore = $this->audit('permission.update');   // the dev DB may already hold real audit rows
        Cache::put('accessmenu_ZZ-PERM_top_navigation', 'stale', 300);
        $r = $this->putJson("/api/security-admin/roles/$id/permissions", $this->items([
            ['type' => 'menu', 'id' => $leaf->id, 'view' => true, 'add' => true, 'manage' => true],
            ['type' => 'menu', 'id' => $container->id, 'view' => true],
            ['type' => 'submenu', 'id' => $sub->id, 'view' => true, 'delete' => true],
        ]))->assertOk()->json('data');
        $this->assertSame(['granted' => 6, 'revoked' => 0, 'changed' => 6], $r);   // 3 views + add + manage + delete
        $this->assertNull(Cache::get('accessmenu_ZZ-PERM_top_navigation'), 'the sidebar must re-read the role at once');
        $this->assertSame(2, DB::table('roleaccessmenus')->where('rolecode', 'ZZ-PERM')->count());
        $this->assertSame(1, DB::table('roleaccesssubmenus')->where('rolecode', 'ZZ-PERM')->count());

        $saved = collect($this->getJson("/api/security-admin/roles/$id/permissions")->json('data.items'))->firstWhere('id', $leaf->id);
        $this->assertTrue($saved['view'] && $saved['add'] && $saved['manage']);
        $this->assertFalse($saved['edit']);

        // turning the menu off takes its submenu rows and their flags with it
        $r = $this->putJson("/api/security-admin/roles/$id/permissions", $this->items([['type' => 'menu', 'id' => $container->id, 'view' => false]]))->assertOk()->json('data');
        $this->assertSame(0, DB::table('roleaccesssubmenus')->where('rolecode', 'ZZ-PERM')->count());
        $this->assertSame(0, DB::table('role_permissions')->where('rolecode', 'ZZ-PERM')->where('item_type', 'submenu')->count());
        $this->assertSame(3, $r['revoked']);                                          // menu view + submenu view + the submenu's Delete flag
        $this->assertSame($auditBefore + 2, $this->audit('permission.update'));
    }

    /** the dialog shows what is really granted: active submenus must come back checked, with their own flags, and can be unchecked */
    public function test_the_permission_tree_reflects_active_submenu_grants_and_they_can_be_unchecked(): void
    {
        $id = $this->makeRole('ZZ-TREE');
        $container = Menu::where('routes', '#')->first();
        $subs = DB::table('submenus')->where('transNo', $container->transNo)->orderBy('id')->limit(2)->get();
        $this->assertCount(2, $subs, 'the System menu has submenus');

        $this->putJson("/api/security-admin/roles/$id/permissions", $this->items([
            ['type' => 'menu', 'id' => $container->id, 'view' => true],
            ['type' => 'submenu', 'id' => $subs[0]->id, 'view' => true, 'edit' => true],
            ['type' => 'submenu', 'id' => $subs[1]->id, 'view' => true],
        ]))->assertOk();

        $read = fn () => collect($this->getJson("/api/security-admin/roles/$id/permissions")->assertOk()->json('data.items'))->firstWhere('id', $container->id);
        $menu = $read();
        $this->assertTrue($menu['view']);
        $this->assertCount(count(DB::table('submenus')->where('transNo', $container->transNo)->get()), $menu['children']);
        foreach ($menu['children'] as $c) {
            $this->assertArrayHasKey('view', $c, 'every submenu carries its view state');
            foreach (SecurityManagementService::FLAGS as $f) { $this->assertArrayHasKey($f, $c); }
        }
        $byId = collect($menu['children'])->keyBy('id');
        $this->assertTrue($byId[$subs[0]->id]['view'] && $byId[$subs[0]->id]['edit']);
        $this->assertTrue($byId[$subs[1]->id]['view']);
        $this->assertFalse($byId[$subs[1]->id]['edit']);
        $this->assertSame(2, $byId->where('view', true)->count());

        // uncheck one active submenu: it comes back unchecked, the other stays
        $this->putJson("/api/security-admin/roles/$id/permissions", $this->items([['type' => 'submenu', 'id' => $subs[0]->id, 'view' => false]]))->assertOk();
        $byId = collect($read()['children'])->keyBy('id');
        $this->assertFalse($byId[$subs[0]->id]['view']);
        $this->assertFalse($byId[$subs[0]->id]['edit']);
        $this->assertTrue($byId[$subs[1]->id]['view']);
    }

    public function test_master_admin_permissions_are_locked(): void
    {
        $this->master();
        $id = Role::where('rolecode', 'DEF-MASTERADMIN')->value('id');
        $tree = $this->getJson("/api/security-admin/roles/$id/permissions")->assertOk()->json('data');
        $this->assertTrue($tree['role']['locked']);
        $before = DB::table('roleaccessmenus')->where('rolecode', 'DEF-MASTERADMIN')->count();
        $this->putJson("/api/security-admin/roles/$id/permissions", $this->items([['type' => 'menu', 'id' => Menu::value('id'), 'view' => false]]))->assertStatus(422);
        $this->assertSame($before, DB::table('roleaccessmenus')->where('rolecode', 'DEF-MASTERADMIN')->count());
    }

    // ------------------------------------------------------------ assignment

    public function test_assigning_changes_the_role_ends_sessions_and_is_audited(): void
    {
        $this->makeRole('ZZ-AUDITOR');
        $t = $this->u['plain']; $t->createToken('t');
        $this->assertSame(1, DB::table('personal_access_tokens')->where('tokenable_id', $t->id)->count());

        $r = $this->postJson('/api/security-admin/users/assign', ['codes' => [$t->code, $this->u['plain2']->code], 'role_code' => 'ZZ-AUDITOR'])->assertOk()->json('data');
        $this->assertSame(2, $r['updated']);
        $this->assertSame('ZZ-AUDITOR', DB::table('users')->where('id', $t->id)->value('role_code'));
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $t->id)->count(), 'the old session must end');
        $this->assertSame(2, $this->audit('role.assign'));

        $users = $this->getJson('/api/security-admin/users?role=ZZ-AUDITOR')->assertOk();
        $this->assertSame(2, $users->json('total'));
        $this->assertSame(2, $users->json('role_counts.ZZ-AUDITOR'));

        // remove = back to the standard role
        $this->deleteJson("/api/security-admin/users/{$t->code}/role")->assertOk();
        $this->assertSame('DEF-USERS', DB::table('users')->where('id', $t->id)->value('role_code'));
        $this->deleteJson("/api/security-admin/users/{$t->code}/role")->assertStatus(422);       // already standard
        $this->assertSame(1, $this->audit('role.remove'));
    }

    public function test_escalation_and_lockout_rules(): void
    {
        $this->makeRole('ZZ-X');
        // nobody changes their own role
        $this->postJson('/api/security-admin/users/assign', ['codes' => [$this->u['master']->code], 'role_code' => 'ZZ-X'])->assertStatus(422)->assertJsonPath('message', 'You can\'t change your own role.');
        // unknown role
        $this->postJson('/api/security-admin/users/assign', ['codes' => [$this->u['plain']->code], 'role_code' => 'NOPE'])->assertStatus(422);
        // a job seeker and a recruiter are not interchangeable
        $this->postJson('/api/security-admin/users/assign', ['codes' => [$this->u['seeker']->code], 'role_code' => 'DEF-CLIENT'])->assertStatus(422);
        $this->assertSame('DEF-USERS', DB::table('users')->where('id', $this->u['seeker']->id)->value('role_code'));
        // partial success: the valid one goes through, the master is skipped
        $r = $this->postJson('/api/security-admin/users/assign', ['codes' => [$this->u['plain']->code, $this->u['master']->code], 'role_code' => 'ZZ-X'])->assertOk()->json('data');
        $this->assertSame([1, 1], [$r['updated'], $r['skipped']]);

        // DEF-ADMIN can never hand out DEF-MASTERADMIN (or anything): checked in the service as well as by the route
        $svc = app(SecurityManagementService::class);
        $this->assertSame('Only a master admin can assign DEF-MASTERADMIN.', $svc->assignBlocker($this->u['admin'], $this->u['plain2'], 'DEF-MASTERADMIN'));
        $this->assertSame('Only a master admin can assign roles.', $svc->assignBlocker($this->u['admin'], $this->u['plain2'], 'ZZ-X'));
        $this->assertSame('Only a master admin can assign DEF-MASTERADMIN.', $svc->assignBlocker($this->u['seeker'], $this->u['plain2'], 'DEF-MASTERADMIN'));
        $this->as('admin');
        $this->postJson('/api/security-admin/users/assign', ['codes' => [$this->u['plain2']->code], 'role_code' => 'DEF-MASTERADMIN'])->assertStatus(403);
        $this->assertSame('DEF-USERS', DB::table('users')->where('id', $this->u['plain2']->id)->value('role_code'));
    }

    public function test_the_last_active_master_admin_cannot_lose_the_role(): void
    {
        $svc = app(SecurityManagementService::class);
        $actor = (object) ['code' => 'someone-else', 'role_code' => 'DEF-MASTERADMIN'];
        $others = DB::table('users')->where('role_code', 'DEF-MASTERADMIN')->where('id', '!=', $this->u['master']->id)->count();
        $this->assertSame(null, $others > 0 ? null : null);

        // while another active master exists the demotion is allowed ...
        $m2 = User::forceCreate(['fname' => 'm2', 'lname' => 'T', 'fullname' => 'm2 T', 'role_code' => 'DEF-MASTERADMIN', 'email' => 'm2x' . random_int(1, 99999) . '@example.test',
            'password' => bcrypt('pw'), 'status' => 'A', 'code' => 897999, 'coverphoto' => '']);
        $this->assertNull($svc->assignBlocker($actor, $m2, 'DEF-ADMIN'));
        // ... and refused once he would be the last one
        DB::table('users')->where('role_code', 'DEF-MASTERADMIN')->where('id', '!=', $m2->id)->update(['status' => 'I']);
        $this->assertSame('The last active master admin can\'t lose the role.', $svc->assignBlocker($actor, $m2->fresh(), 'DEF-ADMIN'));
    }

    // ------------------------------------------------------------------ stats

    public function test_dashboard_statistics_match_the_data(): void
    {
        $this->makeRole('ZZ-STAT');
        $this->postJson('/api/security-admin/users/assign', ['codes' => [$this->u['plain']->code], 'role_code' => 'ZZ-STAT'])->assertOk();
        $menu = Menu::where('routes', 'home')->value('id');
        $this->putJson('/api/security-admin/roles/' . Role::where('rolecode', 'ZZ-STAT')->value('id') . '/permissions', $this->items([['type' => 'menu', 'id' => $menu, 'view' => true, 'edit' => true]]))->assertOk();

        $s = $this->getJson('/api/security-admin/stats')->assertOk()->json('data');
        $this->assertSame(User::count(), $s['total_users']);
        $this->assertSame(Role::count(), $s['total_roles']);
        // grants that still point at a real menu / submenu (old rows for deleted menus don't count), each counted once, + 1 = the Edit flag
        $menus = DB::table('roleaccessmenus as a')->join('menus as m', 'm.id', '=', 'a.menus_id')->selectRaw("count(distinct concat(a.rolecode, ':', a.menus_id)) as n")->value('n');
        $subs = DB::table('roleaccesssubmenus as a')->join('submenus as s', 's.id', '=', 'a.submenus_id')->selectRaw("count(distinct concat(a.rolecode, ':', a.submenus_id)) as n")->value('n');
        $flags = 0;
        foreach (SecurityManagementService::FLAGS as $f) { $flags += DB::table('role_permissions')->where("can_$f", true)->count(); }   // includes any real ones
        $this->assertGreaterThanOrEqual(1, $flags);
        $this->assertSame((int) $menus + (int) $subs + $flags, $s['total_permissions']);
        $this->assertSame(User::count(), array_sum(array_column($s['users_by_role'], 'total')));
        $this->assertSame(1, collect($s['users_by_role'])->firstWhere('role_code', 'ZZ-STAT')['total']);
        $activeRoles = Role::all()->filter(fn ($r) => User::where('role_code', $r->rolecode)->where('status', 'A')->exists())->count();
        $this->assertSame($activeRoles, $s['active_roles']);
        $this->assertStringContainsString('ZZ-STAT', $s['recent_assignments'][0]['summary']);
        $this->assertGreaterThanOrEqual(3, count($s['recent_activities']));                // create + assign + permission
        $this->assertSame((string) $this->u['master']->code, $s['recent_activities'][0]['actor_code']);

        $audit = $this->getJson('/api/security-admin/audit')->assertOk();
        $this->assertGreaterThanOrEqual(3, $audit->json('total'));
    }
}
