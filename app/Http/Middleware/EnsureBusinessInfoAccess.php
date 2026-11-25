<?php

namespace App\Http\Middleware;

use App\Models\Roleaccesssubmenu;
use App\Models\Submenu;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Business information management: Master Admin always; Admin only when the "nexsuzinfo" submenu is assigned to the
 * DEF-ADMIN role in Security Roles (the same assignment that shows the page in their sidebar). Everyone else: 403.
 */
class EnsureBusinessInfoAccess
{
    public const ROUTE = 'nexsuzinfo';

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }
        if ($user->role_code === 'DEF-MASTERADMIN' || ($user->role_code === 'DEF-ADMIN' && $this->assigned($user->role_code))) {
            return $next($request);
        }

        return response()->json(['success' => false, 'message' => 'You do not have access to the business information.'], 403);
    }

    private function assigned(string $role): bool
    {
        $ids = Submenu::where('routes', self::ROUTE)->where('status', 'A')->pluck('id');

        return $ids->isNotEmpty() && Roleaccesssubmenu::where('rolecode', $role)->whereIn('submenus_id', $ids)->exists();
    }
}
