<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Server-side guard for subscription-plan management.
 * Only authenticated DEF-MASTERADMIN accounts may pass; every other role gets 403
 * regardless of what the Angular UI shows or hides.
 */
class EnsureMasterAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if ($user->role_code !== 'DEF-MASTERADMIN') {
            return response()->json([
                'success' => false,
                'message' => 'Only a master administrator can do this.'
            ], 403);
        }

        return $next($request);
    }
}
