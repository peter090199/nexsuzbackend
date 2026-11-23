<?php

namespace App\Http\Controllers\Jobs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Jobs\SavedJob;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SavedJobController extends Controller
{
    // Toggle save/unsave
    public function saveJobs(Request $request)
    {
        $request->validate([
            'job_id' => 'required|integer',
        ]);
        $user = Auth::user();
        $existing = SavedJob::where('code', $user->code)
            ->where('job_id', $request->input('job_id'))
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['saved' => false]);
        }

        SavedJob::create([
            'code'   => $user->code,
            'job_id' => $request->input('job_id'),
        ]);

        return response()->json(['saved' => true]);
    }

    public function getSaveJobs()
    {
        $user = Auth::user();
        $jobs = DB::table('saved_jobs')
            ->leftJoin('jobposting', 'saved_jobs.job_id', '=', 'jobposting.job_id')
            ->where('saved_jobs.code',  $user->code)
            ->where('saved_jobs.recordstatus', 'Active')
            ->orderByDesc('saved_jobs.created_at')
            ->select(
                'saved_jobs.id as saved_job_id',
                'saved_jobs.code',
                'saved_jobs.job_id',
                'saved_jobs.recordstatus',
                'saved_jobs.created_at',
                'jobposting.transNo',
                'jobposting.job_name',
                'jobposting.company',
                'jobposting.work_type',
                'jobposting.location',
                'jobposting.job_image'
            )
            ->get();

        // image URL for the current host (works on local and production)
        $jobs->each(function ($j) {
            if (!$j->job_image) {
                return;
            }
            if (preg_match('#uploads/.+$#', $j->job_image, $m)) {
                $j->job_image = url('storage/' . $m[0]);
            } elseif (!preg_match('#^https?://#i', $j->job_image)) {
                $j->job_image = url(ltrim(str_replace('/storage/app/public/', '/storage/', $j->job_image), '/'));
            }
        });

        return response()->json([
            'success' => true,
            'data' => $jobs
        ]);
    }
}
