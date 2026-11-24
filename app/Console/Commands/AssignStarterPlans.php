<?php

namespace App\Console\Commands;

use App\Services\PlanService;
use App\Models\AccountPlan\UserSubscriptions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Gives the free STARTER plan to existing job seeker / recruiter accounts that have no subscription at all.
 *
 *   php artisan plans:assign-starter --dry-run                 list who would get it
 *   php artisan plans:assign-starter                           do it (all recruiters and job seekers)
 *   php artisan plans:assign-starter --role=DEF-CLIENT         recruiters only
 *
 * Safe to run again: accounts with ANY subscription row (active, expired, suspended, paid) are skipped, so paid
 * subscribers, suspensions and history are never touched. Each account goes through PlanService::ensureStarterPlan,
 * the same path registration uses.
 */
class AssignStarterPlans extends Command
{
    protected $signature = 'plans:assign-starter {--dry-run : only list the accounts} {--role= : DEF-CLIENT or DEF-USERS (default both)}';
    protected $description = 'Give the free starter plan to existing accounts that have no subscription yet';

    public function handle(): int
    {
        $role = $this->option('role');
        if ($role && !in_array($role, PlanService::PLAN_ROLES, true)) {
            $this->error('--role must be DEF-CLIENT or DEF-USERS.');
            return self::INVALID;
        }

        $given = 0;
        $query = DB::table('users')->whereIn('role_code', $role ? [$role] : PlanService::PLAN_ROLES)
            ->whereNotIn('code', UserSubscriptions::query()->select('code'))->orderBy('code');

        foreach ($query->get(['code', 'role_code']) as $u) {
            if (!PlanService::starterPlan($u->role_code)) {
                $this->warn("No starter plan published for {$u->role_code}; skipped {$u->code}.");
                continue;
            }
            if ($this->option('dry-run')) {
                $this->line("would assign: {$u->code} ({$u->role_code})");
                $given++;
                continue;
            }
            if (PlanService::ensureStarterPlan((string) $u->code, $u->role_code)) { $given++; }
        }

        $this->info($this->option('dry-run') ? "$given account(s) would get the starter plan. (dry run)" : "$given account(s) given the starter plan.");
        return self::SUCCESS;
    }
}
