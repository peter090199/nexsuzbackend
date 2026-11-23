<?php

namespace App\Services;

use App\Models\AccountPlan\SubscriptionFeature;
use App\Models\AccountPlan\UserPlan;
use App\Models\AccountPlan\UserPlanDetails;

/**
 * The plan list a job seeker or recruiter sees (Settings → Subscription Plan), built from exactly what the master admin
 * manages on Subscription Plans / Feature Management / Plan Feature Assignment:
 *  - only active plans of the caller's own audience (job seeker plans for DEF-USERS, recruiter plans for DEF-CLIENT)
 *  - each plan's features with limits ("Unlimited", a number), a plan inheriting what the lower plans of its audience include
 *  - features switched off in the catalog never show
 *  - plan rows that carry no feature code (older, name-only features such as the Business plan's list) still show
 * The inheritance rule is the one PlanService enforces: the highest plan that sets a feature wins, and "NO" overrides.
 */
class PlanCatalogService
{
    /**
     * @return array{audience:string, plans:array<int,array>}
     */
    public function forAudience(string $audience, ?object $user = null): array
    {
        // job seekers and recruiters always have at least the free starter plan
        if ($user && PlanService::roleCanHavePlan($user->role_code) && $user->role_code === $audience) {
            PlanService::ensureStarterPlan((string) $user->code, $user->role_code);
        }

        $plans = UserPlan::where('recordStatus', 'active')->orderBy('plan_level')->orderBy('sort_number')->get()
            ->filter(fn (UserPlan $p) => $p->audience === $audience)->values();

        $catalog = SubscriptionFeature::all()->keyBy(fn ($f) => strtoupper($f->feature_code));
        $rows = UserPlanDetails::whereIn('planId', $plans->pluck('planId'))->where('recordStatus', 'active')->orderBy('id')->get()->groupBy('planId');

        // pass 1: the effective state of every feature at each plan level, in order
        $order = [];          // key => position (first time it appears across the plans)
        $meta = [];           // key => name/category/icon
        $perPlan = [];        // planId => key => state
        $state = [];
        foreach ($plans as $plan) {
            foreach ($rows->get($plan->planId, collect()) as $row) {
                if ($this->isInheritanceRow($row)) { continue; }
                $code = strtoupper(trim((string) $row->feature_code));
                $key = $code !== '' ? $code : 'NAME:' . strtoupper(trim((string) $row->feature_name));
                $cat = $code !== '' ? ($catalog[$code] ?? null) : null;
                if ($cat && !$cat->is_active) { continue; }                       // switched off in the catalog: nobody is offered it

                $cell = $code !== '' ? SubscriptionAdminService::decode($row) : ['enabled' => true, 'limit' => null, 'unlimited' => false];
                $state[$key] = $cell;
                $order[$key] ??= count($order);
                $meta[$key] ??= [
                    'name' => $cat->name ?? (string) $row->feature_name,
                    'category' => $cat->category ?? 'Other',
                    'icon' => $cat->icon ?? 'check_circle',
                    'key' => $code !== '' ? $code : null,
                ];
            }
            $perPlan[$plan->planId] = $state;     // arrays are copied by value: this is a snapshot for this level
        }
        asort($order);

        // the caller's current plan, to mark it and to lock downgrades
        $current = $user ? PlanService::getCurrentPlan((string) $user->code) : null;
        $currentPlan = $current ? $plans->firstWhere('planId', $current->planId) : null;

        // pass 2: one list per plan over every feature that appears anywhere, so lower plans show what they lack
        $out = [];
        foreach ($plans as $plan) {
            $features = [];
            foreach (array_keys($order) as $key) {
                $cell = $perPlan[$plan->planId][$key] ?? ['enabled' => false, 'limit' => null, 'unlimited' => false];
                $detail = '';
                if ($cell['enabled']) {
                    $detail = $cell['unlimited'] ? 'Unlimited' : ($cell['limit'] !== null ? (string) $cell['limit'] : '');
                    // "15 Connections Limit" already says 15: do not print it twice
                    if ($detail !== '' && ctype_digit($detail) && str_contains($meta[$key]['name'], $detail)) { $detail = ''; }
                }
                $features[] = $meta[$key] + [
                    'included' => (bool) $cell['enabled'],
                    'unlimited' => (bool) $cell['unlimited'],
                    'limit' => $cell['limit'],
                    'detail' => $detail,
                ];
            }

            $isCurrent = $current && $current->planId === $plan->planId;
            $out[] = [
                'planId' => $plan->planId,
                'plan_name' => $plan->plan_name,
                'price' => (float) $plan->price,
                'billing' => str_contains(strtolower((string) $plan->tagmonthYear), 'year') ? 'yearly' : 'monthly',
                'tag' => $plan->tag,
                'description' => $plan->description,
                'button_name' => $plan->button_name,
                'button_color' => $plan->button_color,
                'plan_level' => (int) $plan->plan_level,
                'is_popular' => strtoupper(trim((string) $plan->tag)) === 'MOST POPULAR',
                'is_current' => (bool) $isCurrent,
                'current_end' => $isCurrent ? $current->end_date : null,
                // moving down a level is not offered here (an admin does it): same rule as account-plan/list
                'disabled' => $currentPlan && !$isCurrent && $plan->plan_level < $currentPlan->plan_level,
                'features' => $features,
                'included_count' => count(array_filter($features, fn ($f) => $f['included'])),
            ];
        }

        return ['audience' => $audience, 'plans' => $out];
    }

    /** "Everything in Free" style rows only say a plan inherits; the inheritance itself is computed here. */
    private function isInheritanceRow(object $row): bool
    {
        return (bool) preg_match('/^EVERYTHING_/i', (string) $row->feature_code)
            || (bool) preg_match('/^everything in /i', (string) $row->feature_name);
    }
}
