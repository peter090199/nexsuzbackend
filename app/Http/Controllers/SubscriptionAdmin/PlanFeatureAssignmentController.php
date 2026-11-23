<?php

namespace App\Http\Controllers\SubscriptionAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionAdmin\SaveAssignmentsRequest;
use App\Models\AccountPlan\UserPlan;
use App\Services\SubscriptionAdminService;
use Illuminate\Http\JsonResponse;

/** Which features each plan includes, with numeric limits. */
class PlanFeatureAssignmentController extends Controller
{
    public function __construct(private SubscriptionAdminService $service) { }

    /** Matrix for one plan: one cell per catalog feature. */
    public function show(string $planId): JsonResponse
    {
        UserPlan::where('planId', $planId)->firstOrFail();

        return response()->json(['success' => true, 'data' => $this->service->assignmentMatrix($planId)]);
    }

    /** Every plan at once (read-only overview grid on the page). */
    public function overview(): JsonResponse
    {
        $data = UserPlan::orderBy('sort_number')->get()
            ->mapWithKeys(fn ($p) => [$p->planId => $this->service->assignmentMatrix($p->planId)]);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(SaveAssignmentsRequest $request, string $planId): JsonResponse
    {
        $plan = UserPlan::where('planId', $planId)->firstOrFail();
        $this->service->saveAssignments($plan, $request->validated()['items']);

        return response()->json([
            'success' => true,
            'message' => $plan->plan_name . ' features saved.',
            'data'    => $this->service->assignmentMatrix($planId),
        ]);
    }
}
