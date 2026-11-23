<?php

namespace App\Http\Controllers\SubscriptionAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionAdmin\SubscriberActionRequest;
use App\Http\Resources\SubscriptionAdmin\SubscriberResource;
use App\Models\AccountPlan\UserPlan;
use App\Services\PlanService;
use App\Services\SubscriptionAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** User subscription monitoring and lifecycle actions. */
class SubscriberController extends Controller
{
    public function __construct(private SubscriptionAdminService $service) { }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status'   => 'nullable|in:active,expired,suspended,pending,none',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $page = $this->service->subscriberQuery($request->only(['search', 'plan_id', 'status']))
            ->paginate((int) $request->input('per_page', 10));

        return response()->json([
            'success' => true,
            'data'    => SubscriberResource::collection($page->items()),
            'meta'    => ['total' => $page->total(), 'per_page' => $page->perPage(),
                'current_page' => $page->currentPage(), 'last_page' => $page->lastPage()],
            'counts'  => $this->counts(),
        ]);
    }

    /** Header cards: status counts over all users, not just the current page. */
    private function counts(): array
    {
        $rows = $this->service->subscriberQuery([])->get();
        $status = fn ($r) => SubscriptionAdminService::statusOf($r->sub_id ? $r : null);
        $by = $rows->groupBy($status)->map->count();
        $soon = $rows->filter(fn ($r) => $status($r) === 'active' && strtotime($r->end_date) <= strtotime('+14 days'))->count();

        return ['all' => $rows->count(), 'active' => (int) ($by['active'] ?? 0), 'expired' => (int) ($by['expired'] ?? 0),
            'suspended' => (int) ($by['suspended'] ?? 0), 'expiring_soon' => $soon];
    }

    public function show(string $code): JsonResponse
    {
        $row = $this->service->subscriberQuery([])->where('u.code', $code)->first();
        abort_if(!$row, 404, 'User not found.');

        return response()->json([
            'success' => true,
            'data'    => (new SubscriberResource($row))->toArray(request()) + [
                'usage'   => $this->service->usageFor($code, $row->planId),
                'history' => $this->service->history($code),
            ],
        ]);
    }

    public function changePlan(SubscriberActionRequest $request, string $code): JsonResponse
    {
        $role = DB::table('users')->where('code', $code)->value('role_code');
        abort_if(!DB::table('users')->where('code', $code)->exists(), 404, 'User not found.');
        $plan = UserPlan::where('planId', $request->plan_id)->firstOrFail();
        abort_if($plan->recordStatus !== 'active', 409, 'This plan is disabled. Enable it before assigning it.');
        abort_if($blocked = PlanService::subscribeBlocker($role, $plan), 409, (string) $blocked);

        $current = $this->service->subscriberQuery([])->where('u.code', $code)->first();
        abort_if($current && $current->planId === $plan->planId && SubscriptionAdminService::statusOf($current) === 'active',
            409, 'This user is already on this plan.');

        DB::transaction(function () use ($request, $code, $plan) {
            $sub = PlanService::subscribeUser($code, $plan->planId, 'ADMIN_ASSIGNED');
            $sub->update(['amount' => 0, 'remarks' => trim('Assigned by ' . Auth::user()->code . '. ' . ($request->remarks ?? ''))]);
        });

        return response()->json(['success' => true, 'message' => 'Subscription changed to ' . $plan->plan_name . '.']);
    }

    public function extend(SubscriberActionRequest $request, string $code): JsonResponse
    {
        $sub = $this->service->extend($code, (int) $request->days);

        return response()->json(['success' => true, 'message' => 'Subscription extended.', 'data' => ['end_date' => $sub->end_date]]);
    }

    public function suspend(SubscriberActionRequest $request, string $code): JsonResponse
    {
        $this->service->suspend($code, $request->reason);

        return response()->json(['success' => true, 'message' => 'Subscription suspended.']);
    }

    public function reactivate(string $code): JsonResponse
    {
        $this->service->reactivate($code);

        return response()->json(['success' => true, 'message' => 'Subscription reactivated.']);
    }
}
