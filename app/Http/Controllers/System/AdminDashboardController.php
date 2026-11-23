<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * GET admin/dashboard-stats?from=YYYY-MM-DD&to=YYYY-MM-DD   (or ?all=1)
 *
 * One endpoint for both admin dashboards. Everything is counted from the real tables; nothing is estimated.
 * The role decides what is returned, on the server:
 *
 *   DEF-MASTERADMIN  platform-wide: every account type, plus admin accounts and subscription data
 *   DEF-ADMIN        users and clients only (DEF-USERS / DEF-CLIENT), and no admin/master or subscription data
 *   anyone else      403
 */
class AdminDashboardController extends Controller
{
    private const ROLES_ALLOWED = ['DEF-ADMIN', 'DEF-MASTERADMIN'];
    private const ADMIN_SCOPE = ['DEF-USERS', 'DEF-CLIENT'];

    public function stats(Request $request)
    {
        $me = Auth::user();
        if (!in_array($me->role_code, self::ROLES_ALLOWED, true)) {
            return response()->json(['success' => false, 'message' => 'You are not allowed to view the dashboard.'], 403);
        }
        $isMaster = $me->role_code === 'DEF-MASTERADMIN';

        $request->validate([
            'from' => 'nullable|date_format:Y-m-d',
            'to'   => 'nullable|date_format:Y-m-d',
            'all'  => 'nullable|boolean',
        ]);

        // ---------------------------------------------------------------- date range
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : now()->endOfDay();

        if ($request->boolean('all')) {
            $earliest = collect([
                DB::table('users')->min('created_at'),
                DB::table('jobposting')->min('created_at'),
                DB::table('applied_jobs')->min('created_at'),
                DB::table('userprofiles')->min('created_at'),
            ])->filter()->min();
            $from = $earliest ? Carbon::parse($earliest)->startOfDay() : $to->copy()->subYear()->startOfDay();
        } else {
            $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : $to->copy()->subMonths(12)->startOfDay();
        }
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $days = $from->diffInDays($to) + 1;
        $unit = $days <= 45 ? 'day' : ($days <= 200 ? 'week' : 'month');

        // ---------------------------------------------------------------- scope: what this role may count
        $users = fn () => $isMaster
            ? DB::table('users')
            : DB::table('users')->whereIn('role_code', self::ADMIN_SCOPE);

        // ---------------------------------------------------------------- totals (all time) + in the selected range
        $totals = [
            'registered_users' => DB::table('users')->where('role_code', 'DEF-USERS')->count(),
            'clients'          => DB::table('users')->where('role_code', 'DEF-CLIENT')->count(),
            'active_users'     => $users()->where('status', 'A')->count(),
            'inactive_users'   => $users()->where('status', 'I')->count(),
            'jobs'             => DB::table('jobposting')->count(),
            'jobs_active'      => DB::table('jobposting')->where('recordstatus', 'active')->count(),
            'applications'     => DB::table('applied_jobs')->count(),
            'cvs'              => DB::table('userprofiles')->count(),
            'new_registrations' => $users()->whereBetween('created_at', [$from, $to])->count(),
        ];
        $inRange = [
            'jobs'         => DB::table('jobposting')->whereBetween('created_at', [$from, $to])->count(),
            'applications' => DB::table('applied_jobs')->whereBetween('created_at', [$from, $to])->count(),
            'cvs'          => DB::table('userprofiles')->whereBetween('created_at', [$from, $to])->count(),
        ];

        // ---------------------------------------------------------------- charts
        $series = [
            'registrations' => $this->series('users', 'created_at', $from, $to, $unit, fn ($q) => $isMaster ? $q : $q->whereIn('role_code', self::ADMIN_SCOPE)),
            'jobs'          => $this->series('jobposting', 'created_at', $from, $to, $unit),
            'applications'  => $this->series('applied_jobs', 'created_at', $from, $to, $unit),
        ];

        $roles = $users()->select('role_code', DB::raw("SUM(status = 'A') AS active"), DB::raw("SUM(status = 'I') AS inactive"), DB::raw('COUNT(*) AS total'))
            ->groupBy('role_code')->orderByDesc('total')->get()
            ->map(fn ($r) => ['role_code' => $r->role_code, 'total' => (int) $r->total, 'active' => (int) $r->active, 'inactive' => (int) $r->inactive])->all();

        $applicationsByStatus = DB::table('applied_jobs')->select('applied_status', DB::raw('COUNT(*) AS c'))
            ->groupBy('applied_status')->orderByDesc('c')->get()
            ->map(fn ($r) => ['status' => $r->applied_status ?: 'unknown', 'count' => (int) $r->c])->all();

        // ---------------------------------------------------------------- recent registrations
        $recentRegistrations = $users()
            ->leftJoin('userprofiles as p', 'p.code', '=', 'users.code')
            ->orderByDesc('users.created_at')->limit(8)
            ->get(['users.code', 'users.fullname', 'users.role_code', 'users.status', 'users.company', 'users.created_at', 'p.photo_pic'])
            ->all();

        // ---------------------------------------------------------------- recent platform activity (newest first, from the real tables)
        $activity = collect();

        foreach ($users()->orderByDesc('created_at')->limit(8)->get(['code', 'fullname', 'role_code', 'created_at']) as $r) {
            $activity->push(['type' => 'registration', 'title' => "{$r->fullname} registered", 'subtitle' => $this->roleLabel($r->role_code), 'at' => $r->created_at, 'code' => $r->code, 'role_code' => $r->role_code]);
        }
        foreach (DB::table('jobposting')->orderByDesc('created_at')->limit(8)->get(['job_name', 'company', 'fullname', 'code', 'created_at']) as $r) {
            $activity->push(['type' => 'job', 'title' => ($r->fullname ?: 'A client') . " posted {$r->job_name}", 'subtitle' => $r->company ?: 'Job listing', 'at' => $r->created_at, 'code' => $r->code, 'role_code' => 'DEF-CLIENT']);
        }
        foreach (DB::table('applied_jobs')->orderByDesc('created_at')->limit(8)->get(['job_name', 'fullname', 'code', 'created_at']) as $r) {
            $activity->push(['type' => 'application', 'title' => ($r->fullname ?: 'A user') . " applied for {$r->job_name}", 'subtitle' => 'Job application', 'at' => $r->created_at, 'code' => $r->code, 'role_code' => 'DEF-USERS']);
        }
        if ($isMaster) {
            foreach (DB::table('usersubscription as s')->leftJoin('users as u', 'u.code', '=', 's.code')->orderByDesc('s.created_at')->limit(8)
                ->get(['u.fullname', 's.plan_name', 's.code', 's.created_at']) as $r) {
                $activity->push(['type' => 'subscription', 'title' => ($r->fullname ?: 'A user') . " subscribed to {$r->plan_name}", 'subtitle' => 'Subscription', 'at' => $r->created_at, 'code' => $r->code, 'role_code' => null]);
            }
        }
        $recentActivity = $activity->filter(fn ($a) => $a['at'])->sortByDesc('at')->take(12)->values()->all();

        $payload = [
            'success' => true,
            'role' => $me->role_code,
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'days' => $days, 'bucket' => $unit],
            'totals' => $totals,
            'in_range' => $inRange,
            'series' => $series,
            'roles' => $roles,
            'applications_by_status' => $applicationsByStatus,
            'recent_registrations' => $recentRegistrations,
            'recent_activity' => $recentActivity,
            'generated_at' => now()->toDateTimeString(),
        ];

        // ---------------------------------------------------------------- master admin only
        if ($isMaster) {
            $payload['master'] = [
                'admin_accounts' => [
                    'admins' => DB::table('users')->where('role_code', 'DEF-ADMIN')->count(),
                    'admins_active' => DB::table('users')->where('role_code', 'DEF-ADMIN')->where('status', 'A')->count(),
                    'master_admins' => DB::table('users')->where('role_code', 'DEF-MASTERADMIN')->count(),
                ],
                'subscriptions' => [
                    'active' => DB::table('usersubscription')->where('is_active', 'active')->count(),
                    'new_in_range' => DB::table('usersubscription')->whereBetween('created_at', [$from, $to])->count(),
                    'by_plan' => DB::table('usersubscription')->where('is_active', 'active')
                        ->select('plan_name', DB::raw('COUNT(*) AS c'))->groupBy('plan_name')->orderByDesc('c')->get()
                        ->map(fn ($r) => ['plan_name' => $r->plan_name, 'count' => (int) $r->c])->all(),
                ],
            ];
        }

        return response()->json($payload);
    }

    // ------------------------------------------------------------------ helpers

    private function roleLabel(?string $role): string
    {
        return [
            'DEF-USERS' => 'User', 'DEF-CLIENT' => 'Recruiter / Client', 'DEF-ADMIN' => 'Admin',
            'DEF-MASTERADMIN' => 'Master Admin', 'DEF-SUPERADMIN' => 'Super Admin',
        ][$role] ?? 'Account';
    }

    /** every bucket of the range, zero-filled, so a chart has no gaps */
    private function buckets(Carbon $from, Carbon $to, string $unit): array
    {
        $out = [];
        $cur = $from->copy();
        if ($unit === 'week') { $cur = $cur->startOfWeek(); }
        if ($unit === 'month') { $cur = $cur->startOfMonth(); }

        while ($cur->lte($to)) {
            $key = $cur->format('Y-m-d');
            $out[$key] = [
                'date' => $key,
                'label' => $unit === 'month' ? $cur->format('M Y') : ($unit === 'week' ? 'Week of ' . $cur->format('M j') : $cur->format('M j')),
                'count' => 0,
            ];
            $cur = $unit === 'day' ? $cur->copy()->addDay() : ($unit === 'week' ? $cur->copy()->addWeek() : $cur->copy()->addMonth());
        }

        return $out;
    }

    private function bucketKey(Carbon $d, string $unit): string
    {
        return $unit === 'day' ? $d->format('Y-m-d')
            : ($unit === 'week' ? $d->copy()->startOfWeek()->format('Y-m-d') : $d->copy()->startOfMonth()->format('Y-m-d'));
    }

    private function series(string $table, string $column, Carbon $from, Carbon $to, string $unit, ?\Closure $extra = null): array
    {
        $q = DB::table($table)->whereBetween($column, [$from, $to]);
        if ($extra) { $q = $extra($q); }

        $rows = $q->select(DB::raw("DATE({$column}) AS d"), DB::raw('COUNT(*) AS c'))->groupBy('d')->get();

        $buckets = $this->buckets($from, $to, $unit);
        foreach ($rows as $r) {
            $key = $this->bucketKey(Carbon::parse($r->d), $unit);
            if (isset($buckets[$key])) { $buckets[$key]['count'] += (int) $r->c; }
        }

        return array_values($buckets);
    }
}
