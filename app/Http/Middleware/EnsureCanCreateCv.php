<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Server-side rule for who may create or change CV content.
 *
 * Only DEF-USERS accounts own a CV. DEF-CLIENT, DEF-MASTERADMIN and DEF-ADMIN get 403 here no matter
 * what the Angular UI shows, so hiding the button is never the only control.
 *
 * All CV write endpoints save to the signed-in account (Auth::user()->code), so a request can never
 * write another account's CV.
 */
class EnsureCanCreateCv
{
    /** roles allowed to create / edit their own CV */
    public const ROLES = ['DEF-USERS'];

    /**
     * Administrators have no CV, but they do have the basic profile of Settings (contact details, location, summary,
     * visibility). They may create that profile row without a photo and without any CV data, and edit it later.
     * They still can never write CV sections: that stays behind allows() / the cvwriter middleware.
     */
    public const PROFILE_ONLY_ROLES = ['DEF-ADMIN', 'DEF-MASTERADMIN'];

    public static function allows($user): bool
    {
        return $user && in_array($user->role_code, self::ROLES, true);
    }

    public static function allowsProfileOnly($user): bool
    {
        return $user && in_array($user->role_code, self::PROFILE_ONLY_ROLES, true);
    }

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if (!self::allows($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Your account type cannot create or edit a CV.'
            ], 403);
        }

        return $next($request);
    }
}
