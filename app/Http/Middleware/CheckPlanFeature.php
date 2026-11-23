<?php

namespace App\Http\Middleware;

use App\Services\PlanService;
use App\Support\FeatureAccess;
use Closure;
use Illuminate\Http\Request;

/**
 * planfeature:CODE[,CODE2]  — the account's subscription must include every listed feature.
 *
 *  - only job seekers (DEF-USERS) and recruiters (DEF-CLIENT) are checked, and only for the account types the feature
 *    is enforced for (see FeatureAccess). Admins and master admins always pass: they are never plan-limited.
 *  - this is an ADDITION to the role rules (cvwriter, masteradmin ...), not a replacement: put it after them.
 *  - the answer is 403 { success:false, code:'feature_locked', feature, message, upgrade:true, plan_status } so the
 *    screen can show the upgrade message and refresh what the account may use.
 *
 * (This used to pass the user's database id where the account code was expected, so it could never have matched.)
 */
class CheckPlanFeature
{
    public function handle(Request $request, Closure $next, string ...$codes)
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);        // the auth middleware answers for a missing sign-in
        }

        foreach ($codes as $code) {
            if (!FeatureAccess::allows($user, $code)) {
                return response()->json([
                    'success' => false,
                    'code' => 'feature_locked',
                    'feature' => $code,
                    'message' => FeatureAccess::deniedMessage($user, $code),
                    'upgrade' => true,
                    'plan_status' => PlanService::entitlement((string) $user->code, $user->role_code)['status'],
                ], 403);
            }
        }

        return $next($request);
    }
}
