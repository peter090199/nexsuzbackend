<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use App\Models\Roleaccessmenu;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Server-side guard for a menu page, e.g. ->middleware('menuaccess:contactinquiries').
 * Master Admin always passes; Admin passes only while that menu is assigned to DEF-ADMIN in Security Roles
 * (the same assignment that puts it in their sidebar). Everyone else, and guests, are refused no matter what the UI shows.
 */
class EnsureMenuAccess
{
    public function handle(Request $request, Closure $next, string $route)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }
        if ($user->role_code === 'DEF-MASTERADMIN' || ($user->role_code === 'DEF-ADMIN' && $this->assigned($user->role_code, $route))) {
            return $next($request);
        }

        return response()->json(['success' => false, 'message' => 'You do not have access to this page.'], 403);
    }

    private function assigned(string $role, string $route): bool
    {
        $ids = Menu::where('routes', $route)->where('status', 'A')->pluck('id');

        return $ids->isNotEmpty() && Roleaccessmenu::where('rolecode', $role)->whereIn('menus_id', $ids)->exists();
    }
}
