<?php

namespace App\Http\Controllers\Jobs;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * My Job Applications: a job seeker's own applications, read only (routes use 'cvwriter' = DEF-USERS only).
 *
 * Reuses applied_jobs, application_status_history and interviews. What recruiters keep internal is never returned:
 * rejection reasons, screening scores, AI advice, notes, interview feedback and who changed a status.
 */
class MyApplicationsController extends Controller
{
    /** the pipeline's status values, in the order an application moves through them */
    public const STATUSES = ['applied_active', 'review', 'interview', 'approved', 'reject'];

    private function mine()
    {
        return DB::table('applied_jobs as aj')
            ->leftJoin('jobPosting as jp', 'aj.transNo', '=', 'jp.transNo')
            ->where('aj.code', (string) Auth::user()->code);
    }

    private static function status(?string $s): string
    {
        return in_array($s, self::STATUSES, true) ? $s : 'applied_active';
    }

    private static function reference(int $id): string
    {
        return 'APP-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    /** GET my-applications */
    public function index()
    {
        $rows = $this->mine()->select('aj.applied_id', 'aj.transNo', 'aj.job_name', 'aj.applied_status', 'aj.created_at', 'aj.updated_at',
            'jp.company', 'jp.location', 'jp.job_name as posting_name', 'jp.job_id')->orderByDesc('aj.applied_id')->get();

        $lastChange = $rows->isEmpty() ? collect() : DB::table('application_status_history')
            ->whereIn('applied_id', $rows->pluck('applied_id'))->groupBy('applied_id')
            ->selectRaw('applied_id, max(created_at) as at')->pluck('at', 'applied_id');

        $data = $rows->map(fn ($r) => [
            'id' => (int) $r->applied_id,
            'reference' => self::reference((int) $r->applied_id),
            'job_title' => $r->posting_name ?: $r->job_name,
            'company' => $r->company,
            'location' => $r->location,
            'trans_no' => $r->transNo,
            'job_available' => $r->job_id !== null,
            'status' => self::status($r->applied_status),
            'applied_at' => $r->created_at,
            'updated_at' => max(array_filter([$r->updated_at, $lastChange[$r->applied_id] ?? null, $r->created_at])),
        ])->values();

        $counts = array_fill_keys(self::STATUSES, 0);
        foreach ($data as $a) { $counts[$a['status']]++; }

        // the latest status changes, for the dashboard: what changed and when, never why or by whom
        $titles = $data->pluck('job_title', 'id');
        $updates = $rows->isEmpty() ? collect() : DB::table('application_status_history')
            ->whereIn('applied_id', $rows->pluck('applied_id'))->orderByDesc('created_at')->orderByDesc('id')->limit(8)
            ->get(['applied_id', 'to_status', 'created_at'])
            ->map(fn ($h) => ['id' => (int) $h->applied_id, 'job_title' => $titles[$h->applied_id] ?? null,
                'status' => self::status($h->to_status), 'at' => $h->created_at])->values();

        return response()->json(['success' => true, 'data' => $data, 'counts' => $counts, 'total' => $data->count(), 'updates' => $updates]);
    }

    /** GET my-applications/{id}: one of the person's own applications, with its timeline */
    public function show(int $id)
    {
        $r = $this->mine()->where('aj.applied_id', $id)
            ->select('aj.applied_id', 'aj.transNo', 'aj.job_name', 'aj.applied_status', 'aj.created_at', 'aj.updated_at',
                'jp.company', 'jp.location', 'jp.job_name as posting_name', 'jp.job_id', 'jp.employment_type', 'jp.work_type')->first();
        // someone else's application looks exactly like one that does not exist
        if (!$r) { return response()->json(['success' => false, 'message' => 'Application not found.'], 404); }

        $timeline = DB::table('application_status_history')->where('applied_id', $id)->orderBy('id')
            ->get(['to_status', 'created_at'])
            ->map(fn ($h) => ['status' => self::status($h->to_status), 'at' => $h->created_at])->values();
        if ($timeline->isEmpty() || $timeline[0]['status'] !== 'applied_active') {
            // older applications have no history row for the moment they were sent
            $timeline->prepend(['status' => 'applied_active', 'at' => $r->created_at]);
        }

        $interviews = DB::table('interviews')->where('applied_id', $id)->orderBy('scheduled_at')
            ->get(['scheduled_at', 'duration_min', 'type', 'location', 'meeting_link', 'interviewer_name', 'status']);

        return response()->json(['success' => true, 'data' => [
            'id' => (int) $r->applied_id,
            'reference' => self::reference((int) $r->applied_id),
            'job_title' => $r->posting_name ?: $r->job_name,
            'company' => $r->company,
            'location' => $r->location,
            'employment_type' => $r->employment_type,
            'work_type' => $r->work_type,
            'trans_no' => $r->transNo,
            'job_available' => $r->job_id !== null,
            'status' => self::status($r->applied_status),
            'applied_at' => $r->created_at,
            'updated_at' => $timeline->max('at') ?: $r->updated_at,
            'timeline' => $timeline,
            'interviews' => $interviews,
        ]]);
    }
}
