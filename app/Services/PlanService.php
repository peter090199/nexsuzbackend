<?php

namespace App\Services;

use App\Models\AccountPlan\UserSubscriptions;
use App\Models\AccountPlan\UserPlanDetails;
use Illuminate\Support\Facades\Cache;
use App\Models\AccountPlan\UserSubscriptionDetails;
use Illuminate\Http\Request;
use App\Models\AccountPlan\UserPlan;
use App\Models\GeneratesTransNo\ControllNumbers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\AccountPlan\UserSubscriptionsDetails;




class PlanService
{
    /** Accounts that can hold a subscription plan: job seekers and recruiters. Admins and master admins never do. */
    public const PLAN_ROLES = ['DEF-USERS', 'DEF-CLIENT'];

    public static function roleCanHavePlan(?string $role): bool
    {
        return in_array($role, self::PLAN_ROLES, true);
    }

    /**
     * Why this account may not take this plan, or null when it may.
     * Job seeker plans are for DEF-USERS, recruiter plans for DEF-CLIENT; DEF-ADMIN / DEF-MASTERADMIN get none.
     */
    public static function subscribeBlocker(?string $role, UserPlan $plan): ?string
    {
        if (!self::roleCanHavePlan($role)) {
            return 'Admin accounts do not use subscription plans.';
        }
        if ($plan->audience !== $role) {
            return $plan->audience === 'DEF-CLIENT'
                ? 'This plan is for recruiter accounts only.'
                : 'This plan is for job seeker accounts only.';
        }
        return null;
    }

    /**
     * The free starter plan for an audience: the active plan tagged STARTER, else the cheapest lowest-level active plan.
     */
    public static function starterPlan(string $role): ?UserPlan
    {
        $plans = UserPlan::where('recordStatus', 'active')->orderBy('plan_level')->orderBy('price')->get()
            ->filter(fn ($p) => $p->audience === $role)->values();
        return $plans->first(fn ($p) => strtoupper(trim((string) $p->tag)) === 'STARTER')
            ?? $plans->first(fn ($p) => (float) $p->price == 0.0);
    }

    /**
     * Gives a job seeker or recruiter the free starter plan when they have no active plan yet. Safe to call any time:
     * does nothing for admins, for accounts that already have a plan, or when no starter plan is published. Never throws.
     */
    public static function ensureStarterPlan(string $code, ?string $role = null): ?UserSubscriptions
    {
        try {
            $role ??= DB::table('users')->where('code', $code)->value('role_code');
            if (!self::roleCanHavePlan($role)) { return null; }
            // nested transaction = savepoint: a failure here rolls back only the plan, never the caller's registration
            return DB::transaction(function () use ($code, $role) {
                // serialise concurrent calls for the same account (repeated requests) on its user row
                DB::table('users')->where('code', $code)->lockForUpdate()->value('code');
                // any active OR suspended/cancelled-by-admin subscription is respected: never duplicate or override
                if (UserSubscriptions::where('code', $code)->where('is_active', 'active')->exists()) { return null; }
                $last = UserSubscriptions::where('code', $code)->orderByDesc('id')->first();
                if ($last && $last->is_active === 'cancelled' && Str::startsWith((string) $last->remarks, SubscriptionAdminService::SUSPENDED_TAG)) { return null; }
                $plan = self::starterPlan($role);
                if (!$plan) { return null; }
                return self::subscribeUser($code, $plan->planId, 'Free');
            });
        } catch (\Throwable $e) {
            Log::warning('Starter plan not activated: ' . $e->getMessage());
            return null;
        }
    }

    public static function activateFreePlan()
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $starter = self::starterPlan((string) $user->role_code);
            $existing = UserSubscriptions::where('code', $user->code)
                ->where('planId', $starter->planId ?? 'PLN000001')
                ->where('is_active', 'active')
                ->first();

            if ($existing) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Your Free Plan is already active.'
                ], 409);
            }
            $subscription = PlanService::subscribeUser($user->code, $starter->planId ?? 'PLN000001', 'Free');
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Free plan activated successfully.',
                'redirect_url' => "{$user->role}/home",
                'data' => $subscription
            ]);
        } catch (\DomainException $e) {   // not allowed for this account type
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public static function subscribeUser(string $code, string $planId, string $paymentMethod = 'Free')
    {
        $now = Carbon::now();
        // Prevent duplicate active subscription
        $existing = UserSubscriptions::where('code', $code)
            ->where('planId', $planId)
            ->where('is_active', 'active')
            ->first();

        if ($existing) {
            throw new \Exception('You already have an active subscription for free plan.');
        }

        $plan = UserPlan::where('planId', $planId)
            ->lockForUpdate()
            ->firstOrFail();

        // single choke point: every way of creating a subscription (checkout, free plan, admin assign) ends up here
        $blocked = self::subscribeBlocker(DB::table('users')->where('code', $code)->value('role_code'), $plan);
        if ($blocked) {
            throw new \DomainException($blocked);
        }

        $transNo = self::generateControlNumber('USER_SUBSCRIPTION');

        UserSubscriptions::where('code', $code)
            ->where('is_active', 'active')
            ->update([
                'is_active' => 'expired',
                'updated_at' => $now
            ]);

        UserSubscriptionDetails::where('code', $code)
            ->where('is_active', 'active')
            ->update([
                'is_active' => 'expired',
                'updated_at' => $now
            ]);

        $subscription = UserSubscriptions::create([
            'transNo'        => $transNo,
            'code'           => $code,
            'planId'         => $plan->planId,
            'plan_name'      => $plan->plan_name,
            'amount'         => $plan->price,
            'payment_method' => $paymentMethod,
            'start_date'     => $now,
            'end_date'       => $now->copy()->addMonth(),
            'is_active'      => 'active'
        ]);

        $planIds = UserPlan::where('plan_level', '<=', $plan->plan_level)->get()
            ->filter(fn ($p) => $p->audience === $plan->audience)
            ->pluck('planId');

        $features = UserPlanDetails::whereIn('planId', $planIds)
            ->where('recordStatus', 'active')
            ->whereNotNull('feature_code')
            ->get();

        $details = [];

        foreach ($features as $feature) {
            $details[] = [
                'transNo'       => $transNo,
                'code'          => $code,
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
        self::bust();
        return $subscription;
    }

    public static function getCurrentPlan(string $code)
    {
        return UserSubscriptions::where('code', $code)
            ->where('is_active', 'active')
            ->first();
    }

    /**
     * What an upgrade means in the enforced feature codes. A plan lists its perks by NAME ("Advanced Job Search"); the
     * checks in the app read a few fixed codes (SEARCH_ACCESS ...). Without this a Basic subscriber would still read
     * Free's "SEARCH_ACCESS = NO". A row that sets the real code itself always wins over one of these.
     */
    private const IMPLIES = [
        'ADVANCED_JOB_SEARCH'   => ['SEARCH_ACCESS' => 'YES'],
        'UNLIMITED_MESSAGING'   => ['MESSAGING_ACCESS' => 'YES'],
        '100_CONNECTIONS_LIMIT' => ['CONNECTION_LIMIT' => '100'],
        'UNLIMITED_CONNECTIONS' => ['CONNECTION_LIMIT' => '1000000'],
        'UNLIMITED_JOB'         => ['APPLICATION_LIMIT' => 'UNLIMITED'],
    ];

    /** call after ANY change to plans, plan features or subscriptions: everyone's features are recalculated */
    public static function bust(): void
    {
        Cache::forever('plan_global_ver', (int) Cache::get('plan_global_ver', 0) + 1);
    }

    /**
     * The features of a user's CURRENT plan, worked out live from the plan setup (the Features plan page), not from a
     * copy taken when they subscribed. A plan includes everything from the plans below it (by plan level); when two
     * plans define the same feature the higher plan wins. Returns one row per feature_code.
     */
    public static function effectiveFeatures(string $code): \Illuminate\Support\Collection
    {
        $ver = (int) Cache::get('plan_global_ver', 0);
        // keyed by the plan that really applies RIGHT NOW (see entitlement), so a plan expiring, being changed or being
        // suspended is seen on the very next request, not after the cache runs out
        $plan = self::entitlement($code)['plan'];

        return Cache::remember("plan_eff_{$code}_{$ver}_" . ($plan->planId ?? 'none'), now()->addMinutes(5), fn () => self::computeEffective($plan));
    }

    /**
     * Which plan an account's features come from today, and why. This is the one place that knows about expiry.
     *
     *  active    : the subscribed plan, until its end date. A free plan never runs out.
     *  expired   : a paid plan past its end date. The account falls back to the free starter plan of its account type
     *              (the paid features stop at once; nothing is deleted and renewing brings them back).
     *  suspended : the master admin suspended the subscription: no features at all.
     *  none      : no subscription row (an older account): the free starter plan, so nobody is locked out by a gap.
     *
     * @return array{status:string, plan:?UserPlan, subscribed_plan:?UserPlan, subscription:?UserSubscriptions, expires_at:?string}
     */
    public static function entitlement(string $code, ?string $role = null): array
    {
        $role ??= (string) DB::table('users')->where('code', $code)->value('role_code');
        $sub = UserSubscriptions::where('code', $code)->where('is_active', 'active')->orderByDesc('id')->first();
        $subscribed = $sub ? UserPlan::where('planId', $sub->planId)->first() : null;
        $out = fn (string $status, ?UserPlan $plan, ?string $ends) => [
            'status' => $status, 'plan' => $plan, 'subscribed_plan' => $subscribed, 'subscription' => $sub, 'expires_at' => $ends,
        ];

        if ($sub && $subscribed) {
            $free = (float) $subscribed->price == 0.0;
            $expired = !$free && $sub->end_date && Carbon::parse($sub->end_date)->isPast();

            return $expired
                ? $out('expired', self::starterPlan($role), $sub->end_date)
                : $out('active', $subscribed, $free ? null : $sub->end_date);
        }
        if (!$sub) {
            $last = UserSubscriptions::where('code', $code)->orderByDesc('id')->first();
            if ($last && $last->is_active === 'cancelled' && Str::startsWith((string) $last->remarks, SubscriptionAdminService::SUSPENDED_TAG)) {
                return $out('suspended', null, null);
            }
        }

        return $out('none', self::starterPlan($role), null);
    }

    private static function computeEffective(?UserPlan $plan): \Illuminate\Support\Collection
    {
        if (!$plan) { return collect(); }

        // a plan inherits the plans below it within its own audience (job seeker vs recruiter ladders are separate)
        $levels = UserPlan::where('plan_level', '<=', $plan->plan_level)->get()
            ->filter(fn ($p) => $p->audience === $plan->audience)->pluck('plan_level', 'planId');
        $rows = UserPlanDetails::whereIn('planId', $levels->keys())->where('recordStatus', 'active')
            ->whereNotNull('feature_code')->where('feature_code', '<>', '')
            // features switched off in the master-admin catalog are not offered to anyone
            ->when(\Illuminate\Support\Facades\Schema::hasTable('subscription_features'), fn ($q) => $q->whereNotIn(
                'feature_code',
                \App\Models\AccountPlan\SubscriptionFeature::where('is_active', false)->pluck('feature_code')
            ))
            ->get();

        $best = [];   // feature_code => [level, explicit, row]
        $offer = function (string $featureCode, $value, int $level, int $explicit, $row) use (&$best) {
            if (!isset($best[$featureCode]) || [$level, $explicit] > [$best[$featureCode][0], $best[$featureCode][1]]) {
                $best[$featureCode] = [$level, $explicit, $row, $value];
            }
        };
        foreach ($rows as $row) {
            $level = (int) ($levels[$row->planId] ?? 0);
            $offer($row->feature_code, $row->feature_value, $level, 1, $row);
            foreach ((self::IMPLIES[$row->feature_code] ?? []) as $impliedCode => $impliedValue) {
                $offer($impliedCode, $impliedValue, $level, 0, $row);
            }
        }

        return collect($best)->map(function ($b, $featureCode) {
            [$level, $explicit, $row, $value] = $b;
            return (object) [
                'planId' => $row->planId, 'plan_name' => $row->plan_name, 'fid' => $row->fid,
                'feature_code' => $featureCode, 'feature_name' => $row->feature_name, 'feature_value' => $value,
                'recordStatus' => 'active', 'is_active' => 'active',
            ];
        })->values();
    }

    public static function getFeatureValue(string $code, string $featureCode): mixed
    {
        try {
            return self::effectiveFeatures($code)->firstWhere('feature_code', $featureCode)->feature_value ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
    public static function getFeatureValuexxx(string $code, string $featureCode)
    {
        return Cache::remember(
            "feature_{$code}_{$featureCode}",
            now()->addHours(1),
            function () use ($code, $featureCode) {

                return UserSubscriptions::join(
                    'user_plan_details',
                    'user_subscriptions.planId',
                    '=',
                    'user_plan_details.planId'
                )
                    ->where('user_subscriptions.code', $code)
                    ->where('user_subscriptions.is_active', 'active')
                    ->where('user_plan_details.is_active', 'active')
                    ->where('user_plan_details.feature_code', $featureCode)
                    ->value('user_plan_details.feature_value');
            }
        );
    }
    /**
     * YES / NO FEATURES
     */
    public static function hasFeature(string $code, string $featureCode): bool
    {
        $value = self::getFeatureValue($code, $featureCode);

        return strtoupper((string) $value) === 'YES';
    }

    /**
     * GET NUMERIC LIMIT (e.g. 15 connections)
     */
    public static function getLimit(string $code, string $featureCode)
    {
        return (int) self::getFeatureValue($code, $featureCode);
    }

    /**
     * Check if user has unlimited job applications.
     */
    public function hasUnlimitedJobApplicationsxxx(string $code): bool
    {
        $value = $this->getFeature($code, 'UNLIMITED_JOB');
        return filter_var($value, FILTER_VALIDATE_BOOLEAN)
            || $value == 1
            || strtolower((string) $value) === 'yes'
            || strtolower((string) $value) === 'true'
            || strtolower((string) $value) === 'unlimited';
    }

    /** true when the plan lifts the application cap (Unlimited Job) */
    public function hasUnlimitedJobApplications(string $code): bool
    {
        return strtoupper((string) self::getFeatureValue($code, 'UNLIMITED_JOB')) === 'YES'
            || strtoupper((string) self::getFeatureValue($code, 'APPLICATION_LIMIT')) === 'UNLIMITED';
    }

    /**
     * The most jobs a user may apply to, or 0 when the plan sets no cap. A cap is set by adding the feature
     * APPLICATION_LIMIT (a number) to the plan on the Features plan page. (Before, "no cap set" was read as a cap of 0,
     * which stopped every Free user from applying.)
     */
    public function getApplicationLimit($code): int
    {
        $v = self::getFeatureValue($code, 'APPLICATION_LIMIT');
        return is_numeric($v) ? max(0, (int) $v) : 0;
    }
    public function getFeature($code, $featureCode)
    {
        $subscription = UserSubscriptions::with('plan.features.feature')
            ->where('code', $code)
            ->where('is_active', 'active')
            ->first();

        if (!$subscription || !$subscription->plan) {
            return null;
        }
        $planFeature = $subscription->plan->features
            ->first(function ($item) use ($featureCode) {
                return optional($item->feature)->code === $featureCode;
            });
        return $planFeature?->value;
    }

    /**
     * GET ACCESS TYPE (RESTRICTED / LIMITED / FULL)
     */
    public static function getAccessType($code, $featureCode)
    {
        return strtoupper((string) self::getFeatureValue($code, $featureCode));
    }

    /**
     * CONNECTION CHECK
     */
    public static function canConnect($code, $currentCount)
    {
        $limit = self::getLimit($code, 'CONNECTION_LIMIT');
        if (!$limit) {
            return false;
        }
        return $currentCount < $limit;
    }

    /**
     * SEARCH CHECK
     */
    public static function canSearch($code)
    {
        $access = self::getAccessType($code, 'SEARCH_ACCESS');
        return in_array($access, ['FULL', 'UNLIMITED', 'YES']);
    }

    /**
     * MESSAGING CHECK
     */
    public static function canMessage($code, $dailyCount = 0)
    {
        $access = self::getAccessType($code, 'MESSAGING_ACCESS');
        if (in_array($access, ['UNLIMITED', 'FULL', 'YES'])) {
            return true;
        }
        if ($access === 'LIMITED' && $dailyCount < 10) {
            return true;
        }
        return false;
    }

    public static function generatePlanId()
    {
        return self::generateControlNumber('TRANSNO');
    }
    public static function generateFeatureId()
    {
        return self::generateControlNumber('USER_PLAN');
    }
    public static function generateSubscriptionCode()
    {
        return self::generateControlNumber('USER_SUBSCRIPTION');
    }
    public static function generateControlNumber($module)
    {
        return DB::transaction(function () use ($module) {

            $control = ControllNumbers::where('module', $module)
                ->lockForUpdate()
                ->firstOrFail();

            $control->last_number += 1;
            $control->save();

            return $control->prefix .
                str_pad($control->last_number, 6, '0', STR_PAD_LEFT);
        });
    }

    public static function getAllFeatures($code)
    {
        return self::effectiveFeatures((string) $code);
    }
    public static function clearCache($code)
    {
        self::bust();
        Cache::forget("plan_feature_{$code}_CONNECTION_LIMIT");
        Cache::forget("plan_features_all_{$code}");
    }
}
