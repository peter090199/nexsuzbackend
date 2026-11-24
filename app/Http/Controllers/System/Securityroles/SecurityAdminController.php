<?php

namespace App\Http\Controllers\System\Securityroles;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\SecurityManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Role & permission management API (Security page and dashboard). Every route sits behind the `masteradmin` middleware;
 * the rules themselves (system roles, escalation, last master admin, audit) live in SecurityManagementService.
 */
class SecurityAdminController extends Controller
{
    private const ROLE_MESSAGES = ['rolecode.regex' => 'The role code must be 3 to 40 capital letters, digits, "-" or "_", starting with a letter (e.g. DEF-AUDITOR).'];

    public function __construct(private SecurityManagementService $security) {}

    private function fail(\DomainException $e, int $status = 422)
    {
        return response()->json(['success' => false, 'message' => $e->getMessage()], $status);
    }

    public function stats()
    {
        return response()->json(['success' => true, 'data' => $this->security->stats()]);
    }

    // ---- roles
    public function roles()
    {
        return response()->json(['success' => true, 'data' => $this->security->roles()]);
    }

    private function roleRules(?int $ignoreId = null): array
    {
        return [
            'rolecode' => ['required', 'string', 'regex:/^[A-Z][A-Z0-9_-]{2,39}$/', Rule::unique('roles', 'rolecode')->ignore($ignoreId)],
            'description' => ['required', 'string', 'max:255'],
        ];
    }

    public function storeRole(Request $request)
    {
        $d = $request->validate($this->roleRules(), self::ROLE_MESSAGES);
        $role = $this->security->createRole(Auth::user(), $d['rolecode'], $d['description']);

        return response()->json(['success' => true, 'message' => "Role {$role->rolecode} created.", 'data' => ['id' => $role->id, 'rolecode' => $role->rolecode]], 201);
    }

    public function updateRole(Request $request, int $id)
    {
        $role = Role::findOrFail($id);
        $d = $request->validate($this->roleRules($role->id), self::ROLE_MESSAGES);
        try {
            $role = $this->security->updateRole(Auth::user(), $role, $d['rolecode'], $d['description']);
        } catch (\DomainException $e) {
            return $this->fail($e);
        }

        return response()->json(['success' => true, 'message' => "Role {$role->rolecode} updated."]);
    }

    public function destroyRole(int $id)
    {
        $role = Role::findOrFail($id);
        try {
            $this->security->deleteRole(Auth::user(), $role);
        } catch (\DomainException $e) {
            return $this->fail($e);
        }

        return response()->json(['success' => true, 'message' => "Role {$role->rolecode} deleted."]);
    }

    // ---- permissions
    public function permissions(int $id)
    {
        return response()->json(['success' => true, 'data' => $this->security->rolePermissions(Role::findOrFail($id))]);
    }

    public function savePermissions(Request $request, int $id)
    {
        $role = Role::findOrFail($id);
        $rules = [
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*.type' => ['required', Rule::in(['menu', 'submenu'])],
            'items.*.id' => ['required', 'integer'],
            'items.*.view' => ['required', 'boolean'],
        ];
        foreach (SecurityManagementService::FLAGS as $f) { $rules["items.*.$f"] = ['sometimes', 'boolean']; }
        $request->validate($rules);
        try {
            $result = $this->security->savePermissions(Auth::user(), $role, $request->input('items'));
        } catch (\DomainException $e) {
            return $this->fail($e);
        }

        return response()->json(['success' => true, 'message' => $result['changed'] ? "Permissions saved ({$result['granted']} granted, {$result['revoked']} revoked)." : 'Nothing changed.', 'data' => $result]);
    }

    // ---- assignment
    public function users(Request $request)
    {
        return response()->json(['success' => true] + $this->security->users($request));
    }

    public function assign(Request $request)
    {
        $d = $request->validate(['codes' => ['required', 'array', 'min:1', 'max:200'], 'codes.*' => ['required'], 'role_code' => ['required', 'string']]);

        return $this->respond($this->security->assign(Auth::user(), $d['codes'], $d['role_code']));
    }

    public function removeRole(string $code)
    {
        return $this->respond($this->security->removeRole(Auth::user(), $code));
    }

    private function respond(array $results)
    {
        $ok = count(array_filter($results, fn ($r) => $r['ok']));
        $failed = count($results) - $ok;
        $message = $failed === 0 ? ($ok === 1 ? $results[0]['message'] : "{$ok} users updated.") : ($ok === 0 ? $results[0]['message'] : "{$ok} updated, {$failed} skipped.");

        return response()->json(['success' => $ok > 0, 'message' => $message, 'data' => ['updated' => $ok, 'skipped' => $failed, 'results' => $results]], $ok > 0 ? 200 : 422);
    }

    // ---- audit
    public function audit(Request $request)
    {
        return response()->json(['success' => true] + $this->security->auditPage(max(1, (int) $request->query('page', 1)), max(5, min(100, (int) $request->query('per_page', 25)))));
    }
}
