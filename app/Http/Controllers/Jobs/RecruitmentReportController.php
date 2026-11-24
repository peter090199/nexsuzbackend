<?php

namespace App\Http\Controllers\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Jobs\JobPosting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Reports / Analytics for recruiters.
 * Everything is computed from the SIGNED-IN account's own vacancies (jobposting.code = my code) and the applications made
 * to them, so it can never show another recruiter's numbers. Nothing here is personal data: only counts and averages.
 */
class RecruitmentReportController extends Controller
{
    private const STAGES = ['applied_active' => 'Applied', 'review' => 'Under review', 'interview' => 'Interview', 'approved' => 'Approved', 'reject' => 'Rejected'];

    /** GET recruitment/reports?from=YYYY-MM-DD&to=YYYY-MM-DD&transNo= */
    public function index(Request $request)
    {
        $me = Auth::user();
        // recruiters, and the admin roles (they have no vacancies of their own, so their report is simply empty)
        if (!in_array($me->role_code, ['DEF-CLIENT', 'DEF-ADMIN', 'DEF-MASTERADMIN'], true)) {
            return response()->json(['success' => false, 'message' => 'Reports are for recruiters and administrators.'], 403);
        }

        $v = $request->validate([
            'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from', 'transNo' => 'nullable|string|max:50',
        ]);

        $to = isset($v['to']) ? Carbon::parse($v['to'])->endOfDay() : now()->endOfDay();
        $from = isset($v['from']) ? Carbon::parse($v['from'])->startOfDay() : $to->copy()->subDays(89)->startOfDay();

        $jobs = JobPosting::where('code', $me->code)->orderByDesc('created_at')->get(['transNo', 'job_name', 'recordstatus', 'created_at']);
        $mine = $jobs->pluck('transNo')->all();

        $transNo = $v['transNo'] ?? null;
        if ($transNo && !in_array($transNo, $mine, true)) {
            return response()->json(['success' => false, 'message' => 'You can only report on your own job postings.'], 403);
        }

        $apps = DB::table('applied_jobs')->whereIn('transNo', $transNo ? [$transNo] : $mine)
            ->whereBetween('created_at', [$from, $to])->get(['applied_id', 'transNo', 'applied_status', 'created_at']);
        $ids = $apps->pluck('applied_id')->all();
        $total = $apps->count();

        $history = $ids ? DB::table('application_status_history')->whereIn('applied_id', $ids)->orderBy('id')->get() : collect();
        $evals = $ids ? DB::table('candidate_evaluations')->whereIn('applied_id', $ids)->orderBy('id')->get()->keyBy('applied_id') : collect(); // latest wins
        $interviews = $ids ? DB::table('interviews')->whereIn('applied_id', $ids)->get() : collect();

        $status = fn ($a) => $a->applied_status ?: 'applied_active';

        // ---- pipeline now + funnel (how many ever reached each stage)
        $now = array_fill_keys(array_keys(self::STAGES), 0);
        foreach ($apps as $a) { if (isset($now[$status($a)])) { $now[$status($a)]++; } }

        $everReached = fn (string $stage) => $apps->filter(fn ($a) => $status($a) === $stage || $history->where('applied_id', $a->applied_id)->contains('to_status', $stage))->count();
        $funnel = [
            ['key' => 'applied_active', 'label' => 'Applied', 'count' => $total],
            ['key' => 'review', 'label' => 'Under review', 'count' => $everReached('review') + $this->countReachedThrough($apps, $history, $status, ['interview', 'approved'], 'review')],
            ['key' => 'interview', 'label' => 'Interview', 'count' => $everReached('interview')],
            ['key' => 'approved', 'label' => 'Approved', 'count' => $everReached('approved')],
        ];
        // a stage can never be larger than the one before it
        for ($i = 1; $i < count($funnel); $i++) { $funnel[$i]['count'] = min($funnel[$i]['count'], $funnel[$i - 1]['count']); }

        // ---- time to first decision (days from applying to the first move a recruiter made)
        $days = [];
        foreach ($apps as $a) {
            $first = $history->where('applied_id', $a->applied_id)->first(fn ($h) => $h->source === 'recruiter' && $h->to_status !== 'applied_active');
            if ($first) { $days[] = max(0, Carbon::parse($a->created_at)->diffInSeconds(Carbon::parse($first->created_at)) / 86400); }
        }
        sort($days);
        $median = $days ? $days[(int) floor((count($days) - 1) / 2)] : null;

        // ---- AI evaluation
        $scoreOf = fn ($e) => (int) ($e->final_score ?? $e->overall_score);
        $dist = ['strong' => 0, 'qualified' => 0, 'review' => 0, 'low' => 0];
        foreach ($evals as $e) { if (isset($dist[$e->recommendation])) { $dist[$e->recommendation]++; } }

        // ---- interviews
        $done = $interviews->where('status', 'completed');
        $scores = $done->whereNotNull('score')->pluck('score');

        // ---- trend (zero-filled): day, week or month depending on the range
        $span = $from->diffInDays($to) + 1;
        $gran = $span <= 31 ? 'day' : ($span <= 120 ? 'week' : 'month');
        $bucket = function (Carbon $d) use ($gran) {
            return $gran === 'day' ? $d->format('Y-m-d') : ($gran === 'week' ? $d->copy()->startOfWeek()->format('Y-m-d') : $d->format('Y-m'));
        };
        $labelOf = fn (string $k) => $gran === 'month' ? Carbon::createFromFormat('Y-m', $k)->format('M Y') : Carbon::parse($k)->format($gran === 'day' ? 'M j' : '\W\k M j');
        $keys = [];
        for ($d = $from->copy(); $d <= $to; $d = $gran === 'day' ? $d->copy()->addDay() : ($gran === 'week' ? $d->copy()->addWeek() : $d->copy()->addMonth())) { $keys[$bucket($d)] = true; }
        $keys[$bucket($to)] = true;
        $applied = array_fill_keys(array_keys($keys), 0);
        $approvedAt = array_fill_keys(array_keys($keys), 0);
        foreach ($apps as $a) { $k = $bucket(Carbon::parse($a->created_at)); if (isset($applied[$k])) { $applied[$k]++; } }
        foreach ($history->where('to_status', 'approved') as $h) { $k = $bucket(Carbon::parse($h->created_at)); if (isset($approvedAt[$k])) { $approvedAt[$k]++; } }
        ksort($applied); ksort($approvedAt);
        $trend = [
            'granularity' => $gran,
            'applications' => array_map(fn ($k) => ['label' => $labelOf($k), 'value' => $applied[$k]], array_keys($applied)),
            'approved' => array_map(fn ($k) => ['label' => $labelOf($k), 'value' => $approvedAt[$k]], array_keys($approvedAt)),
        ];

        // ---- per vacancy
        $byVacancy = $jobs->filter(fn ($j) => !$transNo || $j->transNo === $transNo)->map(function ($j) use ($apps, $status, $evals, $interviews, $scoreOf) {
            $rows = $apps->where('transNo', $j->transNo);
            $n = $rows->count();
            $ids = $rows->pluck('applied_id');
            $jobEvals = $ids->map(fn ($i) => $evals[$i] ?? null)->filter();
            $c = fn (string $s) => $rows->filter(fn ($a) => $status($a) === $s)->count();
            return [
                'transNo' => $j->transNo, 'job_name' => $j->job_name, 'status' => $j->recordstatus,
                'applicants' => $n, 'review' => $c('review'), 'interview' => $c('interview'), 'approved' => $c('approved'), 'rejected' => $c('reject'),
                'avg_ai_score' => $jobEvals->count() ? (int) round($jobEvals->avg(fn ($e) => $scoreOf($e))) : null,
                'interviews' => $interviews->whereIn('applied_id', $ids->all())->count(),
                'approval_rate' => $n ? round($c('approved') / $n * 100) : null,
            ];
        })->sortByDesc('applicants')->values();

        $pct = fn ($n) => $total ? round($n / $total * 100) : null;

        return response()->json([
            'success' => true,
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'granularity' => $gran],
            'jobs' => $jobs->map(fn ($j) => ['transNo' => $j->transNo, 'job_name' => $j->job_name, 'status' => $j->recordstatus])->values(),
            'kpis' => [
                'total' => $total, 'vacancies' => $byVacancy->count(),
                'in_review' => $now['review'], 'interviewing' => $now['interview'], 'approved' => $now['approved'], 'rejected' => $now['reject'],
                'interview_rate' => $pct($funnel[2]['count']), 'approval_rate' => $pct($now['approved']), 'rejection_rate' => $pct($now['reject']),
                'avg_days_to_decision' => $days ? round(array_sum($days) / count($days), 1) : null, 'median_days_to_decision' => $median !== null ? round($median, 1) : null,
                'awaiting_first_review' => $now['applied_active'],
            ],
            'pipeline' => collect(self::STAGES)->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'count' => $now[$key]])->values(),
            'funnel' => $funnel,
            'trend' => $trend,
            'ai' => [
                'evaluated' => $evals->count(), 'evaluated_pct' => $pct($evals->count()),
                'avg_score' => $evals->count() ? (int) round($evals->avg(fn ($e) => $scoreOf($e))) : null,
                'distribution' => $dist, 'overridden' => $evals->where('status', 'reviewed')->count(),
            ],
            'interviews' => [
                'total' => $interviews->count(), 'upcoming' => $interviews->where('status', 'scheduled')->filter(fn ($i) => Carbon::parse($i->scheduled_at)->isFuture())->count(),
                'completed' => $done->count(), 'cancelled' => $interviews->where('status', 'cancelled')->count(),
                'avg_score' => $scores->count() ? (int) round($scores->avg()) : null,
                'pass' => $done->where('result', 'pass')->count(), 'hold' => $done->where('result', 'hold')->count(), 'fail' => $done->where('result', 'fail')->count(),
            ],
            'by_vacancy' => $byVacancy,
        ]);
    }

    /** applications whose current status is one of $later and that have no history row for $stage (they skipped past it) */
    private function countReachedThrough($apps, $history, callable $status, array $later, string $stage): int
    {
        return $apps->filter(fn ($a) => in_array($status($a), $later, true) && !$history->where('applied_id', $a->applied_id)->contains('to_status', $stage))->count();
    }
}
