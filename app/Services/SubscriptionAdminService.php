<?php

namespace App\Services;

use App\Models\AccountPlan\SubscriptionFeature;
use App\Models\AccountPlan\UserPlan;
use App\Models\AccountPlan\UserPlanDetails;
use App\Models\AccountPlan\UserSubscriptionDetails;
use App\Models\AccountPlan\UserSubscriptions;
use App\Models\Jobs\AppliedJobs;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Business logic behind the master-admin Subscription Management pages.
 * Reads and writes the tables the app already enforces (userplandetails, usersubscription) so every change
 * takes effect immediately; PlanService::bust() runs through the model events.
 */
class SubscriptionAdminService
{
    public const SUSPENDED_TAG = 'SUSPENDED';

    // ------------------------------------------------------------ assignments

    /** Decode a stored userplandetails row into the matrix cell the UI edits. */
    public static function decode(?UserPlanDetails $row): array
    {
        if (!$row || $row->recordStatus !== 'active') {
            return ['enabled' => false, 'limit' => null, 'unlimited' => false];
        }
        $v = strtoupper(trim((string) $row->feature_value));
        if ($v === '' || $v === 'NO' || $v === '0') {
            return ['enabled' => false, 'limit' => null, 'unlimited' => false];
        }
        if ($v === 'UNLIMITED') {
            return ['enabled' => true, 'limit' => null, 'unlimited' => true];
        }
        if (is_numeric($v)) {
            return ['enabled' => true, 'limit' => (int) $v, 'unlimited' => false];
        }
        return ['enabled' => true, 'limit' => null, 'unlimited' => false]; // "YES" and anything else truthy
    }

    /** Encode a matrix cell into the value string PlanService reads. */
    public static function encode(SubscriptionFeature $feature, array $cell): string
    {
        if (empty($cell['enabled'])) {
            return 'NO';
        }
        if ($feature->value_type === 'limit') {
            if (!empty($cell['unlimited'])) {
                return 'UNLIMITED';
            }
            return isset($cell['limit']) && $cell['limit'] !== null ? (string) (int) $cell['limit'] : 'YES';
        }
        return 'YES';
    }

    /** @return array<int, array> one entry per catalog feature */
    public function assignmentMatrix(string $planId): array
    {
        $rows = UserPlanDetails::where('planId', $planId)
            ->whereNotNull('feature_code')->where('feature_code', '<>', '')
            ->orderBy('id')->get()->keyBy(fn ($r) => strtoupper($r->feature_code));

        return SubscriptionFeature::orderBy('category')->orderBy('name')->get()
            ->map(fn (SubscriptionFeature $f) => ['feature_id' => $f->id, 'feature_code' => $f->feature_code]
                + self::decode($rows->get($f->feature_code)))
            ->values()->all();
    }

    /** Persist the whole matrix for one plan inside a transaction. */
    public function saveAssignments(UserPlan $plan, array $items): void
    {
        $features = SubscriptionFeature::whereIn('id', collect($items)->pluck('feature_id'))->get()->keyBy('id');

        DB::transaction(function () use ($plan, $items, $features) {
            foreach ($items as $cell) {
                /** @var SubscriptionFeature|null $feature */
                $feature = $features->get($cell['feature_id']);
                if (!$feature) {
                    continue;
                }
                $enabled = !empty($cell['enabled']) && $feature->is_active;
                $value = self::encode($feature, ['enabled' => $enabled] + $cell);

                $row = UserPlanDetails::where('planId', $plan->planId)->where('feature_code', $feature->feature_code)->first();
                if (!$row && !$enabled) {
                    continue; // nothing stored, nothing to switch off
                }
                if (!$row) {
                    $row = new UserPlanDetails(['planId' => $plan->planId, 'feature_code' => $feature->feature_code]);
                    $row->fid = $this->nextFeatureId();
                    $row->button_name = (string) $plan->button_name; // NOT NULL without default in userplandetails
                }
                $row->plan_name = $plan->plan_name;
                $row->feature_name = $feature->name;
                $row->feature_value = $value;
                $row->recordStatus = $enabled ? 'active' : 'inactive';
                $row->save();
            }
        });
    }

    private function nextFeatureId(): string
    {
        $n = (int) UserPlanDetails::max('id') + 1;
        do {
            $fid = 'FID' . str_pad((string) $n++, 6, '0', STR_PAD_LEFT);
        } while (UserPlanDetails::where('fid', $fid)->exists());
        return $fid;
    }

    // ------------------------------------------------------------ subscribers

    /** active / expired / suspended / pending / none, derived from the latest subscription row. */
    public static function statusOf(?object $sub): string
    {
        if (!$sub) {
            return 'none';
        }
        if ($sub->is_active === 'cancelled' && Str::startsWith((string) $sub->remarks, self::SUSPENDED_TAG)) {
            return 'suspended';
        }
        if ($sub->is_active === 'active') {
            return $sub->end_date && Carbon::parse($sub->end_date)->isPast() ? 'expired' : 'active';
        }
        return $sub->is_active === 'pending' ? 'pending' : 'expired';
    }

    /** Latest subscription row id per user code (the "current" subscription whatever its state). */
    private function latestSubscriptionIds()
    {
        return DB::table('usersubscription')->select('code', DB::raw('MAX(id) as sid'))->groupBy('code');
    }

    public function subscriberQuery(array $f)
    {
        $q = DB::table('users as u')
            ->leftJoinSub($this->latestSubscriptionIds(), 'l', 'l.code', '=', 'u.code')
            ->leftJoin('usersubscription as s', 's.id', '=', 'l.sid')
            ->whereIn('u.role_code', ['DEF-USERS', 'DEF-CLIENT'])
            ->select('u.code', 'u.fullname', 'u.email', 'u.role_code', 's.id as sub_id', 's.transNo', 's.planId', 's.plan_name',
                's.start_date', 's.end_date', 's.is_active', 's.remarks', 's.payment_method');

        if (!empty($f['search'])) {
            $term = '%' . $f['search'] . '%';
            $q->where(fn ($w) => $w->where('u.fullname', 'like', $term)->orWhere('u.email', 'like', $term));
        }
        if (!empty($f['plan_id'])) {
            $q->where('s.planId', $f['plan_id']);
        }
        if (!empty($f['status'])) {
            $now = now()->toDateTimeString();
            match ($f['status']) {
                'active'    => $q->where('s.is_active', 'active')->where('s.end_date', '>=', $now),
                'expired'   => $q->where(fn ($w) => $w->where('s.is_active', 'expired')
                    ->orWhere(fn ($x) => $x->where('s.is_active', 'active')->where('s.end_date', '<', $now))),
                'suspended' => $q->where('s.is_active', 'cancelled')->where('s.remarks', 'like', self::SUSPENDED_TAG . '%'),
                'pending'   => $q->where('s.is_active', 'pending'),
                'none'      => $q->whereNull('s.id'),
                default     => null,
            };
        }
        return $q->orderBy('u.fullname');
    }

    /** Usage for the features we can actually measure today. */
    public function usageFor(string $code, ?string $planId): array
    {
        if (!$planId) {
            return [];
        }
        $usage = [];
        $limit = PlanService::effectiveFeatures($code)->firstWhere('feature_code', 'APPLICATION_LIMIT');
        if ($limit) {
            $v = strtoupper((string) $limit->feature_value);
            $usage[] = [
                'feature_code' => 'APPLICATION_LIMIT', 'name' => 'Job applications',
                'used' => AppliedJobs::where('code', $code)->count(),
                'limit' => is_numeric($v) ? (int) $v : null,
                'unlimited' => $v === 'UNLIMITED',
            ];
        }
        return $usage;
    }

    public function history(string $code): array
    {
        return UserSubscriptions::where('code', $code)->orderByDesc('id')->get()->map(fn ($s) => [
            'transNo' => $s->transNo, 'plan_name' => $s->plan_name, 'amount' => (float) $s->amount,
            'payment_method' => $s->payment_method, 'start_date' => $s->start_date, 'end_date' => $s->end_date,
            'status' => self::statusOf($s), 'remarks' => $s->remarks,
        ])->all();
    }

    /** Move +days: from the later of (current end, now). An expired row is revived. */
    public function extend(string $code, int $days): UserSubscriptions
    {
        $sub = UserSubscriptions::where('code', $code)->orderByDesc('id')->firstOrFail();
        if (self::statusOf($sub) === 'suspended') {
            abort(409, 'Reactivate this subscription before extending it.');
        }
        $base = Carbon::parse($sub->end_date ?? now());
        if ($base->isPast()) {
            $base = now();
        }
        $sub->end_date = $base->copy()->addDays($days);
        $sub->is_active = 'active';
        $sub->remarks = trim(($sub->remarks ? $sub->remarks . ' | ' : '') . "Extended {$days}d by " . Auth::user()->code);
        $sub->save();
        return $sub;
    }

    /** Suspending cancels the active row (no feature access) but remembers why, so it can be reactivated. */
    public function suspend(string $code, ?string $reason): UserSubscriptions
    {
        $sub = UserSubscriptions::where('code', $code)->where('is_active', 'active')->orderByDesc('id')->first();
        if (!$sub) {
            abort(409, 'This user has no active subscription to suspend.');
        }
        $sub->is_active = 'cancelled';
        $sub->cancelled_at = now();
        $sub->remarks = self::SUSPENDED_TAG . ' by ' . Auth::user()->code . ($reason ? ': ' . $reason : '');
        $sub->save();
        UserSubscriptionDetails::where('code', $code)->where('transNo', $sub->transNo)->update(['is_active' => 'expired']);
        return $sub;
    }

    public function reactivate(string $code): UserSubscriptions
    {
        $sub = UserSubscriptions::where('code', $code)->orderByDesc('id')->first();
        if (!$sub || self::statusOf($sub) !== 'suspended') {
            abort(409, 'This subscription is not suspended.');
        }
        $sub->is_active = 'active';
        $sub->cancelled_at = null;
        $sub->remarks = trim($sub->remarks . ' | Reactivated by ' . Auth::user()->code);
        $sub->save();
        UserSubscriptionDetails::where('code', $code)->where('transNo', $sub->transNo)->update(['is_active' => 'active']);
        return $sub;
    }

    // ---------------------------------------------------------------- reports

    public function report(int $expiringDays = 30): array
    {
        $now = now();
        $latest = $this->subscriberQuery([])->get();
        $statusOf = fn ($r) => self::statusOf($r->sub_id ? $r : null);

        $withStatus = $latest->map(fn ($r) => [$r, $statusOf($r)]);
        $statusCounts = $withStatus->groupBy(1)->map->count();

        $plans = UserPlan::orderBy('sort_number')->get();
        $distribution = $plans->map(fn ($p) => [
            'plan_id' => $p->planId, 'name' => $p->plan_name,
            'count' => $withStatus->filter(fn ($x) => $x[0]->planId === $p->planId && $x[1] === 'active')->count(),
        ])->values();

        $paidIds = $plans->filter(fn ($p) => (float) $p->price > 0)->pluck('planId');
        $activeRows = $withStatus->filter(fn ($x) => $x[1] === 'active');
        $paid = $activeRows->filter(fn ($x) => $paidIds->contains($x[0]->planId))->count();
        $free = $activeRows->count() - $paid;

        $revenue = $plans->map(function ($p) use ($activeRows) {
            $n = $activeRows->filter(fn ($x) => $x[0]->planId === $p->planId)->count();
            $monthly = str_contains(strtolower((string) $p->tagmonthYear), 'year') ? (float) $p->price / 12 : (float) $p->price;
            return ['plan_id' => $p->planId, 'name' => $p->plan_name, 'subscribers' => $n, 'monthly' => round($n * $monthly, 2)];
        })->values();

        $growth = collect(range(5, 0))->map(function ($i) use ($now) {
            $m = $now->copy()->startOfMonth()->subMonths($i);
            return [
                'label' => $m->format('M'),
                'value' => DB::table('usersubscription')->whereBetween('start_date', [$m, $m->copy()->endOfMonth()])->distinct()->count('code'),
            ];
        })->values();

        $expiring = $activeRows->filter(fn ($x) => Carbon::parse($x[0]->end_date)->lte($now->copy()->addDays($expiringDays)))
            ->map(fn ($x) => [
                'code' => $x[0]->code, 'fullname' => $x[0]->fullname, 'email' => $x[0]->email, 'plan_name' => $x[0]->plan_name,
                'end_date' => $x[0]->end_date, 'days_left' => (int) ceil((Carbon::parse($x[0]->end_date)->timestamp - $now->timestamp) / 86400),
            ])->sortBy('days_left')->values();

        return [
            'totals' => [
                'subscribers' => $activeRows->count(), 'paid' => $paid, 'free' => $free,
                'monthly_revenue' => round($revenue->sum('monthly'), 2), 'all_users' => $latest->count(),
            ],
            'status' => collect(['active', 'expired', 'suspended', 'pending', 'none'])
                ->map(fn ($s) => ['status' => $s, 'count' => (int) ($statusCounts[$s] ?? 0)])->values(),
            'distribution' => $distribution, 'revenue' => $revenue, 'growth' => $growth, 'expiring' => $expiring,
        ];
    }
}
