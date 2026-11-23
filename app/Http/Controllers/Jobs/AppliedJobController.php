<?php

namespace App\Http\Controllers\Jobs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Jobs\AppliedJobs;
use App\Models\Jobs\AppliedResumes;
use Illuminate\Support\Facades\Mail;
use App\Mail\AppliedStatusUpdated;
use App\Http\Controllers\ChatController;
use App\Models\Jobs\JobPosting;
use App\Services\PlanService;

class AppliedJobController extends Controller
{
    protected $planService;
    public function __construct(PlanService $planService)
    {
        $this->planService = $planService;
    }

    // public function saveAppliedJob(Request $request)
    // {
    //     try {
    //         $user = Auth::user();

    //         // ✅ Validate request
    //         $validated = $request->validate([
    //             'job_name'      => 'required|string|max:255',
    //             'email'         => 'required|string|email|max:255',
    //             'country_code'  => 'required|string|max:10',
    //             'phone_number'  => 'required|string|max:20',
    //             'transNo'       => 'required|string|max:50',
    //             'resume_pdf'    => 'required|file|mimes:pdf|max:2048', // PDF only
    //             'answers'       => 'required|array',                   // expect multiple answers
    //             'answers.*.question_id' => 'required|integer',
    //             'answers.*.answer_text' => 'required|string',
    //         ]);

    //         DB::beginTransaction();

    //         // ✅ Save file
    //         $resumePath = null;
    //         if ($request->hasFile('resume_pdf')) {
    //             $file = $request->file('resume_pdf');
    //             $uuid = Str::uuid();
    //             $folderPath = "uploads/{$user->code}/AppliedJobsResume/{$uuid}";
    //             $fileName = time() . '.' . $file->getClientOriginalExtension();
    //             $filePath = $file->storeAs($folderPath, $fileName, 'public');
    //             $resumePath = "/storage/app/public/" . $filePath;
    //         }
    //         $job = AppliedJobs::create([
    //             'transNo'       => $validated['transNo'],
    //             'job_name'      => $validated['job_name'],
    //             'email'         => $validated['email'],
    //             'country_code'  => $validated['country_code'],
    //             'phone_number'  => $validated['phone_number'],
    //             'code'          => $user->code,
    //             'role_code'     => $user->role_code,
    //             'fullname'      => $user->fullname,
    //         ]);
    //         // ✅ Save related Resume record
    //         AppliedResumes::create([
    //             'transNo'       => $validated['transNo'],
    //             'resume_pdf'    => $resumePath,
    //             'job_name'      => $validated['job_name'],
    //             'role_code'     => $user->role_code,
    //             'code'          => $user->code,
    //             'fullname'      => $user->fullname,
    //             'company'       => $user->company,
    //         ]);

    //         // ✅ Update multiple answers
    //         foreach ($validated['answers'] as $answer) {
    //             DB::table('applied_questions')
    //                 ->where('transNo', $validated['transNo'])
    //                 ->where('question_id', $answer['question_id'])
    //                 ->update([
    //                     'answer_text' => $answer['answer_text'],
    //                     'updated_at'  => now(),
    //                 ]);
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Applied Job saved successfully',
    //             'transNo' => $validated['transNo'],
    //         ], 201);
    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'message' => 'Validation failed.',
    //             'success' => false,
    //             'errors'  => $e->errors(),
    //         ], 422);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'message' => 'Something went wrong while saving the job application.',
    //             'success' => false,
    //             'error'   => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function saveAppliedJob(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();

            // drafts and unknown jobs can't be applied to
            $job = \App\Models\Jobs\JobPosting::where('transNo', (string) $request->input('transNo'))->first();
            if (!$job || $job->recordstatus !== 'active') {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'This job is not accepting applications.'], 404);
            }

            // a recruiter can't apply to their own job
            if ((string) $job->code === (string) $user->code) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => "You can't apply to your own job."], 403);
            }

            // the recruiter can switch the CV off; old jobs keep requiring it
            $cvRule = ($job->allow_cv === null || (int) $job->allow_cv === 1) ? 'required' : 'nullable';

            // Settings > Resume / CV: with no file uploaded, the applicant's default resume can be used instead
            $defaultResume = (!$request->hasFile('resume_pdf') && $request->boolean('use_default_resume'))
                ? \App\Http\Controllers\Auth\UserResumeController::defaultFor($user->code)
                : null;
            if ($defaultResume && (strtolower(pathinfo($defaultResume->path, PATHINFO_EXTENSION)) !== 'pdf'
                || !\Illuminate\Support\Facades\Storage::disk('local')->exists($defaultResume->path))) {
                $defaultResume = null;      // not a PDF, or the stored file is gone: fall back to requiring an upload
            }
            if ($defaultResume) { $cvRule = 'nullable'; }

            $validated = $request->validate([
                'job_name'      => 'required|string|max:255',
                'email'         => 'required|email|max:255',
                'country_code'  => 'required|string|max:10',
                'phone_number'  => 'required|string|max:20',
                'transNo'       => 'required|string|max:50',
                'resume_pdf'    => $cvRule . '|file|mimes:pdf|max:5120',
                'answers'       => 'nullable|array',
                'answers.*.question_id' => 'required|integer',
                'answers.*.answer_text' => 'nullable|string',
            ]);

            // ---- the recruiter's application settings, enforced here (the screen only mirrors them) ----
            if ($job->application_deadline && now()->startOfDay()->gt(\Carbon\Carbon::parse($job->application_deadline))) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'The application deadline for this job has passed.'], 409);
            }
            if ($job->application_limit
                && AppliedJobs::where('transNo', $validated['transNo'])->count() >= $job->application_limit) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'This job has reached its application limit.'], 409);
            }

            // ---- answers: required questions answered, and every answer valid for its question type ----
            $questions = \App\Models\Jobs\Question::where('transNo', $validated['transNo'])->get()->keyBy('question_id');
            $given = collect($validated['answers'] ?? [])->mapWithKeys(fn ($a) => [(int) $a['question_id'] => trim((string) ($a['answer_text'] ?? ''))]);
            $problems = [];
            foreach ($questions as $qid => $q) {
                $ans = $given[$qid] ?? '';
                $required = $q->is_required === null ? true : (bool) $q->is_required;

                if ($ans === '') {
                    if ($required) { $problems[] = 'Please answer: ' . $q->question_text; }
                    continue;
                }
                $options = $q->options ? (json_decode($q->options, true) ?: []) : [];
                $bad = match ($q->question_type) {
                    'yes_no' => !in_array(strtolower($ans), ['yes', 'no'], true),
                    'multiple_choice' => !in_array($ans, $options, true),
                    'numeric' => !is_numeric($ans),
                    'short_text' => mb_strlen($ans) > 255,
                    'long_text' => mb_strlen($ans) > 5000,
                    default => false,
                };
                if ($bad) { $problems[] = 'Invalid answer for: ' . $q->question_text; }
            }
            if ($problems) {
                throw \Illuminate\Validation\ValidationException::withMessages(['answers' => $problems]);
            }

            // Prevent duplicate application
            $exists = AppliedJobs::where('transNo', $validated['transNo'])
                ->where('code', $user->code)
                ->exists();

            if ($exists) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'You have already applied for this job.'
                ], 409);
            }

            $hasUnlimited = $this->planService->hasUnlimitedJobApplications($user->code);
            if (!$hasUnlimited) {
                // Example: Free = 10, Basic = 25
                $limit = $this->planService->getApplicationLimit($user->code);
                $totalApplied = AppliedJobs::where('code', $user->code)->count();
                // a limit of 0 means the plan sets no cap (see PlanService::getApplicationLimit)
                if ($limit > 0 && $totalApplied >= $limit) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'You have reached your job application limit. Upgrade your plan to apply for unlimited jobs.',
                        'current' => $totalApplied,
                        'limit'   => $limit
                    ], 409);
                }
            }

            $resumePath = null;
            if ($request->hasFile('resume_pdf')) {
                $file = $request->file('resume_pdf');
                $folder = "uploads/{$user->code}/AppliedJobsResume";
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                // PRIVATE disk: a CV is personal data. It is only ever served to the job's recruiter through
                // CandidateReviewController::resume (authenticated), never by a public URL.
                $resumePath = $file->storeAs($folder, $filename, 'local');
            } elseif ($defaultResume && ((int) ($job->allow_cv ?? 1) === 1)) {
                // a copy, so deleting or replacing the account resume later never changes what the recruiter already received
                $folder = "uploads/{$user->code}/AppliedJobsResume";
                $resumePath = $folder . '/' . Str::uuid() . '.pdf';
                \Illuminate\Support\Facades\Storage::disk('local')->copy($defaultResume->path, $resumePath);
            }

            /*
        |--------------------------------------------------------------------------
        | Save Applied Job
        |--------------------------------------------------------------------------
        */

            $appliedRow = AppliedJobs::create([
                'transNo'       => $validated['transNo'],
                'job_name'      => $validated['job_name'],
                'email'         => $validated['email'],
                'country_code'  => $validated['country_code'],
                'phone_number'  => $validated['phone_number'],
                'code'          => $user->code,
                'role_code'     => $user->role_code,
                'fullname'      => $user->fullname,
            ]);

            /*
        |--------------------------------------------------------------------------
        | Save Resume
        |--------------------------------------------------------------------------
        */

            if ($resumePath) {
                AppliedResumes::create([
                    'transNo'       => $validated['transNo'],
                    'resume_pdf'    => $resumePath, // save relative path only
                    'job_name'      => $validated['job_name'],
                    'role_code'     => $user->role_code,
                    'code'          => $user->code,
                    'fullname'      => $user->fullname,
                    'company'       => $user->company,
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | Save Answers
        |--------------------------------------------------------------------------
        */

            $appliedId = (int) ($appliedRow->applied_id ?? DB::table('applied_jobs')->where('transNo', $validated['transNo'])->where('code', $user->code)->value('applied_id'));
            foreach (($validated['answers'] ?? []) as $answer) {
                // each applicant's own answer (the old column on the question row is kept for older screens)
                DB::table('applied_answers')->updateOrInsert(
                    ['applied_id' => $appliedId, 'question_id' => (int) $answer['question_id']],
                    ['transNo' => $validated['transNo'], 'code' => $user->code, 'answer_text' => $answer['answer_text'] ?? '', 'updated_at' => now(), 'created_at' => now()]
                );

                DB::table('applied_questions')
                    ->where('transNo', $validated['transNo'])
                    ->where('question_id', $answer['question_id'])
                    ->update([
                        'answer_text' => $answer['answer_text'] ?? '',
                        'updated_at' => now()
                    ]);
            }

            DB::table('application_status_history')->insert([
                'applied_id' => $appliedId, 'transNo' => $validated['transNo'], 'from_status' => null, 'to_status' => 'applied_active',
                'reason' => 'Application received', 'source' => 'applicant', 'changed_by' => $user->code, 'changed_by_name' => $user->fullname, 'created_at' => now(),
            ]);

            // ---- automatic screening (only when the recruiter turned it on and gave a passing score) ----
            if ($job->auto_screen && $job->min_pass_score !== null) {
                $objective = $questions->filter(fn ($q) => $q->correct_answer !== null && $q->correct_answer !== '');
                if ($objective->count()) {
                    $right = $objective->filter(function ($q) use ($given) {
                        $a = $given[$q->question_id] ?? '';
                        return match ($q->question_type) {
                            'numeric' => is_numeric($a) && (float) $a == (float) $q->correct_answer,
                            'yes_no' => strtolower($a) === strtolower($q->correct_answer),
                            default => $a === $q->correct_answer,
                        };
                    })->count();

                    $percent = (int) round(($right / $objective->count()) * 100);
                    $auto = $percent >= (int) $job->min_pass_score ? 'review' : 'reject';
                    AppliedJobs::where('transNo', $validated['transNo'])->where('code', $user->code)->update(['applied_status' => $auto]);
                    // the recruiter switched this on and set the pass mark; it is logged so it can be reviewed and reversed
                    DB::table('application_status_history')->insert([
                        'applied_id' => $appliedId, 'transNo' => $validated['transNo'], 'from_status' => 'applied_active', 'to_status' => $auto,
                        'reason' => "Automatic screening: {$percent}% on the questions with a correct answer (pass mark {$job->min_pass_score}%)",
                        'source' => 'auto_screen', 'created_at' => now(),
                    ]);
                }
            }

            DB::commit();

            // "application received" (chat + email, best effort: never fails the application)
            try {
                $fresh = \App\Services\Recruitment\RecruitmentPipeline::load($appliedId);
                if ($fresh) { \App\Services\Recruitment\RecruitmentPipeline::notify($fresh, 'received', null); }
            } catch (\Throwable $e) { \Log::warning('Application received notice failed: ' . $e->getMessage()); }

            return response()->json([
                'success' => true,
                'message' => 'Job application submitted successfully.'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAppliedJob()
    {
        $user = Auth::user();

        $results = DB::table('applied_jobs as aj')
            ->join('jobPosting as jp', 'aj.transNo', '=', 'jp.transNo')
            ->select(
                'jp.transNo',
                'aj.code',
                'aj.role_code',
                'aj.applied_id',
                'aj.fullname',
                'aj.job_name',
                'aj.email',
                'aj.country_code',
                'aj.phone_number',
                'aj.applied_status'
            )
            ->where('jp.code', $user->code)
            ->get();

        if ($results->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No applied jobs found.',
                'data' => []
            ], 404);
        }

        $results->transform(function ($job) {

            $job->resumes = DB::table('applied_resumes')
                ->where('transNo', $job->transNo)
                ->where('code', $job->code)
                ->select('resume_pdf as url')
                ->get();

            return $job;
        });

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    public function getAppliedJobByUsers($transNo)
    {
        $user = Auth::user();

        $results = DB::table('applied_jobs as aj')
            ->leftJoin('jobPosting as jp', 'aj.transNo', '=', 'jp.transNo')
            ->select(
                'aj.transNo',
                'aj.code',
                'aj.role_code',
                'aj.applied_id',
                'aj.fullname',
                'aj.job_name',
                'aj.email',
                'aj.country_code',
                'aj.phone_number',
                'aj.applied_status'
            )
            ->where('aj.code', $user->code)
            ->where('aj.transNo', $transNo)
            ->get();

        if ($results->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No applied jobs found for this user.',
                'data' => []
            ]);
        }

        $results->transform(function ($job) {
            $job->resumes = DB::table('applied_resumes')
                ->where('transNo', $job->transNo)
                ->where('code', $job->code)
                ->select('resume_pdf as url')
                ->get();

            return $job;
        });

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }



    public function getAppliedJobByTransNo($transNo)
    {
        $user = Auth::user();

        $results = DB::table('applied_jobs as aj')
            ->leftJoin('jobPosting as jp', 'aj.transNo', '=', 'jp.transNo')
            ->select(
                'jp.transNo',
                'aj.code',
                'aj.role_code',
                'aj.applied_id',
                'aj.fullname',
                'aj.job_name',
                'aj.email',
                'aj.country_code',
                'aj.phone_number',
                'aj.applied_status'
            )
            ->where('jp.code', $user->code)
            ->where('jp.transNo', $transNo)
            ->get();

        if ($results->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No applied jobs found for this user.',
                'data' => []
            ]);
        }

        // Combined into a single transform pass (was two separate loops before)
        $results->transform(function ($job) {
            // no file paths: the CV is opened through the authenticated review endpoint
            $job->resumes = DB::table('applied_resumes')
                ->where('transNo', $job->transNo)
                ->where('code', $job->code)
                ->select('resume_id as id')
                ->get();

            // THIS applicant's answers (they used to be read from the question row, which held only the last applicant's)
            $job->answers = DB::table('applied_questions as q')
                ->leftJoin('applied_answers as a', function ($j) use ($job) {
                    $j->on('a.question_id', '=', 'q.question_id')->where('a.applied_id', '=', $job->applied_id);
                })
                ->where('q.transNo', $job->transNo)
                ->select('q.question_id', 'q.answer_type', 'q.question_text', 'a.answer_text')
                ->get();

            return $job;
        });

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    public function getInterviewAppliedJobs()
    {
        $user = Auth::user();

        $results = DB::table('applied_jobs as aj')
            ->join('jobPosting as jp', 'aj.transNo', '=', 'jp.transNo')
            ->select(
                'jp.transNo',
                'aj.code',
                'aj.role_code',
                'aj.applied_id',
                'aj.fullname',
                'aj.job_name',
                'aj.email',
                'aj.country_code',
                'aj.phone_number',
                'aj.applied_status'
            )
            ->where('jp.code', $user->code)              // ✅ filter by company code
            ->where('aj.applied_status', 'interview')    // ✅ interview only
            ->get();

        if ($results->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No interview candidates found.',
                'data' => []
            ]);
        }

        // Attach resumes
        $results->transform(function ($job) {
            $job->resumes = DB::table('applied_resumes')
                ->where('transNo', $job->transNo)
                ->where('code', $job->code)
                ->select('resume_pdf as url')
                ->get();

            return $job;
        });

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    public function getAllAppliedJobsByCode()
    {
        $user = Auth::user();

        $results = DB::table('applied_jobs as aj')
            ->join('jobPosting as jp', 'aj.transNo', '=', 'jp.transNo')
            ->select(
                'jp.transNo',
                'aj.code',
                'aj.role_code',
                'aj.applied_id',
                'aj.fullname',
                'aj.job_name',
                'aj.email',
                'aj.country_code',
                'aj.phone_number',
                'aj.applied_status'
            )
            ->where('jp.code', $user->code) // filter by company code
            ->get();

        if ($results->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No applied jobs found for this company.',
                'data' => []
            ]);
        }

        // Attach resumes
        $results->transform(function ($job) {
            $job->resumes = DB::table('applied_resumes')
                ->where('transNo', $job->transNo)
                ->where('code', $job->code)
                ->select('resume_pdf as url')
                ->get();

            return $job;
        });

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    // public function getAppliedJob()
    // {
    //     $user = Auth::user();

    //     $results = DB::table('applied_jobs as aj')
    //         ->leftJoin('jobPosting as jp', 'aj.transNo', '=', 'jp.transNo')
    //         ->select(
    //             'jp.transNo',
    //             'aj.code',
    //             'aj.role_code',
    //             'aj.applied_id',
    //             'aj.fullname',
    //             'aj.job_name',
    //             'aj.email',
    //             'aj.country_code',
    //             'aj.phone_number',
    //             'aj.applied_status'
    //         )
    //         ->where('jp.code', $user->code) // or use email if your table doesn't have user_id
    //         ->get();

    //     if ($results->isEmpty()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'No applied jobs found for this user.',
    //             'data' => []
    //         ]);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'data' => $results
    //     ]);
    // }

    /**
     * Change an application's status. Goes through the recruitment pipeline: only the job's owner (or a master
     * administrator) may do it, only valid moves are allowed, a rejection needs a reason, and every change is
     * recorded. The applicant is notified by chat and email as before.
     */
    public function updateAppliedStatus(Request $request, $applied_id)
    {
        $request->validate([
            'status' => 'required|string|max:50',
            'reason' => 'nullable|string|max:1000',
        ]);

        $app = \App\Services\Recruitment\RecruitmentPipeline::load((int) $applied_id);
        if (!$app) {
            return response()->json(['success' => false, 'message' => 'Application not found.'], 404);
        }
        if (!\App\Services\Recruitment\RecruitmentPipeline::mayManage(Auth::user(), $app)) {
            return response()->json(['success' => false, 'message' => 'You can only change the status of applications to your own job postings.'], 403);
        }

        [$ok, $why] = \App\Services\Recruitment\RecruitmentPipeline::changeStatus(
            $app, $request->input('status'), $request->input('reason'), Auth::user(), 'recruiter', true
        );
        if (!$ok) {
            return response()->json(['success' => false, 'message' => $why], 422);
        }

        return response()->json(['success' => true, 'message' => 'Application status updated and the applicant was notified.']);
    }

    public function getAppliedStatus($transNo)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // ❗ Check if transNo is empty
        if (!$transNo) {
            return response()->json([
                'success' => false,
                'message' => 'transNo is required',
            ], 400);
        }

        // ❗ Check if transNo exists in the entire applied_jobs table
        $transNoExists = DB::table('applied_jobs')
            ->where('transNo', $transNo)
            ->exists();

        if (!$transNoExists) {
            // ❌ transNo DOES NOT exist in the database
            return response()->json([
                'success' => false,
                'message' => 'transNo does not exist in the database',
            ], 404);
        }

        // ✔ Check if this user applied to this specific transNo
        $job = DB::table('applied_jobs')
            ->where('transNo', $transNo)
            ->where('code', $user->code)
            ->select('applied_status')
            ->first();

        if ($job) {
            // ✔ Return actual applied_status
            return response()->json([
                'success' => true,
                'applied_status' => $job->applied_status,
            ]);
        }

        // ✔ If user has no applied jobs at all → return default
        $anyJob = DB::table('applied_jobs')
            ->where('code', $user->code)
            ->first();

        if (!$anyJob) {
            return response()->json([
                'success' => true,
                'applied_status' => 'default',
            ]);
        }

        // ✔ User applied to other jobs but not this transNo → return default
        return response()->json([
            'success' => true,
            'applied_status' => 'default',
        ]);
    }

    // public function getAppliedStatus($transNo)
    // {
    //     $user = Auth::user(); // get logged-in user

    //     if (!$user) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Unauthorized',
    //         ], 401);
    //     }

    //     // Fetch applied job by transNo and user code
    //     $job = DB::table('applied_jobs')
    //         ->where('transNo', $transNo)
    //         ->where('code', $user->code)
    //         ->select('applied_status')
    //         ->first();

    //     if (!$job) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'default',
    //         ], 200);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'applied_status' => $job->applied_status,
    //     ]);
    // }

    public function updateAppliedStatusx(Request $request, $transNo)
    {
        $user = Auth::user();
        $request->validate([
            'status' => 'required|string|max:50',
        ]);

        $status = $request->input('status');
        $updated = DB::table('applied_jobs')
            ->where('transNo', $transNo)
            ->update(['applied_status' => $status]);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update applied status. Record may not exist or unauthorized.'
            ]);
        }

        // Fetch updated job details
        $job = DB::table('applied_jobs')->where('transNo', $transNo)->first();

        // --- Send email notification ---
        if ($job && $job->email && \App\Helpers\NotificationPrefs::allowsEmailForCode($job->code ?? null, 'email_application_updates')) {
            Mail::to($job->email)->send(new AppliedStatusUpdated($job, $status));
        }

        // --- Get user ID from users table using code ---
        $receiver = DB::table('users')
            ->where('code', $job->code)
            ->select('id')
            ->first();

        if ($receiver) {
            $chatController = new ChatController();
            $messageText =
                "Subject: Application Status Update " .
                "Your application for '{$job->job_name}' has been updated to '{$status}'.";

            $requestMessage = new Request([
                'receiver_id' => $receiver->id,
                'message'     => $messageText
            ]);
            //notify app
            $chatController->sendMessage($requestMessage);
        }
        return response()->json([
            'success' => true,
            'message' => "Applied status updated to '{$status}', email sent, and message notification sent."
        ]);
    }
}
