<?php

namespace App\Http\Controllers\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Jobs\JobPosting;
use App\Services\Recruitment\EvaluationEngine;
use App\Services\Recruitment\RecruitmentPipeline as Pipeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Recruiter's candidate review: pipeline, CV, AI evaluation, screening answers, interviews, notes and history.
 *
 * Every method first checks that the signed-in user owns the job (or is a master administrator); the Angular
 * screens only mirror these checks. Applicant CV files are streamed through here and never exposed by a public URL.
 */
class CandidateReviewController extends Controller
{
    // ======================================================================= helpers

    private function fail(string $message, int $status = 422)
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    /** [application, null] or [null, error response] */
    private function app($id): array
    {
        $app = Pipeline::load((int) $id);
        if (!$app) { return [null, $this->fail('Application not found.', 404)]; }
        if (!Pipeline::mayManage(Auth::user(), $app)) {
            return [null, $this->fail('You can only review candidates of your own job postings.', 403)];
        }

        return [$app, null];
    }

    private function job(string $transNo): array
    {
        $job = JobPosting::where('transNo', $transNo)->first();
        if (!$job) { return [null, $this->fail('Job not found.', 404)]; }
        if (!Pipeline::mayManageJob(Auth::user(), $job)) {
            return [null, $this->fail('You can only review candidates of your own job postings.', 403)];
        }

        return [$job, null];
    }

    private function dec(?string $json): array { return $json ? (json_decode($json, true) ?: []) : []; }

    // ======================================================================= list

    /** GET recruitment/jobs/{transNo}/candidates */
    public function candidates($transNo)
    {
        [$job, $err] = $this->job($transNo);
        if ($err) { return $err; }

        $rows = DB::table('applied_jobs')->where('transNo', $transNo)->orderByDesc('created_at')->get();

        return $this->listResponse($rows, ['transNo' => $job->transNo, 'job_name' => $job->job_name, 'recordstatus' => $job->recordstatus], $transNo);
    }

    /** GET recruitment/candidates : every candidate of every job the signed-in recruiter owns */
    public function allCandidates()
    {
        $transNos = JobPosting::where('code', Auth::user()->code)->pluck('transNo');
        $rows = DB::table('applied_jobs')->whereIn('transNo', $transNos)->orderByDesc('created_at')->get();

        return $this->listResponse($rows, ['transNo' => '', 'job_name' => 'All vacancies', 'recordstatus' => 'active'], null);
    }

    /** the candidate list + pipeline counts, shared by the one-job and the all-jobs pages */
    private function listResponse($rows, array $job, ?string $transNo)
    {
        $ids = $rows->pluck('applied_id');

        $evals = DB::table('candidate_evaluations')->whereIn('applied_id', $ids)->orderBy('id')->get()->keyBy('applied_id'); // latest wins
        $next = DB::table('interviews')->whereIn('applied_id', $ids)->where('status', 'scheduled')->orderBy('scheduled_at')->get()->groupBy('applied_id');
        $notes = DB::table('candidate_notes')->whereIn('applied_id', $ids)->select('applied_id', DB::raw('COUNT(*) c'))->groupBy('applied_id')->pluck('c', 'applied_id');

        $data = $rows->map(function ($r) use ($evals, $next, $notes) {
            $e = $evals[$r->applied_id] ?? null;
            return [
                'applied_id' => (int) $r->applied_id, 'transNo' => $r->transNo, 'code' => $r->code, 'fullname' => $r->fullname,
                'email' => $r->email, 'country_code' => $r->country_code, 'phone_number' => $r->phone_number,
                'job_name' => $r->job_name, 'applied_status' => $r->applied_status ?: 'applied_active',
                'applied_at' => $r->created_at, 'updated_at' => $r->updated_at,
                'ai_score' => $e ? (int) ($e->final_score ?? $e->overall_score) : null,
                'ai_recommendation' => $e->recommendation ?? null,
                'ai_reviewed' => $e ? $e->status === 'reviewed' : false,
                'next_interview' => isset($next[$r->applied_id]) ? $next[$r->applied_id]->first()->scheduled_at : null,
                'notes_count' => (int) ($notes[$r->applied_id] ?? 0),
            ];
        })->values();

        $counts = array_fill_keys(Pipeline::statuses(), 0);
        foreach ($data as $d) { if (isset($counts[$d['applied_status']])) { $counts[$d['applied_status']]++; } }

        $s = EvaluationEngine::settings($transNo);

        return response()->json([
            'success' => true,
            'job' => $job,
            'counts' => $counts, 'total' => $data->count(),
            'statuses' => config('recruitment.statuses'), 'transitions' => config('recruitment.transitions'),
            'passing_score' => $s['passing_score'], 'thresholds' => $s['thresholds'],
            'ai_provider' => $this->providerName(),
            'data' => $data,
        ]);
    }

    private function providerName(): string
    {
        return (config('recruitment.ai.provider') !== 'local' && config('recruitment.ai.anthropic.key')) ? 'anthropic' : 'local';
    }

    // ======================================================================= one application

    /** GET recruitment/applications/{id} */
    public function show($id)
    {
        [$app, $err] = $this->app($id);
        if ($err) { return $err; }

        $code = $app->code;
        $profile = DB::table('userprofiles')->where('code', $code)->first(['current_location', 'photo_pic']);
        $res = DB::table('resources')->where('code', $code)->first(['profession']);   // never age / date_birth

        $resumes = DB::table('applied_resumes')->where('transNo', $app->transNo)->where('code', $code)->orderBy('resume_id')->get()
            ->map(fn ($r) => ['id' => (int) $r->resume_id, 'name' => basename($r->resume_pdf), 'available' => (bool) $this->resumePath($r->resume_pdf)]);

        $questions = DB::table('applied_questions')->where('transNo', $app->transNo)->orderBy('sort_order')->orderBy('question_id')->get();
        $given = DB::table('applied_answers')->where('applied_id', $app->applied_id)->pluck('answer_text', 'question_id');
        $answers = $questions->map(fn ($q) => [
            'question_id' => (int) $q->question_id, 'question' => $q->question_text, 'type' => $q->question_type ?: 'short_text',
            'required' => $q->is_required === null ? true : (bool) $q->is_required, 'options' => $q->options ? (json_decode($q->options, true) ?: []) : [],
            'expected_answer' => $q->correct_answer, 'answer' => (string) ($given[$q->question_id] ?? ''),
        ])->values();

        $history = DB::table('application_status_history')->where('applied_id', $app->applied_id)->orderByDesc('id')->get()->map(function ($h) {
            $h->ai_snapshot = $this->dec($h->ai_snapshot) ?: null;
            return $h;
        });

        $latest = DB::table('candidate_evaluations')->where('applied_id', $app->applied_id)->orderByDesc('id')->first();

        return response()->json([
            'success' => true,
            'application' => [
                'applied_id' => (int) $app->applied_id, 'transNo' => $app->transNo, 'job_name' => $app->job_name, 'code' => $app->code,
                'fullname' => $app->fullname, 'email' => $app->email, 'phone' => trim('+' . $app->country_code . ' ' . $app->phone_number),
                'status' => $app->applied_status ?: 'applied_active', 'status_label' => Pipeline::label($app->applied_status ?: 'applied_active'),
                'applied_at' => $app->created_at, 'photo' => $profile->photo_pic ?? null,
            ],
            'cv' => [
                'personal' => ['name' => $app->fullname, 'email' => $app->email, 'phone' => trim('+' . $app->country_code . ' ' . $app->phone_number),
                    'location' => $profile->current_location ?? null, 'profession' => $res->profession ?? null],
                'education' => DB::table('usereducations')->where('code', $code)->get(['highest_education', 'school_name', 'start_month', 'start_year', 'end_month', 'end_year']),
                'experience' => DB::table('useremploymentrecords')->where('code', $code)->get(['company_name', 'position', 'job_description', 'date_completed']),
                'skills' => DB::table('userskills')->where('code', $code)->pluck('skills'),
                'certifications' => DB::table('usercertificates')->where('code', $code)->get(['certificate_title', 'certificate_provider', 'date_completed']),
                'trainings' => DB::table('usertrainings')->where('code', $code)->get(['training_title', 'training_provider', 'date_completed']),
            ],
            'resumes' => $resumes,
            'answers' => $answers,
            'history' => $history,
            'notes' => DB::table('candidate_notes')->where('applied_id', $app->applied_id)->orderByDesc('id')->get(),
            'interviews' => DB::table('interviews')->where('applied_id', $app->applied_id)->orderByDesc('scheduled_at')->get(),
            'evaluation' => $latest ? EvaluationEngine::format($latest) : null,
            'transitions' => config('recruitment.transitions')[$app->applied_status ?: 'applied_active'] ?? [],
            'ai_provider' => $this->providerName(),
        ]);
    }

    // ======================================================================= CV file (never a public URL)

    /** the CV on disk: private storage first, then the older public location. Null when the file is gone. */
    private function resumePath(?string $stored): ?string
    {
        if (!$stored || str_contains($stored, '..')) { return null; }
        $rel = preg_match('#uploads/.+$#', $stored, $m) ? $m[0] : null;
        if (!$rel) { return null; }
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($rel)) { return Storage::disk($disk)->path($rel); }
        }

        return null;
    }

    /** GET recruitment/applications/{id}/resume?resume=ID&download=1 : streamed to the owner of the job only */
    public function resume(Request $request, $id)
    {
        [$app, $err] = $this->app($id);
        if ($err) { return $err; }

        $q = DB::table('applied_resumes')->where('transNo', $app->transNo)->where('code', $app->code);
        $row = $request->filled('resume') ? (clone $q)->where('resume_id', (int) $request->input('resume'))->first() : (clone $q)->orderByDesc('resume_id')->first();
        if (!$row) { return $this->fail('This applicant did not upload a CV.', 404); }

        $path = $this->resumePath($row->resume_pdf);
        if (!$path) { return $this->fail('The CV file could not be found. It may have been removed.', 404); }
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'pdf') {
            return $this->fail('This file type can\'t be previewed. Download it instead.', 415);
        }

        $name = preg_replace('/[^A-Za-z0-9._ -]/', '', ($app->fullname ?: 'candidate') . ' CV') . '.pdf';

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline') . '; filename="' . $name . '"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // ======================================================================= status

    /** POST recruitment/applications/{id}/status  {status, reason?, notify?, message?} */
    public function changeStatus(Request $request, $id)
    {
        [$app, $err] = $this->app($id);
        if ($err) { return $err; }

        $v = $request->validate([
            'status' => 'required|string|in:' . implode(',', Pipeline::statuses()),
            'reason' => 'nullable|string|max:1000',
            'notify' => 'nullable|boolean',
            'message' => 'nullable|string|max:3000',
        ]);

        [$ok, $why] = Pipeline::changeStatus($app, $v['status'], $v['reason'] ?? null, Auth::user(), 'recruiter',
            (bool) ($v['notify'] ?? false), $v['message'] ?? null, $this->latestInterviewExtra($app->applied_id));
        if (!$ok) { return $this->fail($why); }

        return response()->json(['success' => true, 'message' => 'Moved to ' . Pipeline::label($v['status']) . '.', 'status' => $v['status']]);
    }

    private function latestInterviewExtra(int $appliedId): array
    {
        $i = DB::table('interviews')->where('applied_id', $appliedId)->where('status', 'scheduled')->orderByDesc('id')->first();
        if (!$i) { return []; }

        return ['when' => date('D, M j, Y \a\t g:i A', strtotime($i->scheduled_at)), 'type' => $i->type, 'meeting_link' => $i->meeting_link,
            'location' => $i->location, 'interviewer' => $i->interviewer_name];
    }

    /** POST recruitment/applications/{id}/notification-preview  {status} : the message the applicant would get */
    public function notificationPreview(Request $request, $id)
    {
        [$app, $err] = $this->app($id);
        if ($err) { return $err; }

        $v = $request->validate(['status' => 'required|string|in:' . implode(',', Pipeline::statuses())]);
        $event = ['review' => 'review', 'interview' => 'interview', 'approved' => 'approved', 'reject' => 'rejected', 'applied_active' => 'review'][$v['status']];

        return response()->json(['success' => true, 'event' => $event,
            'message' => Pipeline::defaultMessage($app, $event, $this->latestInterviewExtra($app->applied_id)),
            'channels' => array_values(array_filter(['chat', $app->email ? 'email' : null]))]);
    }

    // ======================================================================= delete

    /**
     * DELETE recruitment/applications/{id}
     * Permanently removes an application and everything tied to it: the applicant's answers for this job, CV file(s),
     * evaluations, interviews, notes and history. Only the job's owner (or a master administrator) may do this.
     * The applicant can apply again afterwards. A short audit line (who, which application) goes to the log.
     */
    public function destroy($id)
    {
        [$app, $err] = $this->app($id);
        if ($err) { return $err; }

        $files = DB::table('applied_resumes')->where('transNo', $app->transNo)->where('code', $app->code)->pluck('resume_pdf')->all();

        try {
            DB::transaction(function () use ($app) {
                $appliedId = $app->applied_id;
                DB::table('evaluation_overrides')->where('applied_id', $appliedId)->delete();
                DB::table('candidate_evaluations')->where('applied_id', $appliedId)->delete();
                DB::table('interviews')->where('applied_id', $appliedId)->delete();
                DB::table('candidate_notes')->where('applied_id', $appliedId)->delete();
                DB::table('application_status_history')->where('applied_id', $appliedId)->delete();
                DB::table('applied_answers')->where('applied_id', $appliedId)->delete();
                DB::table('applied_resumes')->where('transNo', $app->transNo)->where('code', $app->code)->delete();
                DB::table('applied_jobs')->where('applied_id', $appliedId)->delete();
            });
        } catch (\Throwable $e) {
            \Log::error('Deleting an application failed: ' . $e->getMessage());
            return $this->fail('The candidate could not be deleted. Nothing was removed.', 500);
        }

        // the CV files go too (private or the older public location)
        foreach ($files as $stored) {
            // a file that is still the applicant's saved CV, or attached to another application, is not removed
            if ($stored && preg_match('#uploads/.+$#', $stored, $m) && !str_contains($stored, '..') && !\App\Helpers\StoredCv::inUse($stored)) {
                foreach (['local', 'public'] as $disk) { Storage::disk($disk)->delete($m[0]); }
            }
        }

        \Log::info('Application deleted', ['application' => $app->applied_id, 'job' => $app->transNo, 'by' => Auth::user()->code]);

        return response()->json(['success' => true, 'message' => 'Candidate deleted.']);
    }

    // ======================================================================= notes

    public function addNote(Request $request, $id)
    {
        [$app, $err] = $this->app($id);
        if ($err) { return $err; }

        $v = $request->validate(['note' => 'required|string|min:2|max:3000']);
        $u = Auth::user();
        $noteId = DB::table('candidate_notes')->insertGetId(['applied_id' => $app->applied_id, 'note' => trim($v['note']),
            'created_by' => $u->code, 'author_name' => $u->fullname, 'created_at' => now(), 'updated_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Note added.', 'data' => DB::table('candidate_notes')->find($noteId)], 201);
    }

    // ======================================================================= interviews

    /** POST recruitment/applications/{id}/interviews : schedule (and move the candidate to Interview) */
    public function scheduleInterview(Request $request, $id)
    {
        [$app, $err] = $this->app($id);
        if ($err) { return $err; }

        $v = $request->validate([
            'scheduled_at' => 'required|date|after:now',
            'duration_min' => 'nullable|integer|between:5,480',
            'type' => 'required|in:online,onsite',
            'meeting_link' => 'nullable|required_if:type,online|url|max:500',
            'location' => 'nullable|required_if:type,onsite|string|max:255',
            'interviewer_name' => 'required|string|max:120',
            'notify' => 'nullable|boolean',
            'message' => 'nullable|string|max:3000',
        ], ['meeting_link.required_if' => 'Add the meeting link for an online interview.', 'location.required_if' => 'Add the location for an on-site interview.',
            'scheduled_at.after' => 'Choose a date and time in the future.']);

        $status = $app->applied_status ?: 'applied_active';
        if (in_array($status, ['approved', 'reject'], true)) {
            return $this->fail('Reopen the application before scheduling an interview.');
        }

        $user = Auth::user();
        $interviewId = DB::table('interviews')->insertGetId([
            'applied_id' => $app->applied_id, 'transNo' => $app->transNo, 'scheduled_at' => date('Y-m-d H:i:s', strtotime($v['scheduled_at'])),
            'duration_min' => $v['duration_min'] ?? 30, 'type' => $v['type'],
            'location' => $v['type'] === 'onsite' ? $v['location'] : null, 'meeting_link' => $v['type'] === 'online' ? $v['meeting_link'] : null,
            'interviewer_name' => $v['interviewer_name'], 'interviewer_code' => $user->code, 'status' => 'scheduled',
            'created_by' => $user->code, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $notify = (bool) ($v['notify'] ?? false);
        if ($status !== 'interview') {
            [$ok, $why] = Pipeline::changeStatus($app, 'interview', 'Interview scheduled', $user, 'recruiter', $notify, $v['message'] ?? null, $this->latestInterviewExtra($app->applied_id));
            if (!$ok) { DB::table('interviews')->where('id', $interviewId)->delete(); return $this->fail($why); }
        } elseif ($notify) {
            Pipeline::notify($app, 'interview', $user, $v['message'] ?? null, $this->latestInterviewExtra($app->applied_id));
        }

        return response()->json(['success' => true, 'message' => 'Interview scheduled.', 'data' => DB::table('interviews')->find($interviewId)], 201);
    }

    /** PUT recruitment/interviews/{id} : feedback, score, result, notes, complete or cancel. Never decides the candidate. */
    public function updateInterview(Request $request, $interviewId)
    {
        $i = DB::table('interviews')->where('id', $interviewId)->first();
        if (!$i) { return $this->fail('Interview not found.', 404); }
        [$app, $err] = $this->app($i->applied_id);
        if ($err) { return $err; }

        $v = $request->validate([
            'status' => 'nullable|in:scheduled,completed,cancelled',
            'feedback' => 'nullable|string|max:5000',
            'score' => 'nullable|integer|between:0,100',
            'result' => 'nullable|in:pass,hold,fail',
            'notes' => 'nullable|string|max:5000',
        ]);
        if (($v['status'] ?? null) === 'completed' && !isset($v['score']) && empty($v['feedback']) && $i->score === null && empty($i->feedback)) {
            return $this->fail('Add feedback or a score before marking the interview completed.');
        }

        DB::table('interviews')->where('id', $interviewId)->update(array_filter($v, fn ($x) => $x !== null) + ['updated_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Interview updated.', 'data' => DB::table('interviews')->find($interviewId)]);
    }

    // ======================================================================= AI evaluation

    /** POST recruitment/applications/{id}/evaluate */
    public function evaluate($id)
    {
        [$app, $err] = $this->app($id);
        if ($err) { return $err; }

        try {
            $result = EvaluationEngine::evaluate($app, Auth::user());
        } catch (\Throwable $e) {
            \Log::error('Candidate evaluation failed: ' . $e->getMessage());
            return $this->fail('The evaluation could not be completed. Please try again.', 500);
        }

        return response()->json(['success' => true, 'message' => 'Evaluation complete. A recruiter must review it before any decision.', 'data' => $result]);
    }

    /** GET recruitment/applications/{id}/evaluations : history, newest first */
    public function evaluations($id)
    {
        [$app, $err] = $this->app($id);
        if ($err) { return $err; }

        $rows = DB::table('candidate_evaluations')->where('applied_id', $app->applied_id)->orderByDesc('id')->get();

        return response()->json(['success' => true,
            'data' => $rows->map(fn ($r) => EvaluationEngine::format($r))->values(),
            'overrides' => DB::table('evaluation_overrides')->where('applied_id', $app->applied_id)->orderByDesc('id')->get()]);
    }

    /** POST recruitment/evaluations/{id}/overrides  {changes:[{question_id|null, score, reason}]} */
    public function override(Request $request, $evaluationId)
    {
        $row = DB::table('candidate_evaluations')->where('id', $evaluationId)->first();
        if (!$row) { return $this->fail('Evaluation not found.', 404); }
        [$app, $err] = $this->app($row->applied_id);
        if ($err) { return $err; }

        $v = $request->validate([
            'changes' => 'required|array|min:1|max:50',
            'changes.*.question_id' => 'nullable|integer',
            'changes.*.score' => 'required|integer|between:0,100',
            'changes.*.reason' => 'required|string|min:5|max:500',
        ], ['changes.*.reason.required' => 'Say why you are changing a score.', 'changes.*.reason.min' => 'Say why you are changing a score (at least 5 characters).']);

        return response()->json(['success' => true, 'message' => 'Scores updated. Your changes are logged.',
            'data' => EvaluationEngine::applyOverrides($row, $v['changes'], Auth::user())]);
    }

    // ======================================================================= evaluation settings

    /** GET recruitment/evaluation-settings?transNo= */
    public function settings(Request $request)
    {
        $transNo = $request->query('transNo');
        if ($transNo) { [, $err] = $this->job($transNo); if ($err) { return $err; } }

        return response()->json(['success' => true, 'data' => EvaluationEngine::settings($transNo ?: null) + ['transNo' => $transNo ?: null],
            'can_edit_global' => Auth::user()->role_code === 'DEF-MASTERADMIN']);
    }

    /** PUT recruitment/evaluation-settings : the job's own criteria, or (master admin) the global defaults */
    public function saveSettings(Request $request)
    {
        $v = $request->validate([
            'transNo' => 'nullable|string|max:50',
            'weights' => 'required|array', 'weights.education' => 'required|integer|between:0,100', 'weights.experience' => 'required|integer|between:0,100',
            'weights.skills' => 'required|integer|between:0,100', 'weights.answers' => 'required|integer|between:0,100', 'weights.competencies' => 'required|integer|between:0,100',
            'thresholds' => 'required|array', 'thresholds.strong' => 'required|integer|between:1,100',
            'thresholds.qualified' => 'required|integer|between:1,100', 'thresholds.review' => 'required|integer|between:1,100',
            'passing_score' => 'required|integer|between:0,100',
            'competencies' => 'nullable|string|max:3000',
        ]);

        if (array_sum($v['weights']) <= 0) { return $this->fail('At least one weight must be above 0.'); }
        $t = $v['thresholds'];
        if (!($t['strong'] > $t['qualified'] && $t['qualified'] > $t['review'])) {
            return $this->fail('Thresholds must go down: strong match above qualified above needs-review.');
        }

        $transNo = $v['transNo'] ?? null;
        if ($transNo) {
            [, $err] = $this->job($transNo);
            if ($err) { return $err; }
        } elseif (Auth::user()->role_code !== 'DEF-MASTERADMIN') {
            return $this->fail('Only a master administrator can change the global defaults.', 403);
        }

        $data = ['weights' => json_encode($v['weights']), 'thresholds' => json_encode($t), 'passing_score' => $v['passing_score'],
            'competencies' => $v['competencies'] ?? null, 'updated_by' => Auth::user()->code, 'updated_at' => now()];
        $exists = DB::table('evaluation_settings')->when($transNo, fn ($q) => $q->where('transNo', $transNo), fn ($q) => $q->whereNull('transNo'))->exists();
        if ($exists) {
            DB::table('evaluation_settings')->when($transNo, fn ($q) => $q->where('transNo', $transNo), fn ($q) => $q->whereNull('transNo'))->update($data);
        } else {
            DB::table('evaluation_settings')->insert($data + ['transNo' => $transNo, 'created_at' => now()]);
        }

        return response()->json(['success' => true, 'message' => $transNo ? 'Evaluation criteria saved for this job.' : 'Global evaluation defaults saved.',
            'data' => EvaluationEngine::settings($transNo)]);
    }
}
