<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Role;
use App\Models\Roleaccessmenu;
use App\Models\Roleaccesssubmenu;
use App\Models\Submenu;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Role & permission management for the master admin (Security page + dashboard).
 *
 * Reuses the existing structure: roles, menus, submenus, roleaccessmenus, roleaccesssubmenus. "View" IS the existing menu /
 * submenu access (which the sidebar reads); the extra flags (add, edit, delete, approve, manage) live in role_permissions.
 * A user has ONE role (users.role_code), so "assigning" means choosing that role, with the escalation rules below.
 * Every change writes a security_audit_logs row.
 */
class SecurityManagementService
{
    public const MASTER = 'DEF-MASTERADMIN';
    public const ADMIN = 'DEF-ADMIN';

    /** Roles the application itself depends on: never deleted, never renamed. */
    public const SYSTEM_ROLES = ['DEF-MASTERADMIN', 'DEF-ADMIN', 'DEF-CLIENT', 'DEF-USERS'];
    /** Roles whose permissions only the code changes (locking yourself out of the security page is impossible this way). */
    public const LOCKED_PERMISSION_ROLES = ['DEF-MASTERADMIN'];

    /** why a role's permissions are read-only (shown in the permissions dialog and returned by the API) */
    public static function lockReason(string $rolecode): ?string
    {
        return match ($rolecode) {
            'DEF-MASTERADMIN' => 'DEF-MASTERADMIN always has full access. Its permissions can\'t be changed here.',
            default => null,
        };
    }
    public const FLAGS = ['add', 'edit', 'delete', 'approve', 'manage'];
    public const DEFAULT_ROLE = 'DEF-USERS';

    public static function isSystemRole(?string $code): bool
    {
        return in_array($code, self::SYSTEM_ROLES, true);
    }

    // ------------------------------------------------------------------ audit

    public function audit(string $action, object $actor, string $targetType, ?string $targetRef, string $summary, array $details = []): void
    {
        DB::table('security_audit_logs')->insert([
            'action' => $action, 'actor_code' => (string) $actor->code, 'actor_name' => $actor->fullname ?? null,
            'target_type' => $targetType, 'target_ref' => $targetRef, 'summary' => mb_substr($summary, 0, 250),
            'details' => $details ? json_encode($details) : null, 'ip' => request()?->ip(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function auditPage(int $page = 1, int $perPage = 25): array
    {
        $q = DB::table('security_audit_logs')->orderByDesc('id');
        $total = (clone $q)->count();
        $rows = $q->forPage($page, $perPage)->get()->map(fn ($r) => (array) $r + [])->map(function ($r) {
            $r['details'] = $r['details'] ? json_decode($r['details'], true) : null;
            return $r;
        })->all();

        return ['total' => $total, 'page' => $page, 'per_page' => $perPage, 'data' => $rows];
    }

    /** The sidebar caches menus per role for 5 minutes: drop it so a change applies on the very next request. */
    public static function forgetMenuCache(string ...$roles): void
    {
        $descs = collect(DB::table('menus')->distinct()->pluck('desc_code'))
            ->merge(DB::table('submenus')->distinct()->pluck('desc_code'))->push('top_navigation')->unique();
        foreach ($roles as $role) {
            foreach ($descs as $d) { Cache::forget("accessmenu_{$role}_{$d}"); }
        }
    }

    // ------------------------------------------------------------------ roles

    /** @return array<int,array<string,mixed>> */
    public function roles(): array
    {
        $users = DB::table('users')->select('role_code', DB::raw('count(*) as total'), DB::raw("sum(status = 'A') as active"))->groupBy('role_code')->get()->keyBy('role_code');
        $menus = $this->validMenuGrants()->select('a.rolecode', DB::raw('count(distinct a.menus_id) as n'))->groupBy('a.rolecode')->pluck('n', 'a.rolecode');
        $subs = $this->validSubmenuGrants()->select('a.rolecode', DB::raw('count(distinct a.submenus_id) as n'))->groupBy('a.rolecode')->pluck('n', 'a.rolecode');

        return Role::orderBy('id')->get()->map(fn ($r) => [
            'id' => $r->id, 'rolecode' => $r->rolecode, 'description' => $r->description,
            'created_by' => $r->created_by, 'updated_by' => $r->updated_by,
            'is_system' => self::isSystemRole($r->rolecode),
            'users_count' => (int) ($users[$r->rolecode]->total ?? 0),
            'active_users' => (int) ($users[$r->rolecode]->active ?? 0),
            'permissions_count' => (int) ($menus[$r->rolecode] ?? 0) + (int) ($subs[$r->rolecode] ?? 0),
        ])->all();
    }

    public function createRole(object $actor, string $rolecode, string $description): Role
    {
        return DB::transaction(function () use ($actor, $rolecode, $description) {
            $role = Role::create(['rolecode' => $rolecode, 'description' => $description, 'created_by' => $actor->fullname, 'updated_by' => null]);
            $this->audit('role.create', $actor, 'role', $rolecode, "Created role {$rolecode}", ['description' => $description]);
            return $role;
        });
    }

    /** @throws \DomainException a rule was broken (the message is safe to show) */
    public function updateRole(object $actor, Role $role, string $rolecode, string $description): Role
    {
        $renamed = $rolecode !== $role->rolecode;
        if ($renamed && self::isSystemRole($role->rolecode)) {
            throw new \DomainException("{$role->rolecode} is a system role and can't be renamed.");
        }
        if ($renamed) {
            $users = User::where('role_code', $role->rolecode)->count();
            if ($users > 0) {
                throw new \DomainException("Can't rename {$role->rolecode}: {$users} user(s) have this role. Move them to another role first.");
            }
        }

        return DB::transaction(function () use ($actor, $role, $rolecode, $description, $renamed) {
            $old = ['rolecode' => $role->rolecode, 'description' => $role->description];
            if ($renamed) {
                // the role code is the key the access rows and permissions hang on: move them with it
                foreach (['roleaccessmenus', 'roleaccesssubmenus', 'role_permissions'] as $t) {
                    DB::table($t)->where('rolecode', $old['rolecode'])->update(['rolecode' => $rolecode]);
                }
                self::forgetMenuCache($old['rolecode'], $rolecode);
            }
            $role->forceFill(['rolecode' => $rolecode, 'description' => $description, 'updated_by' => $actor->fullname])->save();
            $this->audit('role.update', $actor, 'role', $rolecode, $renamed ? "Renamed role {$old['rolecode']} to {$rolecode}" : "Edited role {$rolecode}", ['from' => $old, 'to' => ['rolecode' => $rolecode, 'description' => $description]]);
            return $role;
        });
    }

    /** @throws \DomainException */
    public function deleteRole(object $actor, Role $role): void
    {
        if (self::isSystemRole($role->rolecode)) {
            throw new \DomainException("{$role->rolecode} is a system role and can't be deleted.");
        }
        $users = User::where('role_code', $role->rolecode)->count();
        if ($users > 0) {
            throw new \DomainException("Can't delete {$role->rolecode}: {$users} user(s) still have this role. Move them to another role first.");
        }

        DB::transaction(function () use ($actor, $role) {
            // the role's own access rows and permissions go with it: nothing else can use them
            $grants = DB::table('roleaccessmenus')->where('rolecode', $role->rolecode)->count() + DB::table('roleaccesssubmenus')->where('rolecode', $role->rolecode)->count();
            foreach (['roleaccessmenus', 'roleaccesssubmenus', 'role_permissions'] as $t) {
                DB::table($t)->where('rolecode', $role->rolecode)->delete();
            }
            $role->delete();
            self::forgetMenuCache($role->rolecode);
            $this->audit('role.delete', $actor, 'role', $role->rolecode, "Deleted role {$role->rolecode}", ['permissions_removed' => $grants]);
        });
    }

    // ------------------------------------------------------------ permissions

    /** access rows that still point at a menu / submenu that exists (old rows for deleted menus are ignored, not deleted) */
    private function validMenuGrants()
    {
        return DB::table('roleaccessmenus as a')->join('menus as m', 'm.id', '=', 'a.menus_id');
    }

    private function validSubmenuGrants()
    {
        return DB::table('roleaccesssubmenus as a')->join('submenus as s', 's.id', '=', 'a.submenus_id');
    }

    /** Menus with their submenus. A container menu (route "#") has nothing to add / edit / delete itself: view only. */
    public function catalog(): array
    {
        $menus = Menu::orderBy('sort')->get();
        $subs = Submenu::orderBy('sort')->get()->groupBy('transNo');
        $applicable = fn ($routes) => trim((string) $routes) === '#' ? [] : self::FLAGS;

        return $menus->map(fn ($m) => [
            'type' => 'menu', 'id' => $m->id, 'name' => $m->description ?: $m->routes, 'routes' => $m->routes, 'icon' => $m->icon, 'active' => $m->status === 'A',
            'flags' => $applicable($m->routes),
            'children' => ($subs[$m->transNo] ?? collect())->map(fn ($s) => [
                'type' => 'submenu', 'id' => $s->id, 'name' => $s->description ?: $s->routes, 'routes' => $s->routes, 'icon' => $s->icon, 'active' => $s->status === 'A',
                'flags' => $applicable($s->routes),
            ])->values()->all(),
        ])->values()->all();
    }

    /** The catalog with this role's view access and flags filled in. */
    public function rolePermissions(Role $role): array
    {
        $menuView = DB::table('roleaccessmenus')->where('rolecode', $role->rolecode)->pluck('menus_id')->flip();
        $subView = DB::table('roleaccesssubmenus')->where('rolecode', $role->rolecode)->pluck('submenus_id')->flip();
        $flags = DB::table('role_permissions')->where('rolecode', $role->rolecode)->get()->keyBy(fn ($r) => $r->item_type . ':' . $r->item_id);

        $fill = function (array $item, $view) use ($flags) {
            $f = $flags[$item['type'] . ':' . $item['id']] ?? null;
            $item['view'] = $view;
            foreach (self::FLAGS as $flag) { $item[$flag] = $f ? (bool) $f->{"can_$flag"} : false; }
            return $item;
        };

        return [
            'role' => ['id' => $role->id, 'rolecode' => $role->rolecode, 'description' => $role->description, 'is_system' => self::isSystemRole($role->rolecode),
                'locked' => in_array($role->rolecode, self::LOCKED_PERMISSION_ROLES, true), 'lock_reason' => self::lockReason($role->rolecode)],
            'items' => array_map(function ($m) use ($fill, $menuView, $subView) {
                $children = array_map(fn ($s) => $fill($s, isset($subView[$s['id']])), $m['children']);
                return array_merge($fill($m, isset($menuView[$m['id']])), ['children' => $children]);   // array_merge: "+" would keep the raw catalog children
            }, $this->catalog()),
        ];
    }

    /**
     * Save view access and flags for a role. Only the items sent change. A flag needs "view"; a submenu needs its menu.
     * @param array<int,array{type:string,id:int,view:bool,add?:bool,edit?:bool,delete?:bool,approve?:bool,manage?:bool}> $items
     * @return array{granted:int,revoked:int,changed:int}
     * @throws \DomainException
     */
    public function savePermissions(object $actor, Role $role, array $items): array
    {
        if (in_array($role->rolecode, self::LOCKED_PERMISSION_ROLES, true)) {
            throw new \DomainException(self::lockReason($role->rolecode));
        }
        $menus = Menu::all()->keyBy('id');
        $subs = Submenu::all()->keyBy('id');
        foreach ($items as $i) {
            $found = $i['type'] === 'menu' ? $menus->has($i['id']) : $subs->has($i['id']);
            if (!$found) { throw new \DomainException("Unknown {$i['type']} #{$i['id']}."); }
            if (empty($i['view']) && array_filter(array_map(fn ($f) => !empty($i[$f]), self::FLAGS))) {
                $name = $i['type'] === 'menu' ? $menus[$i['id']]->description : $subs[$i['id']]->description;
                throw new \DomainException("\"{$name}\" has an Add / Edit / Delete / Approve / Manage permission but no View. Turn View on first.");
            }
        }

        return DB::transaction(function () use ($actor, $role, $items, $menus, $subs) {
            $rc = $role->rolecode;
            $before = $this->snapshot($rc);
            $max = (int) (Roleaccessmenu::max('transNo') ?? 0);
            $byType = collect($items)->groupBy('type');

            // menus first: a submenu hangs on its menu's access row
            foreach ($byType->get('menu', collect()) as $i) {
                $row = Roleaccessmenu::where('rolecode', $rc)->where('menus_id', $i['id'])->first();
                if (!empty($i['view'])) {
                    if (!$row) { Roleaccessmenu::create(['rolecode' => $rc, 'menus_id' => $i['id'], 'transNo' => ++$max, 'created_by' => $actor->fullname]); }
                } elseif ($row) {
                    Roleaccesssubmenu::where('rolecode', $rc)->where('transNo', $row->transNo)->delete();
                    $row->delete();
                    $childIds = Submenu::where('transNo', $menus[$i['id']]->transNo)->pluck('id');
                    DB::table('role_permissions')->where('rolecode', $rc)->where('item_type', 'submenu')->whereIn('item_id', $childIds)->delete();
                }
            }
            foreach ($byType->get('submenu', collect()) as $i) {
                $parent = $menus->firstWhere('transNo', $subs[$i['id']]->transNo);
                $parentRow = $parent ? Roleaccessmenu::where('rolecode', $rc)->where('menus_id', $parent->id)->first() : null;
                if (!empty($i['view'])) {
                    if (!$parentRow) { throw new \DomainException("Turn on \"{$parent?->description}\" before its submenu \"{$subs[$i['id']]->description}\"."); }
                    Roleaccesssubmenu::firstOrCreate(['rolecode' => $rc, 'transNo' => $parentRow->transNo, 'submenus_id' => $i['id']], ['created_by' => $actor->fullname]);
                } elseif ($parentRow) {
                    Roleaccesssubmenu::where('rolecode', $rc)->where('transNo', $parentRow->transNo)->where('submenus_id', $i['id'])->delete();
                }
            }
            foreach ($items as $i) {
                $flags = collect(self::FLAGS)->mapWithKeys(fn ($f) => ["can_$f" => !empty($i['view']) && !empty($i[$f])]);
                $key = ['rolecode' => $rc, 'item_type' => $i['type'], 'item_id' => $i['id']];
                if ($flags->contains(true)) {
                    DB::table('role_permissions')->updateOrInsert($key, $flags->all() + ['updated_by' => $actor->fullname, 'updated_at' => now(), 'created_at' => now()]);
                } else {
                    DB::table('role_permissions')->where($key)->delete();
                }
            }

            $after = $this->snapshot($rc);
            $granted = count(array_diff($after, $before));
            $revoked = count(array_diff($before, $after));
            $this->audit('permission.update', $actor, 'permission', $rc, "Changed permissions of {$rc}: {$granted} granted, {$revoked} revoked",
                ['granted' => array_slice(array_values(array_diff($after, $before)), 0, 40), 'revoked' => array_slice(array_values(array_diff($before, $after)), 0, 40)]);
            self::forgetMenuCache($rc);

            return ['granted' => $granted, 'revoked' => $revoked, 'changed' => $granted + $revoked];
        });
    }

    /** every permission a role holds as a flat list of "type:id:permission" strings, to compare before and after */
    private function snapshot(string $rolecode): array
    {
        $out = [];
        foreach (DB::table('roleaccessmenus')->where('rolecode', $rolecode)->pluck('menus_id') as $id) { $out[] = "menu:$id:view"; }
        foreach (DB::table('roleaccesssubmenus')->where('rolecode', $rolecode)->pluck('submenus_id') as $id) { $out[] = "submenu:$id:view"; }
        foreach (DB::table('role_permissions')->where('rolecode', $rolecode)->get() as $r) {
            foreach (self::FLAGS as $f) { if ($r->{"can_$f"}) { $out[] = "{$r->item_type}:{$r->item_id}:$f"; } }
        }
        return $out;
    }

    // ------------------------------------------------------------ assignment

    /** Why this actor may not give this user this role, or null. */
    public function assignBlocker(object $actor, object $target, string $newRole): ?string
    {
        $actorRole = $actor->role_code ?? null;
        if ($newRole === self::MASTER && $actorRole !== self::MASTER) {
            return 'Only a master admin can assign DEF-MASTERADMIN.';       // no privilege escalation, DEF-ADMIN included
        }
        if ($actorRole !== self::MASTER) {
            return 'Only a master admin can assign roles.';
        }
        if (!Role::where('rolecode', $newRole)->exists()) {
            return "The role {$newRole} does not exist.";
        }
        if ((string) $target->code === (string) $actor->code) {
            return 'You can\'t change your own role.';
        }
        if ($target->role_code === $newRole) {
            return "Already has the role {$newRole}.";
        }
        if ($target->role_code === self::MASTER) {
            $others = User::where('role_code', self::MASTER)->where('status', 'A')->where('id', '!=', $target->id)->count();
            if ($others < 1) { return 'The last active master admin can\'t lose the role.'; }
        }
        // job seekers and recruiters carry different data (CV, plans): switching one into the other would orphan it
        if (PlanService::roleCanHavePlan($target->role_code) && PlanService::roleCanHavePlan($newRole) && $target->role_code !== $newRole) {
            return 'A job seeker (DEF-USERS) and a recruiter (DEF-CLIENT) account can\'t be switched into each other: their data is different.';
        }
        return null;
    }

    /**
     * Give each user the role. One result per user; a user that breaks a rule is skipped, the others still go through.
     * @param string[] $codes
     * @return array<int,array{code:string,ok:bool,message:string}>
     */
    public function assign(object $actor, array $codes, string $newRole, string $action = 'role.assign'): array
    {
        $results = [];
        foreach (array_values(array_unique($codes)) as $code) {
            $target = User::where('code', $code)->first();
            if (!$target) { $results[] = ['code' => (string) $code, 'ok' => false, 'message' => 'User not found.']; continue; }
            if ($blocked = $this->assignBlocker($actor, $target, $newRole)) {
                $results[] = ['code' => (string) $code, 'ok' => false, 'message' => $blocked];
                continue;
            }
            $from = $target->role_code;
            DB::transaction(function () use ($actor, $target, $newRole, $from, $action) {
                $target->forceFill(['role_code' => $newRole])->save();
                DB::table('resources')->where('code', $target->code)->update(['role_code' => $newRole]);
                $target->tokens()->delete();            // end every open session so the new role is what they sign in with
                $this->audit($action, $actor, 'user', (string) $target->code,
                    ($action === 'role.remove' ? "Removed role {$from} from {$target->fullname} (now {$newRole})" : "Changed {$target->fullname} from {$from} to {$newRole}"),
                    ['from' => $from, 'to' => $newRole, 'user' => $target->fullname]);
            });
            PlanService::bust();
            $results[] = ['code' => (string) $code, 'ok' => true, 'message' => "{$target->fullname}: {$from} → {$newRole}"];
        }
        return $results;
    }

    /** "Remove" an assignment = back to the standard role (DEF-USERS). Same rules as assigning. */
    public function removeRole(object $actor, string $code): array
    {
        $target = User::where('code', $code)->first();
        if ($target && $target->role_code === self::DEFAULT_ROLE) {
            return [['code' => (string) $code, 'ok' => false, 'message' => 'This user has no extra role to remove (already ' . self::DEFAULT_ROLE . ').']];
        }
        return $this->assign($actor, [$code], self::DEFAULT_ROLE, 'role.remove');
    }

    public function users(Request $request): array
    {
        $q = DB::table('users')->select('code', 'fullname', 'email', 'role_code', 'status', 'updated_at');
        if ($s = trim((string) $request->query('search'))) {
            $q->where(fn ($w) => $w->where('fullname', 'like', "%$s%")->orWhere('email', 'like', "%$s%")->orWhere('code', 'like', "%$s%"));
        }
        if ($r = $request->query('role')) { $q->where('role_code', $r); }
        $per = max(5, min(100, (int) $request->query('per_page', 10)));
        $page = max(1, (int) $request->query('page', 1));
        $total = (clone $q)->count();

        return [
            'total' => $total, 'page' => $page, 'per_page' => $per,
            'data' => $q->orderBy('fullname')->forPage($page, $per)->get()->all(),
            'role_counts' => DB::table('users')->select('role_code', DB::raw('count(*) as total'))->groupBy('role_code')->pluck('total', 'role_code'),
        ];
    }

    // ------------------------------------------------------------------ stats

    public function stats(): array
    {
        $roles = $this->roles();
        $flagCount = 0;
        foreach (self::FLAGS as $f) { $flagCount += DB::table('role_permissions')->where("can_$f", true)->count(); }
        $recent = fn ($q) => $q->orderByDesc('id')->limit(10)->get()->map(function ($r) {
            return ['id' => $r->id, 'action' => $r->action, 'summary' => $r->summary, 'actor_name' => $r->actor_name, 'actor_code' => $r->actor_code,
                'target_ref' => $r->target_ref, 'created_at' => $r->created_at];
        })->all();

        return [
            'total_users' => User::count(),
            'total_roles' => count($roles),
            // permission entries granted: menu + submenu access, plus every add / edit / delete / approve / manage switched on
            'total_permissions' => $this->validMenuGrants()->distinct()->count(DB::raw("concat(a.rolecode, ':', a.menus_id)")) + $this->validSubmenuGrants()->distinct()->count(DB::raw("concat(a.rolecode, ':', a.submenus_id)")) + $flagCount,
            'active_roles' => count(array_filter($roles, fn ($r) => $r['active_users'] > 0)),   // a role with at least one active user
            'users_by_role' => array_map(fn ($r) => ['role_code' => $r['rolecode'], 'total' => $r['users_count'], 'active' => $r['active_users']], $roles),
            'recent_assignments' => $recent(DB::table('security_audit_logs')->whereIn('action', ['role.assign', 'role.remove'])),
            'recent_activities' => $recent(DB::table('security_audit_logs')),
        ];
    }
}
