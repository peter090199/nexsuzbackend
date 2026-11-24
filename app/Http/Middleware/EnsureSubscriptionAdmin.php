<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Server-side guard for subscription management (plans, features, plan assignment, subscribers, reports).
 * DEF-ADMIN and DEF-MASTERADMIN may pass; every other role gets 403 and a missing login gets 401, whatever the
 * Angular UI shows. Everything else master-admin-only keeps using EnsureMasterAdmin.
 */
class EnsureSubscriptionAdmin
{
    public const ROLES = ['DEF-ADMIN', 'DEF-MASTERADMIN'];

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }
        if (!in_array($user->role_code, self::ROLES, true)) {
            return response()->json(['success' => false, 'message' => 'Only an administrator can do this.'], 403);
        }

        return $next($request);
    }
}
