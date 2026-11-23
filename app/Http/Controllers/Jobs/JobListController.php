<?php

namespace App\Http\Controllers\Jobs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class JobListController extends Controller
{
    /**
     * Turn a stored job image path (e.g. "/storage/uploads/703/...jpg") into a full URL
     * on the host that served this request, so the browser can load it directly.
     */
    private function imageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        // our own uploads: always rebuild for the current host (local or production)
        if (preg_match('#uploads/.+$#', $path, $m)) {
            return url('storage/' . $m[0]);
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $path = str_replace('/storage/app/public/', '/storage/', $path);

        return url(ltrim($path, '/'));
    }

    private function withImageUrls($jobs)
    {
        return $jobs->map(function ($job) {
            if (isset($job->job_image)) {
                $job->job_image = $this->imageUrl($job->job_image);
            }
            if (property_exists($job, 'recruiter_photo')) {
                $job->recruiter_photo = $this->imageUrl($job->recruiter_photo);
            }
            return $job;
        });
    }

    public function getActiveJobs()
    {
        $jobs = DB::table('jobposting')
            ->select('jobposting.*')
            ->selectSub(
                DB::table('userprofiles')
                    ->select('photo_pic')
                    ->whereColumn('userprofiles.code', 'jobposting.code')
                    ->limit(1),
                'recruiter_photo'
            )
            ->where('recordstatus', 'active')
            ->orderBy('created_at', 'asc')
            ->get();

        $jobs = $this->withImageUrls($jobs);

        return response()->json([
            'success' => true,
            'data' => $jobs
        ]);
    }

    public function getAppliedJobCount()
    {
        $user = Auth::user();

        $count = DB::table('applied_jobs as aj')
            ->leftJoin('jobposting as jp', 'aj.transNo', '=', 'jp.transNo')
            ->where('jp.code', $user->code)
            ->count();

        return response()->json([
            'success' => true,
            'total' => $count
        ]);
    }
    public function getPendingReviews()
    {
        $user = Auth::user();

        $count = DB::table('applied_jobs as aj')
            ->join('jobposting as jp', 'aj.transNo', '=', 'jp.transNo')
            ->where('jp.code', $user->code)
            ->where('aj.applied_status', 'review') // ✅ FIXED
            ->count();

        return response()->json([
            'success' => true,
            'total' => $count
        ]);
    }
    public function getHired()
    {
        $user = Auth::user();

        $count = DB::table('applied_jobs as aj')
            ->join('jobposting as jp', 'aj.transNo', '=', 'jp.transNo')
            ->where('jp.code', $user->code)
            ->where('aj.applied_status', 'interview') // ✅ FIXED
            ->count();

        return response()->json([
            'success' => true,
            'total' => $count
        ]);
    }
    public function getActiveJobsByCode($code)
    {
        $jobs = DB::table('jobposting')
            ->where('recordstatus', 'active')
            ->where('code', $code)
            ->orderByDesc('created_at')
            ->get();

        $jobs = $this->withImageUrls($jobs);

        return response()->json([
            'success' => true,
            'data' => $jobs
        ]);
    }

    public function getActiveJobsByPublic(Request $request)
    {
        // public and unauthenticated: never trust the page size (per_page=1000000 would dump the whole table)
        $perPage = max(1, min(50, (int) $request->query('per_page', 20)));
        $jobs = DB::table('jobposting')
            ->select([
                'job_id',
                'transNo',
                'job_name',
                'job_position',
                'job_image',
                'location',
                'work_type',
                'currency',
                'min_salary',
                'max_salary',
                'company',
                'created_at'
            ])
            ->where('recordstatus', 'active')
            // a job whose application deadline has passed cannot be applied to (AppliedJobController refuses it),
            // so it should not be advertised; no deadline means open-ended
            ->where(fn ($q) => $q->whereNull('application_deadline')->orWhereDate('application_deadline', '>=', now()->toDateString()))
            // job_id breaks ties so a page boundary never repeats or skips a job
            ->orderBy('created_at', 'desc')
            ->orderBy('job_id', 'desc')
            ->paginate($perPage);

        $jobs->getCollection()->transform(function ($job) {
            $job->job_image = $this->imageUrl($job->job_image);
            return $job;
        });

        return response()->json([
            'success' => true,
            'data' => $jobs
        ]);
    }


    public function getJobVacanciesCountByCode()
    {
        $user = Auth::user();

        $count = DB::table('jobposting')
            ->where('recordstatus', 'active')
            ->where('code', $user->code)
            ->count();

        return response()->json([
            'success' => true,
            'total' => $count
        ]);
    }

    /**
     * GET getClientDashboard : the recruiter dashboard.
     * Everything is about the SIGNED-IN account's own job listings and the applications made to them
     * (jobposting.code = my code), so it can never show another recruiter's data.
     */
    public function getClientDashboard()
    {
        $me = Auth::user();

        $mine = fn () => DB::table('applied_jobs as aj')
            ->join('jobposting as jp', 'aj.transNo', '=', 'jp.transNo')
            ->where('jp.code', $me->code);

        // pipeline: the statuses the recruiter screens really use, in order, zeros included
        $stages = [
            'applied_active' => 'Applied',
            'review'         => 'In review',
            'interview'      => 'Interview',
            'approved'       => 'Approved',
            'reject'         => 'Rejected',
        ];
        $counts = $mine()->select('aj.applied_status', DB::raw('COUNT(*) AS c'))->groupBy('aj.applied_status')->pluck('c', 'applied_status');
        $pipeline = [];
        foreach ($stages as $key => $label) {
            $pipeline[] = ['key' => $key, 'label' => $label, 'count' => (int) ($counts[$key] ?? 0)];
        }

        // applications per month, last 6 months (zero-filled)
        $start = now()->startOfMonth()->subMonths(5);
        $perMonth = $mine()->where('aj.created_at', '>=', $start)
            ->select(DB::raw("DATE_FORMAT(aj.created_at, '%Y-%m') AS m"), DB::raw('COUNT(*) AS c'))->groupBy('m')->pluck('c', 'm');
        $trend = [];
        for ($i = 0; $i < 6; $i++) {
            $d = $start->copy()->addMonths($i);
            $trend[] = ['label' => $d->format('M Y'), 'count' => (int) ($perMonth[$d->format('Y-m')] ?? 0)];
        }

        // my job listings with how many applied
        $applicants = DB::table('applied_jobs')->select('transNo', DB::raw('COUNT(*) AS c'))->groupBy('transNo')->pluck('c', 'transNo');
        $vacancies = DB::table('jobposting')->where('code', $me->code)->orderByDesc('created_at')->limit(8)
            ->get(['transNo', 'job_name', 'job_position', 'location', 'work_type', 'recordstatus', 'created_at'])
            ->map(fn ($j) => [
                'transNo' => $j->transNo, 'job_name' => $j->job_name, 'job_position' => $j->job_position,
                'location' => $j->location, 'work_type' => $j->work_type, 'status' => $j->recordstatus,
                'created_at' => $j->created_at, 'applicants' => (int) ($applicants[$j->transNo] ?? 0),
            ])->all();

        $recent = $mine()->leftJoin('userprofiles as p', 'p.code', '=', 'aj.code')
            ->orderByDesc('aj.created_at')->limit(6)
            ->get(['aj.applied_id', 'aj.fullname', 'aj.job_name', 'aj.applied_status as status', 'aj.created_at', 'aj.code', 'p.photo_pic'])->all();

        return response()->json([
            'success' => true,
            'user' => ['fullname' => $me->fullname, 'company' => $me->company],
            'totals' => [
                'vacancies' => DB::table('jobposting')->where('code', $me->code)->where('recordstatus', 'active')->count(),
                'vacancies_all' => DB::table('jobposting')->where('code', $me->code)->count(),
                'applicants' => $mine()->count(),
                'in_review' => (int) ($counts['review'] ?? 0),
                'interviews' => (int) ($counts['interview'] ?? 0),
                'approved' => (int) ($counts['approved'] ?? 0),
            ],
            'pipeline' => $pipeline,
            'trend' => $trend,
            'vacancies' => $vacancies,
            'recent_applicants' => $recent,
            'generated_at' => now()->toDateTimeString(),
        ]);
    }
}
