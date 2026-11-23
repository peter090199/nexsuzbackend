<?php

namespace App\Console\Commands;

use App\Models\AccountPlan\SubscriptionFeature;
use App\Models\AccountPlan\UserPlanDetails;
use App\Services\PlanService;
use App\Services\SubscriptionAdminService;
use App\Support\FeatureAccess;
use Illuminate\Console\Command;

/**
 * Plan features are enforced strictly: a feature that is off on a plan is not available on it. Recruiters already use
 * networking, messaging and search, but the recruiter starter plan only had "job posting" and "job applicants" switched
 * on. This switches the missing ones on for it, once, so enforcing the plans does not take away what recruiters have.
 *
 *   php artisan plans:sync-recruiter-defaults --dry-run     show what would change
 *   php artisan plans:sync-recruiter-defaults               do it
 *
 * Safe to run again. It only ADDS: a feature the master admin has deliberately switched off for that plan (its row is
 * there but inactive) is left off, and nothing is ever removed. Every change goes through the same service the Plan
 * Feature Assignment page uses.
 */
class SyncRecruiterPlanDefaults extends Command
{
    protected $signature = 'plans:sync-recruiter-defaults {--dry-run : only list what would be switched on}';
    protected $description = 'Switch on networking, messaging and search for the recruiter starter plan (they already use them)';

    /** the features recruiters use today that the recruiter starter plan lacks */
    private const FEATURES = [FeatureAccess::NETWORK_FEED, FeatureAccess::MESSAGING_ACCESS, FeatureAccess::SEARCH_ACCESS];

    public function handle(SubscriptionAdminService $service): int
    {
        $plan = PlanService::starterPlan('DEF-CLIENT');
        if (!$plan) {
            $this->warn('There is no active recruiter starter plan, so there is nothing to update.');

            return self::SUCCESS;
        }
        $this->info("Recruiter starter plan: {$plan->plan_name} ({$plan->planId})");

        $items = [];
        foreach (self::FEATURES as $code) {
            $feature = SubscriptionFeature::where('feature_code', $code)->first();
            if (!$feature || !$feature->is_active) {
                $this->warn("  {$code}: not in the feature catalog (or switched off there), skipped.");
                continue;
            }
            $row = UserPlanDetails::where('planId', $plan->planId)->where('feature_code', $code)->first();
            if ($row && $row->recordStatus === 'active') {
                $this->line("  {$code}: already on.");
            } elseif ($row) {
                $this->line("  {$code}: switched off on purpose, left as it is.");
            } else {
                $this->line("  {$code}: will be switched on.");
                $items[] = ['feature_id' => $feature->id, 'enabled' => true];
            }
        }

        if (!$items) {
            $this->info('Nothing to change.');

            return self::SUCCESS;
        }
        if ($this->option('dry-run')) {
            $this->comment(count($items) . ' feature(s) would be switched on. (dry run: nothing was saved)');

            return self::SUCCESS;
        }

        $service->saveAssignments($plan, $items);
        PlanService::bust();                     // everyone's features are recalculated at once
        $this->info(count($items) . ' feature(s) switched on.');

        return self::SUCCESS;
    }
}
