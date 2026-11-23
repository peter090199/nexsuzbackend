<?php

namespace App\Http\Controllers\AccountPlan;

use App\Http\Controllers\Controller;
use App\Services\PlanCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlanOverviewController extends Controller
{
    /**
     * GET plans/overview[?audience=DEF-USERS|DEF-CLIENT]
     * The plans (with features and limits) for the signed-in account's own audience. Job seekers always get job seeker
     * plans and recruiters recruiter plans; the audience parameter only matters for admins, who have no plan of their own.
     */
    public function index(Request $request, PlanCatalogService $catalog)
    {
        $request->validate(['audience' => 'nullable|in:DEF-USERS,DEF-CLIENT']);
        $user = Auth::user();

        $audience = in_array($user->role_code, ['DEF-USERS', 'DEF-CLIENT'], true)
            ? $user->role_code
            : ($request->query('audience') ?: 'DEF-USERS');

        return response()->json(['success' => true, 'data' => $catalog->forAudience($audience, $user)]);
    }
}
