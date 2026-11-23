<?php

namespace App\Http\Controllers\AccountPlan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccountPlan\UserPlan;
use App\Services\PlanService;
use Illuminate\Support\Facades\Auth;
use App\Models\AccountPlan\UserSubscriptions;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\AccountPlan\UserPlanDetails;
use App\Models\AccountPlan\UserSubscriptionDetails;

class UserPlanController extends Controller
{
    public function myFeatures()
    {
        if (!Auth::check()) {
            return response("Unauthenticated", 401);
        }
        $code = Auth::user()->code;

        // account plans apply to job seekers (DEF-USERS) and recruiters (DEF-CLIENT); admins and master admins have none
        if (!PlanService::roleCanHavePlan(Auth::user()->role_code ?? null)) {
            return response()->json([
                'success'       => true,
                'plan_applies'  => false,
                'plan_code'     => $code,
                'plan_id'       => null,
                'CurrentPlan'   => null,
                'features'      => []
            ]);
        }

        // What this account may use TODAY. One answer for job seekers and recruiters, from the entitlement (active /
        // expired / suspended / none) and the plan features the master admin assigned. The screens read `access` and
        // `messages`; `features` and the older keys are kept for the pages that already use them.
        $user = Auth::user();
        $ent = PlanService::entitlement($code, $user->role_code);

        return response()->json([
            'success'   => true,
            'plan_applies' => true,
            'plan_code' => $code,
            'plan_id'  => $ent['subscription']->planId ?? null,             // what they subscribed to (as before)
            'CurrentPlan' => $ent['subscription']->plan_name ?? null,
            'subscribed_plan' => $ent['subscribed_plan']->plan_name ?? null,
            'effective_plan_id' => $ent['plan']->planId ?? null,            // the plan whose features apply TODAY: it differs
            'effective_plan' => $ent['plan']->plan_name ?? null,            // from the subscription once that has expired
            'status'    => $ent['status'],                                  // active | expired | suspended | none
            'expires_at' => $ent['expires_at'] ? \Carbon\Carbon::parse($ent['expires_at'])->toIso8601String() : null,   // with its offset: the browser schedules the refresh from it
            'features'  => PlanService::getAllFeatures($code),
            'access'    => \App\Support\FeatureAccess::access($user),      // code => may use it
            'messages'  => \App\Support\FeatureAccess::deniedMessages($user), // code => why not (upgrade message)
        ]);
    }
    public function myFeaturesx()
    {
        if (!Auth::check()) {
            return response("authenticated", 401);
        }
        $code = Auth::user()->code;
        return response()->json([
            'success' => true,
            'plan_code' => $code,
            'features' => PlanService::getAllFeatures($code)
        ]);
    }

    private function generatePlanId()
    {
        $lastPlan = UserPlan::latest('id')->first();
        $nextNumber = $lastPlan ? $lastPlan->id + 1 : 1;
        return 'PLN' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /** Plans are keyed by planId (PLN000001) but the admin UI also sends the numeric row id. */
    private function findPlan($id): ?UserPlan
    {
        return UserPlan::where('planId', $id)->first()
            ?? (ctype_digit((string) $id) ? UserPlan::where('id', (int) $id)->first() : null);
    }

    /** Optional ?audience=DEF-USERS|DEF-CLIENT filter (no param = every plan, as before). */
    private function forAudience($plans, ?string $audience)
    {
        return in_array($audience, UserPlan::AUDIENCES, true)
            ? $plans->filter(fn ($p) => $p->audience === $audience)->values()
            : $plans;
    }

    private function isAdminUser(): bool
    {
        return in_array(Auth::user()->role_code ?? null, ['DEF-ADMIN', 'DEF-MASTERADMIN'], true);
    }

    /** Is this badge already used by another plan of the same audience? (legacy blank role_code counts as job seekers) */
    private function tagTaken(?string $tag, string $audience, ?string $exceptPlanId = null): bool
    {
        return UserPlan::where('tag', $tag)
            ->when($exceptPlanId, fn ($q) => $q->where('planId', '!=', $exceptPlanId))
            ->get()
            ->contains(fn ($p) => $p->audience === $audience);
    }

    public function save(Request $request)
    {
        $request->validate([
            'plan_name' => 'required|string|max:100',
            'button_name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'sort_number' => 'required|numeric|min:1',
            'tag' => 'required|string|max:50',
            'tagmonthYear' => 'nullable|in:Monthly,Yearly',
            'recordStatus' => 'nullable|in:active,inactive',
            'audience' => 'nullable|in:DEF-USERS,DEF-CLIENT',
        ]);

        // ✅ CHECK DUPLICATE TAG
        // (a badge only has to be unique within the same audience, so recruiters can have their own STARTER / PREMIUM)
        $audience = $request->input('audience', 'DEF-USERS');
        if ($this->tagTaken($request->tag, $audience)) {
            return response()->json([
                'success' => false,
                'exists' => true,
                'message' => 'A ' . ($audience === 'DEF-CLIENT' ? 'recruiter' : 'job seeker') . ' plan with this badge already exists.'
            ], 409);
        }

        $plan = UserPlan::create([
            'planId' => $this->generatePlanId(),
            'plan_name' => $request->plan_name,
            'button_name' => $request->button_name,
            'sort_number' => $request->sort_number,
            'price' => $request->price,
            'tagmonthYear' => $request->tagmonthYear ?? 'Monthly',
            'tag' => $request->tag,
            'button_color' => $request->button_color,
            'description' => $request->description,
            'recordStatus' => $request->recordStatus ?? 'active',
            'role_code' => $request->input('audience', 'DEF-USERS'),
            // new plans sit above the current top tier so feature inheritance (plan_level <=) stays correct
            'plan_level' => $request->plan_level ?? ((int) UserPlan::max('plan_level') + 1)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plan created successfully.',
            'data' => $plan
        ]);
    }

    public function save1(Request $request)
    {
        $request->validate([
            'plan_name' => 'required',
            'button_name' => 'required',
            'price' => 'required|numeric',
            'sort_number' => 'required|numeric',
        ]);

        $exists = UserPlan::where('tag', $request->tag)->first();
        if ($exists) {
            return response()->json([
                'exists' => false,
                'message' => 'Plan tag already exists.'
            ], 409);
        }


        $plan = UserPlan::create([
            'planId' => $this->generatePlanId(),
            'plan_name' => $request->plan_name,
            'button_name' => $request->button_name,
            'sort_number' => $request->sort_number,
            'price' => $request->price,
            'tagmonthYear' => $request->tagmonthYear,
            'tag' => $request->tag,
            'button_color' => $request->button_color,
            'description' => $request->description,
            'recordStatus' => 'active'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plan created successfully.',
            'data' => $plan
        ]);
    }

    public function getPlanpublic(Request $request)
    {
        $plans = UserPlan::where('recordStatus', 'active')
            ->orderBy('sort_number', 'asc')
            ->get();
        $plans = $this->forAudience($plans, $request->query('audience'));

        foreach ($plans as $plan) {
            $plan->button_name = 'Get Started';
            $plan->disabled = false;
        }

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    public function index(Request $request)
    {
        // Master admins managing plans can ask for inactive plans too (?all=1).
        $includeInactive = $request->boolean('all')
            && Auth::check()
            && Auth::user()->role_code === 'DEF-MASTERADMIN';

        $plans = UserPlan::when(!$includeInactive, fn($q) => $q->where('recordStatus', 'active'))
            ->orderBy('sort_number', 'asc')
            ->get();
        $plans = $this->forAudience($plans, $request->query('audience'));

        if ($includeInactive) {
            $counts = UserSubscriptions::where('is_active', 'active')
                ->select('planId', DB::raw('COUNT(*) as c'))
                ->groupBy('planId')
                ->pluck('c', 'planId');

            foreach ($plans as $plan) {
                $plan->setAttribute('subscribers', (int) ($counts[$plan->planId] ?? 0));
            }
        }

        foreach ($plans as $plan) {
            $plan->setAttribute('is_popular', strtoupper(trim((string) $plan->tag)) === 'MOST POPULAR');
        }

        $currentSubscription = null;
        if (Auth::check()) {

            $currentSubscription = UserSubscriptions::where('code', Auth::user()->code)
                ->where('is_active', 'active')
                ->first();

            if ($currentSubscription) {

                foreach ($plans as $plan) {
                    $plan->setAttribute('is_current', $plan->planId == $currentSubscription->planId);
                }

                $currentPlan = UserPlan::where('planId', $currentSubscription->planId)
                    ->first();

                if ($currentPlan) {

                    foreach ($plans as $plan) {

                        // Current Plan
                        if ($plan->planId == $currentPlan->planId) {
                            $plan->button_name = 'Current Plan';
                            $plan->disabled = true;
                        }
                        // Downgrade
                        elseif ($plan->plan_level < $currentPlan->plan_level) {
                            $plan->button_name = 'Downgrade';
                            $plan->disabled = true;
                        }
                        // Upgrade
                        else {
                            $plan->disabled = false;
                        }
                    }
                }
            }
        }

        // Nobody has a "current plan" without an active subscription (a master admin, or someone who has not picked one).
        // The Free plan's stored button text is "Current Plan", which must not leak out as if it were theirs.
        if (!$currentSubscription) {
            foreach ($plans as $plan) {
                $plan->setAttribute('is_current', false);
                if (strcasecmp(trim((string) $plan->button_name), 'Current Plan') === 0) {
                    $plan->button_name = 'Get started free';
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }


    public function indexx()
    {
        $plans = UserPlan::where('recordStatus', 'active')
            ->orderBy('sort_number', 'asc')
            ->get();
        if (Auth::check()) {
            $currentSubscription = UserSubscriptions::where('code', Auth::user()->code)
                ->where('is_active', 'active')
                ->first();

            if ($currentSubscription) {

                foreach ($plans as $plan) {

                    if ($plan->planId == $currentSubscription->planId) {
                        $plan->button_name = 'Current Plan';
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }



    public function show($planId)
    {
        $plan = UserPlan::where('planId', $planId)->first();

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found.'
            ], 404);
        }

        // Admin screens may still need inactive plans; checkout must not offer them.
        $isAdmin = Auth::check() && $this->isAdminUser();

        if ($plan->recordStatus !== 'active' && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'This plan is no longer available.'
            ], 410);
        }

        $plan->setAttribute('features', UserPlanDetails::where('planId', $plan->planId)
            ->where('recordStatus', 'active')
            ->orderBy('id')
            ->get(['fid', 'feature_code', 'feature_name', 'feature_value']));

        return response()->json([
            'success' => true,
            'data' => $plan
        ]);
    }

    /**
     * UPDATE
     */
    public function update(Request $request, $id)
    {
        $plan = $this->findPlan($id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found.'
            ], 404);
        }

        $request->validate([
            'plan_name' => 'required|string|max:100',
            'button_name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'sort_number' => 'nullable|numeric|min:1',
            'tag' => 'nullable|string|max:50',
            'tagmonthYear' => 'nullable|in:Monthly,Yearly',
            'recordStatus' => 'nullable|in:active,inactive',
            'audience' => 'nullable|in:DEF-USERS,DEF-CLIENT',
        ]);

        $audience = $request->input('audience', $plan->audience);
        if ($request->filled('tag') && $this->tagTaken($request->tag, $audience, $plan->planId)) {
            return response()->json([
                'success' => false,
                'exists' => true,
                'message' => 'A ' . ($audience === 'DEF-CLIENT' ? 'recruiter' : 'job seeker') . ' plan with this badge already exists.'
            ], 409);
        }

        // Fields that are not sent keep their current value (no accidental blanking)
        $plan->update([
            'plan_name' => $request->plan_name,
            'button_name' => $request->button_name,
            'sort_number' => $request->input('sort_number', $plan->sort_number),
            'price' => $request->price,
            'tagmonthYear' => $request->input('tagmonthYear', $plan->tagmonthYear),
            'tag' => $request->input('tag', $plan->tag),
            'button_color' => $request->input('button_color', $plan->button_color),
            'description' => $request->input('description', $plan->description),
            'recordStatus' => $request->recordStatus ?? $plan->recordStatus,
            'role_code' => $request->input('audience', $plan->audience)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plan updated successfully.',
            'data' => $plan
        ]);
    }

    /**
     * DELETE
     */
    public function destroy($id)
    {
        $plan = $this->findPlan($id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found.'
            ], 404);
        }

        // Never delete a plan that has subscription history: it would orphan billing records.
        if (UserSubscriptions::where('planId', $plan->planId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This plan has subscription records and cannot be deleted. Disable it instead.'
            ], 409);
        }

        DB::transaction(function () use ($plan) {
            UserPlanDetails::where('planId', $plan->planId)->delete();
            $plan->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Plan deleted successfully.'
        ]);
    }

    /**
     * DISABLE: hides the plan from new subscribers. Existing subscriptions and
     * payment records are untouched.
     */
    public function deactivate($id)
    {
        $plan = $this->findPlan($id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found.'
            ], 404);
        }

        // Keep at least one plan available to subscribers
        $otherActive = UserPlan::where('recordStatus', 'active')
            ->where('planId', '!=', $plan->planId)
            ->exists();

        if (!$otherActive) {
            return response()->json([
                'success' => false,
                'message' => 'At least one plan must stay enabled.'
            ], 409);
        }

        $plan->update([
            'recordStatus' => 'inactive'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plan disabled successfully.'
        ]);
    }

    public function activate($id)
    {
        $plan = $this->findPlan($id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found.'
            ], 404);
        }

        $plan->update(['recordStatus' => 'active']);

        return response()->json([
            'success' => true,
            'message' => 'Plan enabled successfully.'
        ]);
    }

    /**
     * MOST POPULAR: only one plan carries the badge. The previous holder goes back to a
     * tag derived from its name.
     */
    public function setPopular($id)
    {
        $plan = $this->findPlan($id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found.'
            ], 404);
        }

        DB::transaction(function () use ($plan) {
            // one Most Popular plan per audience: job seekers and recruiters each have their own
            UserPlan::where('tag', 'MOST POPULAR')
                ->where('planId', '!=', $plan->planId)
                ->get()
                ->filter(fn ($p) => $p->audience === $plan->audience)
                ->each(fn($p) => $p->update(['tag' => strtoupper($p->plan_name)]));

            $plan->update(['tag' => 'MOST POPULAR']);
        });

        return response()->json([
            'success' => true,
            'message' => $plan->plan_name . ' is now the Most Popular plan.'
        ]);
    }

    /** Active subscription of one user (used before an admin changes their plan). */
    public function userSubscription($code)
    {
        $user = DB::table('users')->where('code', $code)->first(['code', 'fullname', 'email', 'role_code']);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        $sub = UserSubscriptions::where('code', $code)->where('is_active', 'active')->first();

        return response()->json([
            'success' => true,
            'user' => $user,
            'subscription' => $sub ? [
                'transNo' => $sub->transNo,
                'planId' => $sub->planId,
                'plan_name' => $sub->plan_name,
                'start_date' => $sub->start_date,
                'end_date' => $sub->end_date,
            ] : null
        ]);
    }

    /**
     * Admin plan change for a user (upgrade / downgrade / assign).
     * This is NOT a payment: it records payment_method ADMIN_ASSIGNED with amount 0 and the
     * acting admin in remarks. The previous subscription is expired, not deleted.
     */
    public function assign(Request $request)
    {
        $request->validate([
            'code' => 'required|exists:users,code',
            'planId' => 'required|string|exists:userplanheader,planId',
            'remarks' => 'nullable|string|max:255',
        ]);

        $plan = UserPlan::where('planId', $request->planId)->first();

        if ($plan->recordStatus !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This plan is disabled. Enable it before assigning it.'
            ], 409);
        }

        // the target must be a job seeker / recruiter whose type matches the plan (admins cannot hold a plan)
        $blocked = PlanService::subscribeBlocker(DB::table('users')->where('code', $request->code)->value('role_code'), $plan);
        if ($blocked) {
            return response()->json(['success' => false, 'message' => $blocked], 409);
        }

        $current = UserSubscriptions::where('code', $request->code)->where('is_active', 'active')->first();

        if ($current && $current->planId === $plan->planId) {
            return response()->json([
                'success' => false,
                'message' => 'This user is already on this plan.'
            ], 409);
        }

        try {
            $subscription = DB::transaction(function () use ($request, $plan) {
                $sub = PlanService::subscribeUser($request->code, $plan->planId, 'ADMIN_ASSIGNED');

                $sub->update([
                    'amount' => 0,
                    'remarks' => trim('Assigned by ' . Auth::user()->code . '. ' . ($request->remarks ?? ''))
                ]);

                return $sub;
            });
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to change the subscription. Please try again.'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Subscription changed to ' . $plan->plan_name . '.',
            'data' => [
                'transNo' => $subscription->transNo,
                'planId' => $subscription->planId,
                'plan_name' => $subscription->plan_name,
                'end_date' => $subscription->end_date,
            ]
        ]);
    }

    public function upgrade(Request $request)
    {
        $request->validate([
            'planId' => 'required|exists:userplanheader,planId',
            'payment_method' => 'required|string|max:50',
        ]);

        DB::beginTransaction();

        try {

            $user = Auth::user();
            $now = Carbon::now();

            // Lock selected plan
            $plan = UserPlan::where('planId', $request->planId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($plan->recordStatus !== 'active') {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'This plan is no longer available.'
                ], 410);
            }

            // Job seekers take job seeker plans, recruiters take recruiter plans, admins take none
            if ($blocked = PlanService::subscribeBlocker($user->role_code, $plan)) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => $blocked], 403);
            }

            // Check if already subscribed
            $activeSubscription = UserSubscriptions::where('code', $user->code)
                ->where('is_active', 'active')
                ->lockForUpdate()
                ->first();

            if ($activeSubscription && $activeSubscription->planId === $plan->planId) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'You are already subscribed to this plan.'
                ], 409);
            }

            $transNo = PlanService::generateControlNumber('USER_SUBSCRIPTION');

            // Expire previous subscription
            UserSubscriptions::where('code', $user->code)
                ->where('is_active', 'active')
                ->update([
                    'is_active' => 'expired',
                    'updated_at' => $now
                ]);

            // Expire previous features
            UserSubscriptionDetails::where('code', $user->code)
                ->where('is_active', 'active')
                ->update([
                    'is_active' => 'expired',
                    'updated_at' => $now
                ]);

            // Create subscription
            $subscription = UserSubscriptions::create([
                'transNo'        => $transNo,
                'code'           => $user->code,
                'planId'         => $plan->planId,
                'plan_name'      => $plan->plan_name,
                'amount'         => $plan->price,
                'payment_method' => $request->payment_method,
                'start_date'     => $now,
                'end_date'       => $now->copy()->addMonth(),
                'is_active'      => 'active'
            ]);

            // Get inherited plan IDs (lower plans of the same audience only)
            $planIds = UserPlan::where('plan_level', '<=', $plan->plan_level)->get()
                ->filter(fn ($p) => $p->audience === $plan->audience)
                ->pluck('planId');

            // Get active features
            $features = UserPlanDetails::whereIn('planId', $planIds)
                ->where('recordStatus', 'active')
                ->whereNotNull('feature_code')
                ->get();

            $details = [];

            foreach ($features as $feature) {

                $details[] = [
                    'transNo'       => $transNo,
                    'code'          => $user->code,
                    'planId'        => $feature->planId,
                    'plan_name'     => $feature->plan_name,
                    'fid'           => $feature->fid,
                    'feature_code'  => $feature->feature_code,
                    'feature_name'  => $feature->feature_name,
                    'feature_value' => $feature->feature_value,
                    'is_active'     => 'active',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }

            if (!empty($details)) {
                UserSubscriptionDetails::insert($details);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Subscription upgraded successfully.',
                'data' => [
                    'transNo' => $subscription->transNo,
                    'planId' => $subscription->planId,
                    'plan_name' => $subscription->plan_name,
                    'start_date' => $subscription->start_date,
                    'end_date' => $subscription->end_date
                ]
            ], 200);
        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to process your subscription. Please try again.'
            ], 500);
        }
    }

    // public function upgrade(Request $request)
    // {
    //     $request->validate([
    //         'planId' => 'required'
    //     ]);
    //     DB::beginTransaction();
    //     try {
    //         $userCode = Auth::user()->code;
    //         $transNo = PlanService::generatePlanId();

    //         $plan = UserPlan::where('planId', $request->planId)->firstOrFail();
    //         // Expire old subscription
    //         UserSubscriptions::where('code', $userCode)
    //             ->where('is_active', 'active')
    //             ->update([
    //                 'is_active' => 'expired'
    //             ]);

    //         UserSubscriptionDetails::where('code', $userCode)
    //             ->where('is_active', 'active')
    //             ->update([
    //                 'is_active' => 'expired'
    //             ]);

    //         // Create new subscription
    //         $subscription = UserSubscriptions::create([
    //             'transNo' => $transNo,
    //             'code' => $userCode,
    //             'planId' => $plan->planId,
    //             'amount' => $plan->price,
    //             'payment_method' => $request->payment_method,
    //             'transaction_id' => $request->transaction_id,
    //             'start_date' => Carbon::now(),
    //             'end_date' => Carbon::now()->addMonth(),
    //             'is_active' => 'active'
    //         ]);

    //         // Get inherited plan IDs
    //         $planIds = UserPlan::where('plan_level', '<=', $plan->plan_level)
    //             ->pluck('planId');

    //         // Get all inherited features
    //         $features = UserPlanDetails::whereIn('planId', $planIds)
    //             ->where('recordStatus', 'active')
    //             ->get();

    //         // Save snapshot
    //         foreach ($features as $feature) {
    //             UserSubscriptionDetails::create([
    //                 'transNo' => $transNo,
    //                 'code'          => $userCode,
    //                 'planId'        => $feature->planId,
    //                 'plan_name'     => $feature->plan_name,
    //                 'fid'           => $feature->fid,
    //                 'feature_code'  => $feature->feature_code,
    //                 'feature_name'  => $feature->feature_name,
    //                 'feature_value' => $feature->feature_value,
    //                 'is_active'  => 'active'
    //             ]);
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Subscription upgraded successfully.',
    //             'subscription' => $subscription
    //         ]);
    //     } catch (\Exception $e) {

    //         DB::rollBack();

    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }


    public function upgradexx(Request $request)
    {
        $request->validate([
            'planId' => 'required'
        ]);

        $userCode = Auth::user()->code;
        $plan = UserPlan::where('planId', $request->planId)->firstOrFail();
        UserSubscriptions::where('code', $userCode)
            ->where('is_active', 'active')
            ->update([
                'is_active' => 'expired'
            ]);

        $subscription = UserSubscriptions::create([
            'code' => $userCode,
            'planId' => $plan->planId,
            'amount' => $plan->price,
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonth(),
            'is_active' => 'active'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription upgraded successfully.',
            'subscription' => $subscription
        ]);
    }
}
