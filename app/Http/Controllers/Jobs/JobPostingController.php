<?php

namespace App\Http\Controllers\Jobs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Jobs\JobPosting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Jobs\QuestionController;
use App\Models\Jobs\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class JobPostingController extends Controller
{

    // public function saveOrUpdateJobPosting(Request $request, $transNo = null)
    // {
    //     try {
    //         $user = Auth::user();

    //         // ✅ Validation
    //         $validated = $request->validate([
    //             'job_name'        => 'required|string|max:255',
    //             'job_position'    => 'required|string|max:255',
    //             'job_description' => 'required|string',
    //             'job_about'       => 'required|string',
    //             'qualification'   => 'required|string',
    //             'work_type'       => 'required|string',
    //             'job_image'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    //             'location'        => 'required|string|max:255',
    //             'benefits'        => 'required|string|max:255',
    //             'question_text'   => 'required|array|min:1',
    //             'question_text.*' => 'required|string|max:255',

    //             // ✅ ADD THIS
    //             'answer_type'          => 'required|array',
    //             'answer_type.*'        => 'required|in:yes,no,general',
    //         ]);

    //         DB::beginTransaction();

    //         // if ($request->hasFile('job_image')) {
    //         //     $file = $request->file('job_image');
    //         //     $uuid = Str::uuid();

    //         //     $folderPath = "uploads/{$user->code}/JobPosting/{$uuid}";
    //         //     $fileName = time() . '.' . $file->getClientOriginalExtension();

    //         //     $filePath = $file->storeAs($folderPath, $fileName, 'public');

    //         //     // ✅ FIX: proper public URL
    //         //     $validated['job_image'] = Storage::disk('public')->url($filePath);
    //         // }
    //         if ($request->hasFile('job_image')) {
    //             $file = $request->file('job_image');
    //             $uuid = Str::uuid();
    //             $folderPath = "uploads/{$user->code}/JobPosting/{$uuid}";
    //             $fileName = time() . '.' . $file->getClientOriginalExtension();
    //             $filePath = $file->storeAs($folderPath, $fileName, 'public');
    //             $validated['job_image'] = "/storage/app/public/" . $filePath;
    //         }

    //         // ---------- CREATE NEW JOB ----------
    //         if (!$transNo) {
    //             $lastTrans = JobPosting::orderByDesc('job_id')->first();
    //             $lastNumber = $lastTrans ? intval(substr($lastTrans->transNo, -6)) : 0;
    //             $transNo = "TR-" . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);

    //             $job = JobPosting::create([
    //                 'job_name'        => $validated['job_name'],
    //                 'job_position'    => $validated['job_position'],
    //                 'job_description' => $validated['job_description'],
    //                 'job_about'       => $validated['job_about'],
    //                 'qualification'   => $validated['qualification'],
    //                 'work_type'       => $validated['work_type'],
    //                 'job_image'       => $validated['job_image'] ?? null,
    //                 'location'        => $validated['location'],
    //                 'benefits'        => $validated['benefits'],
    //                 'code'            => $user->code,
    //                 'role_code'       => $user->role_code,
    //                 'fullname'        => $user->fullname,
    //                 'is_online'       => $user->is_online,
    //                 'company'         => $user->company,
    //                 'transNo'         => $transNo,
    //             ]);

    //             // Save all questions
    //             foreach ($validated['question_text'] as $index => $questionText) {
    //                 Question::create([
    //                     'question_text' => $questionText,
    //                     'answer_text'   => $validated['answer_text'][$index] ?? 'yes',
    //                     'job_name'      => $validated['job_name'],
    //                     'role_code'     => $user->role_code,
    //                     'code'          => $user->code,
    //                     'fullname'      => $user->fullname,
    //                     'company'       => $user->company,
    //                     'transNo'       => $transNo,
    //                 ]);
    //             }
    //         }
    //         // ---------- UPDATE EXISTING JOB ----------
    //         else {
    //             $job = JobPosting::where('transNo', $transNo)->firstOrFail();

    //             // Update job fields
    //             $job->update([
    //                 'job_name'        => $validated['job_name'],
    //                 'job_position'    => $validated['job_position'],
    //                 'job_description' => $validated['job_description'],
    //                 'job_about'       => $validated['job_about'],
    //                 'qualification'   => $validated['qualification'],
    //                 'work_type'       => $validated['work_type'],
    //                 'location'        => $validated['location'],
    //                 'benefits'        => $validated['benefits'],
    //                 'job_image'       => $validated['job_image'] ?? $job->job_image,
    //             ]);

    //             // Update questions:
    //             // Remove old questions for this job
    //             Question::where('transNo', $transNo)->delete();

    //             // Insert new/updated questions
    //             foreach ($validated['question_text'] as $index => $questionText) {
    //                 Question::create([
    //                     'question_text' => $questionText,
    //                     'answer_text'   => $validated['answer_text'][$index] ?? 'yes',
    //                     'job_name'      => $validated['job_name'],
    //                     'role_code'     => $user->role_code,
    //                     'code'          => $user->code,
    //                     'fullname'      => $user->fullname,
    //                     'company'       => $user->company,
    //                     'transNo'       => $transNo,
    //                 ]);
    //             }
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => $transNo ? 'Job updated successfully' : 'Job saved successfully',
    //             'transNo' => $transNo,
    //         ], $transNo ? 200 : 201);
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
    //             'message' => 'Something went wrong while saving the job.',
    //             'success' => false,
    //             'error'   => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function saveOrUpdateJobPosting(Request $request, $transNo = null)
    // {
    //     try {
    //         $user = Auth::user();

    //         // ✅ Validation
    //         $validated = $request->validate([
    //             'job_name'        => 'required|string|max:255',
    //             'job_position'    => 'required|string|max:255',
    //             'job_description' => 'required|string',
    //             'job_about'       => 'required|string',
    //             'qualification'   => 'required|string',
    //             'work_type'       => 'required|string',
    //             'job_image'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    //             'location'        => 'required|string|max:255',
    //             'benefits'        => 'required|string|max:255',

    //             // ✅ SALARY RANGE
    //             'min_salary'      => 'required|numeric|min:0',
    //             'max_salary'      => 'required|numeric|min:0|gte:min_salary',

    //             'question_text'   => 'required|array|min:1',
    //             'question_text.*' => 'required|string|max:255',

    //             // ✅ ADD THIS
    //             'answer_type'          => 'required|array',
    //             'answer_type.*'        => 'required|in:yes,no,general',
    //         ]);

    //         DB::beginTransaction();

    //         if ($request->hasFile('job_image')) {
    //             $file = $request->file('job_image');
    //             $uuid = Str::uuid();
    //             $folderPath = "uploads/{$user->code}/JobPosting/{$uuid}";
    //             $fileName = time() . '.' . $file->getClientOriginalExtension();
    //             $filePath = $file->storeAs($folderPath, $fileName, 'public');
    //             $validated['job_image'] = "/storage/app/public/" . $filePath;
    //         }

    //         // ---------- CREATE NEW JOB ----------
    //         if (!$transNo) {
    //             $lastTrans = JobPosting::orderByDesc('job_id')->first();
    //             $lastNumber = $lastTrans ? intval(substr($lastTrans->transNo, -6)) : 0;
    //             $transNo = "TR-" . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);

    //             $job = JobPosting::create([
    //                 'job_name'        => $validated['job_name'],
    //                 'job_position'    => $validated['job_position'],
    //                 'job_description' => $validated['job_description'],
    //                 'job_about'       => $validated['job_about'],
    //                 'qualification'   => $validated['qualification'],
    //                 'work_type'       => $validated['work_type'],
    //                 'job_image'       => $validated['job_image'] ?? null,
    //                 'location'        => $validated['location'],
    //                 'benefits'        => $validated['benefits'],

    //                 // ✅ SALARY RANGE
    //                 'min_salary'      => $validated['min_salary'],
    //                 'max_salary'      => $validated['max_salary'],

    //                 'code'            => $user->code,
    //                 'role_code'       => $user->role_code,
    //                 'fullname'        => $user->fullname,
    //                 'is_online'       => $user->is_online,
    //                 'company'         => $user->company,
    //                 'transNo'         => $transNo,
    //             ]);

    //             // Save all questions
    //             foreach ($validated['question_text'] as $index => $questionText) {
    //                 Question::create([
    //                     'question_text' => $questionText,
    //                     'answer_type'   => $validated['answer_type'][$index] ?? 'yes',
    //                     'job_name'      => $validated['job_name'],
    //                     'role_code'     => $user->role_code,
    //                     'code'          => $user->code,
    //                     'fullname'      => $user->fullname,
    //                     'company'       => $user->company,
    //                     'transNo'       => $transNo,
    //                 ]);
    //             }
    //         }
    //         // ---------- UPDATE EXISTING JOB ----------
    //         else {
    //             $job = JobPosting::where('transNo', $transNo)->firstOrFail();

    //             // Update job fields
    //             $job->update([
    //                 'job_name'        => $validated['job_name'],
    //                 'job_position'    => $validated['job_position'],
    //                 'job_description' => $validated['job_description'],
    //                 'job_about'       => $validated['job_about'],
    //                 'qualification'   => $validated['qualification'],
    //                 'work_type'       => $validated['work_type'],
    //                 'location'        => $validated['location'],
    //                 'benefits'        => $validated['benefits'],

    //                 // ✅ SALARY RANGE
    //                 'min_salary'      => $validated['min_salary'],
    //                 'max_salary'      => $validated['max_salary'],

    //                 'job_image'       => $validated['job_image'] ?? $job->job_image,
    //             ]);

    //             // Update questions:
    //             // Remove old questions for this job
    //             Question::where('transNo', $transNo)->delete();

    //             // Insert new/updated questions
    //             foreach ($validated['question_text'] as $index => $questionText) {
    //                 Question::create([
    //                     'question_text' => $questionText,
    //                     'answer_type'   => $validated['answer_type'][$index] ?? 'yes',
    //                     'job_name'      => $validated['job_name'],
    //                     'role_code'     => $user->role_code,
    //                     'code'          => $user->code,
    //                     'fullname'      => $user->fullname,
    //                     'company'       => $user->company,
    //                     'transNo'       => $transNo,
    //                 ]);
    //             }
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => $transNo ? 'Job updated successfully' : 'Job saved successfully',
    //             'transNo' => $transNo,
    //         ], $transNo ? 200 : 201);
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
    //             'message' => 'Something went wrong while saving the job.',
    //             'success' => false,
    //             'error'   => $e->getMessage(),
    //         ], 500);
    //     }
    // }


    // ------------------------------------------------------------------ job posting (create / edit / draft / publish)

    /** account types that may post jobs (the ones that have the Job Vacancies pages) */
    private const JOB_POSTERS = ['DEF-CLIENT', 'DEF-MASTERADMIN'];
    private const WORK_TYPES = ['Onsite', 'Work From Home', 'Hybrid'];
    private const EMPLOYMENT_TYPES = ['Full-time', 'Part-time', 'Contract', 'Internship', 'Freelance'];
    private const EXPERIENCE_LEVELS = ['Internship', 'Entry level', 'Associate', 'Mid-Senior level', 'Director', 'Executive'];
    private const SALARY_PERIODS = ['Hourly', 'Monthly', 'Yearly'];
    private const QUESTION_TYPES = ['yes_no', 'multiple_choice', 'short_text', 'long_text', 'numeric'];

    /**
     * POST job-posting            create
     * POST job-posting/{transNo}  edit (?_method=PUT)
     *
     * status=draft saves an unfinished job (only the title is required) that nobody else can see.
     * Anything else publishes it. The signed-in account must be a job poster, and may only edit its OWN jobs.
     * A published job can't go back to draft, and once a job has applicants its screening questions are locked
     * so their answers are never lost.
     */
    public function saveOrUpdateJobPosting(Request $request, $transNo = null)
    {
        $user = Auth::user();

        if (!in_array($user->role_code, self::JOB_POSTERS, true)) {
            return response()->json(['success' => false, 'message' => 'Your account type cannot post jobs.'], 403);
        }

        $isNewJob = empty($transNo);
        $job = null;

        if (!$isNewJob) {
            $job = JobPosting::where('transNo', $transNo)->first();
            if (!$job) {
                return response()->json(['success' => false, 'message' => 'Job not found.'], 404);
            }
            if ((string) $job->code !== (string) $user->code) {
                return response()->json(['success' => false, 'message' => 'You can only edit your own job postings.'], 403);
            }
        }

        $isDraft = $request->input('status') === 'draft';
        if ($isDraft && $job && $job->recordstatus === 'active') {
            return response()->json(['success' => false, 'message' => 'A published job can\'t be saved as a draft.'], 422);
        }

        // a job that already has applicants keeps its questions exactly as they are
        $locked = $job && DB::table('applied_jobs')->where('transNo', $job->transNo)->exists();

        try {
            $validated = $request->validate($this->jobRules($isDraft, $job, $locked));
            $this->assertJobConsistency($request, $validated, $isDraft, $job, $locked);

            DB::beginTransaction();

            // ---------- image
            if ($request->hasFile('job_image')) {
                if ($job && $job->job_image) {
                    if (preg_match('#uploads/.+$#', $job->job_image, $old)) {
                        Storage::disk('public')->delete($old[0]);
                    }
                }
                $file = $request->file('job_image');
                $folderPath = "uploads/{$user->code}/JobPosting/" . Str::uuid();
                $fileName = time() . '.' . $file->getClientOriginalExtension();
                // stored without a host so it works on local and production
                $validated['job_image'] = '/storage/' . $file->storeAs($folderPath, $fileName, 'public');
            }

            // ---------- the job row. On edit only the fields that were sent are touched, so the old
            // edit form (which does not know the new fields) never blanks them.
            $attrs = [];
            foreach ([
                'job_name', 'job_position', 'job_description', 'job_about', 'qualification', 'work_type', 'location', 'benefits',
                'currency', 'min_salary', 'max_salary', 'vacancies', 'employment_type', 'experience_level', 'salary_period',
                'education', 'preferred_qualifications', 'application_deadline', 'application_limit', 'min_pass_score', 'job_image',
            ] as $key) {
                if ($request->has($key) || ($key === 'job_image' && isset($validated['job_image']))) {
                    $attrs[$key] = $validated[$key] ?? null;
                }
            }
            // these columns are NOT NULL: a cleared field on a draft is stored as empty text, never NULL
            foreach (['job_position', 'job_description', 'job_about', 'qualification', 'work_type'] as $k) {
                if (array_key_exists($k, $attrs) && $attrs[$k] === null) { $attrs[$k] = ''; }
            }
            if ($request->has('required_skills')) {
                $skills = array_values(array_filter(array_map('trim', $validated['required_skills'] ?? [])));
                $attrs['required_skills'] = $skills ? json_encode($skills) : null;
            }
            // template: must be a published one (or the one the job already has); a new job without one gets the default
            $templateId = $this->resolveTemplateId($request, $job, $isNewJob);
            if ($templateId !== false) { $attrs['template_id'] = $templateId; }

            foreach (['allow_cv', 'auto_screen'] as $flag) {
                if ($request->has($flag)) { $attrs[$flag] = $request->boolean($flag); }
            }

            if ($isNewJob) {
                // NOT NULL text columns: an unfinished draft stores empty text
                foreach (['job_position', 'job_description', 'job_about', 'qualification', 'work_type'] as $k) {
                    $attrs[$k] = $attrs[$k] ?? '';
                }

                // Next number = highest TR-###### used ANYWHERE + 1. Deleting a job leaves its applications behind, so
                // the number must also skip every number still referenced by applications, résumés and questions:
                // otherwise a new job would inherit the old job's applicants.
                $lastNumber = 0;
                foreach (['jobposting', 'applied_jobs', 'applied_questions', 'applied_resumes'] as $table) {
                    $lastNumber = max($lastNumber, (int) DB::table($table)
                        ->whereRaw("transNo REGEXP '^TR-[0-9]{6}$'")
                        ->max(DB::raw('CAST(SUBSTRING(transNo, 4) AS UNSIGNED)')));
                }
                $transNo = 'TR-' . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);

                $job = JobPosting::create($attrs + [
                    'code'         => $user->code,           // ownership: always the signed-in account
                    'role_code'    => $user->role_code,
                    'fullname'     => $user->fullname,
                    'is_online'    => $user->is_online,
                    'company'      => $user->company,
                    'transNo'      => $transNo,
                    'recordstatus' => $isDraft ? 'draft' : 'active',
                ]);
            } else {
                $job->update($attrs + ['recordstatus' => $isDraft ? 'draft' : 'active']);
            }

            // ---------- screening questions
            if (!$locked) {
                $this->saveQuestions($request, $job, $user);
            }

            DB::commit();

            $created = $isNewJob && !$isDraft;
            return response()->json([
                'success' => true,
                'message' => $isDraft ? 'Draft saved' : ($created ? 'Job saved successfully' : 'Job updated successfully'),
                'transNo' => $job->transNo,
                'status' => $job->recordstatus,
                'questions_locked' => (bool) $locked,
            ], $created ? 201 : 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while saving the job.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * The template to store on the job: an id, null, or false when nothing should change.
     * Only a published, not-deleted template can be newly assigned; the job's current one may stay.
     */
    private function resolveTemplateId(Request $request, $job, bool $isNewJob)
    {
        $requested = $request->input('template_id');
        $hasField = $request->has('template_id') && $requested !== '' && $requested !== null;

        if ($hasField) {
            $id = (int) $requested;
            if ($job && (int) $job->template_id === $id) {
                return false;                                   // unchanged
            }
            if (!\App\Models\Jobs\JobTemplate::published()->whereKey($id)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['template_id' => 'Choose one of the available templates.']);
            }
            return $id;
        }

        if ($isNewJob) {
            $default = \App\Models\Jobs\JobTemplate::published()->where('is_default', true)->value('id');
            return $default ?: null;
        }

        return false;
    }

    private function jobRules(bool $isDraft, $job, bool $locked): array
    {
        $must = fn (string $rule) => ($isDraft ? 'nullable|' : 'required|') . $rule;

        $deadline = ['nullable', 'date_format:Y-m-d'];
        // a deadline already saved on the job may stay; a new or changed one must not be in the past
        if (!$job || $this->deadlineChanged($job)) {
            $deadline[] = 'after_or_equal:today';
        }

        $rules = [
            'status'          => 'nullable|in:draft,publish',
            'job_name'        => 'required|string|max:255',
            'job_position'    => $must('string|max:255'),
            'job_description' => $must('string'),
            'job_about'       => $must('string'),
            'qualification'   => $must('string'),
            'work_type'       => $must('string|in:' . implode(',', self::WORK_TYPES)),
            'job_image'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'location'        => $must('string|max:255'),
            'benefits'        => 'nullable|string|max:2000',
            'currency'        => $must('string|max:10'),
            'min_salary'      => $must('numeric|min:0'),
            'max_salary'      => $isDraft ? 'nullable|numeric|min:0' : 'required|numeric|min:0|gte:min_salary',

            'vacancies'       => 'nullable|integer|min:1|max:10000',
            'employment_type' => 'nullable|in:' . implode(',', self::EMPLOYMENT_TYPES),
            'experience_level' => 'nullable|in:' . implode(',', self::EXPERIENCE_LEVELS),
            'salary_period'   => 'nullable|in:' . implode(',', self::SALARY_PERIODS),
            'education'       => 'nullable|string|max:255',
            'required_skills' => 'nullable|array|max:30',
            'required_skills.*' => 'string|max:60',
            'preferred_qualifications' => 'nullable|string|max:5000',

            'application_deadline' => $deadline,
            'allow_cv'          => 'nullable|boolean',
            'application_limit' => 'nullable|integer|min:1|max:100000',
            'min_pass_score'    => 'nullable|integer|between:0,100',
            'auto_screen'       => 'nullable|boolean',
            'template_id'       => 'nullable|integer',
        ];

        if (!$locked) {
            $rules += [
                'question_text'     => $isDraft ? 'nullable|array' : 'required|array|min:1',
                'question_text.*'   => 'required|string|max:255',
                'answer_type'       => 'nullable|array',
                'answer_type.*'     => 'nullable|in:yes,no,general',
                'question_type'     => 'nullable|array',
                'question_type.*'   => 'nullable|in:' . implode(',', self::QUESTION_TYPES),
                'options'           => 'nullable|array',
                'options.*'         => 'nullable|array|max:10',
                'options.*.*'       => 'nullable|string|max:120',
                'is_required'       => 'nullable|array',
                'is_required.*'     => 'nullable|boolean',
                'correct_answer'    => 'nullable|array',
                'correct_answer.*'  => 'nullable|string|max:255',
            ];
        }

        return $rules;
    }

    private function deadlineChanged($job): bool
    {
        $sent = request()->input('application_deadline');
        $saved = $job->application_deadline ? substr((string) $job->application_deadline, 0, 10) : null;

        return $sent !== null && $sent !== '' && $sent !== $saved;
    }

    /** rules that depend on several fields at once; throws a 422 like normal validation */
    private function assertJobConsistency(Request $request, array $v, bool $isDraft, $job, bool $locked): void
    {
        $errors = [];

        if ($isDraft && isset($v['min_salary'], $v['max_salary']) && (float) $v['max_salary'] < (float) $v['min_salary']) {
            $errors['max_salary'] = ['The maximum salary must be at least the minimum salary.'];
        }

        if (!$locked) {
            $texts = $v['question_text'] ?? [];
            $hasObjective = false;

            foreach ($texts as $i => $text) {
                $type = $v['question_type'][$i] ?? null;
                $opts = array_values(array_unique(array_filter(array_map('trim', $v['options'][$i] ?? []), fn ($o) => $o !== '')));
                $correct = trim((string) ($v['correct_answer'][$i] ?? ''));

                if ($type === 'multiple_choice' && count($opts) < 2) {
                    $errors["question_text.$i"] = ['A multiple choice question needs at least two different options.'];
                } elseif ($type === 'multiple_choice' && $correct !== '' && !in_array($correct, $opts, true)) {
                    $errors["correct_answer.$i"] = ['The correct answer must be one of the options.'];
                } elseif ($type === 'yes_no' && $correct !== '' && !in_array(strtolower($correct), ['yes', 'no'], true)) {
                    $errors["correct_answer.$i"] = ['The correct answer must be Yes or No.'];
                } elseif ($type === 'numeric' && $correct !== '' && !is_numeric($correct)) {
                    $errors["correct_answer.$i"] = ['The correct answer must be a number.'];
                }

                if ($correct !== '' && in_array($type, ['yes_no', 'multiple_choice', 'numeric'], true)) {
                    $hasObjective = true;
                }
            }

            if (!$isDraft && $request->boolean('auto_screen')) {
                if (!$hasObjective) {
                    $errors['auto_screen'] = ['Automatic screening needs at least one question with a correct answer.'];
                }
                if (!isset($v['min_pass_score'])) {
                    $errors['min_pass_score'] = ['Set the minimum passing score for automatic screening.'];
                }
            }
        }

        if ($errors) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }

    /** Replace the job's screening questions (only called when the job has no applicants yet). */
    private function saveQuestions(Request $request, $job, $user): void
    {
        Question::where('transNo', $job->transNo)->delete();

        $texts = $request->input('question_text', []);
        $order = 0;

        foreach ($texts as $i => $text) {
            $type = $request->input("question_type.$i");
            $legacyType = $request->input("answer_type.$i");
            $opts = array_values(array_unique(array_filter(array_map('trim', (array) $request->input("options.$i", [])), fn ($o) => $o !== '')));
            $correct = trim((string) $request->input("correct_answer.$i", ''));

            if ($type === 'yes_no' && $correct !== '') { $correct = strtolower($correct); }
            if (!in_array($type, ['yes_no', 'multiple_choice', 'numeric'], true)) { $correct = ''; }   // free text can't be auto-scored

            Question::create([
                'question_text'  => $text,
                // `answer_type` keeps the meaning the existing apply screens understand: yes/no -> a Yes/No select, anything else -> free text
                'answer_type'    => $type ? ($type === 'yes_no' ? 'yes' : 'general') : ($legacyType ?: null),
                'question_type'  => $type ?: null,
                'options'        => $type === 'multiple_choice' ? json_encode($opts) : null,
                'is_required'    => $request->has("is_required.$i") ? $request->boolean("is_required.$i") : true,
                'correct_answer' => $correct !== '' ? $correct : null,
                'sort_order'     => $order++,
                'job_name'       => $job->job_name,
                'role_code'      => $user->role_code,
                'code'           => $user->code,
                'company'        => $user->company,
                'transNo'        => $job->transNo,
            ]);
        }
    }

    public function saveOrUpdateJobPostingOld(Request $request, $transNo = null)
    {
        try {
            $user = Auth::user();
            $validated = $request->validate([
                'job_name'        => 'required|string|max:255',
                'job_position'    => 'required|string|max:255',
                'job_description' => 'required|string',
                'job_about'       => 'required|string',
                'qualification'   => 'required|string',
                'work_type'       => 'required|string',
                'job_image'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'location'        => 'required|string|max:255',
                'benefits'        => 'required|string|max:255',

                // Salary
                'min_salary'      => 'required|numeric|min:0',
                'max_salary'      => 'required|numeric|min:0|gte:min_salary',

                // Questions
                'question_text'   => 'required|array|min:1',
                'question_text.*' => 'required|string|max:255',

                // Accept answer_type from frontend,
                // but DO NOT save it because the database column does not exist.
                'answer_type'     => 'nullable|array',
                'answer_type.*'   => 'nullable|in:yes,no,general',
            ]);

            DB::beginTransaction();

            // ---------------------------------------------------------
            // DETERMINE CREATE OR UPDATE
            // ---------------------------------------------------------
            $isNewJob = empty($transNo);

            // ---------------------------------------------------------
            // UPLOAD JOB IMAGE
            // ---------------------------------------------------------
            if ($request->hasFile('job_image')) {

                $file = $request->file('job_image');

                $uuid = Str::uuid();

                $folderPath = "uploads/{$user->code}/JobPosting/{$uuid}";

                $fileName = time() . '.' . $file->getClientOriginalExtension();

                $filePath = $file->storeAs(
                    $folderPath,
                    $fileName,
                    'public'
                );

                $validated['job_image'] = "/storage/app/public/" . $filePath;
            }

            // =========================================================
            // CREATE NEW JOB
            // =========================================================
            if ($isNewJob) {

                // Get latest transaction number
                $lastTrans = JobPosting::orderByDesc('job_id')->first();

                $lastNumber = 0;

                if ($lastTrans && !empty($lastTrans->transNo)) {
                    $lastNumber = intval(
                        substr($lastTrans->transNo, -6)
                    );
                }

                $transNo = "TR-" . str_pad(
                    $lastNumber + 1,
                    6,
                    '0',
                    STR_PAD_LEFT
                );

                // -----------------------------------------------------
                // CREATE JOB
                // -----------------------------------------------------
                $job = JobPosting::create([
                    'job_name'        => $validated['job_name'],
                    'job_position'    => $validated['job_position'],
                    'job_description' => $validated['job_description'],
                    'job_about'       => $validated['job_about'],
                    'qualification'   => $validated['qualification'],
                    'work_type'       => $validated['work_type'],
                    'job_image'       => $validated['job_image'] ?? null,
                    'location'        => $validated['location'],
                    'benefits'        => $validated['benefits'],

                    // Salary
                    'min_salary'      => $validated['min_salary'],
                    'max_salary'      => $validated['max_salary'],

                    // User information
                    'code'            => $user->code,
                    'role_code'       => $user->role_code,
                    'fullname'        => $user->fullname,
                    'is_online'       => $user->is_online,
                    'company'         => $user->company,

                    // Transaction
                    'transNo'         => $transNo,
                ]);

                // -----------------------------------------------------
                // SAVE QUESTIONS
                // -----------------------------------------------------
                foreach ($validated['question_text'] as $index => $questionText) {

                    Question::create([
                        'question_text' => $questionText,

                        // IMPORTANT:
                        // Do NOT include answer_type here because
                        // applied_questions does not have this column.

                        'job_name'      => $validated['job_name'],
                        'role_code'     => $user->role_code,
                        'code'          => $user->code,
                        'company'       => $user->company,
                        'transNo'       => $transNo,
                    ]);
                }
            }

            // =========================================================
            // UPDATE EXISTING JOB
            // =========================================================
            else {

                $job = JobPosting::where('transNo', $transNo)
                    ->firstOrFail();

                // -----------------------------------------------------
                // UPDATE JOB
                // -----------------------------------------------------
                $job->update([
                    'job_name'        => $validated['job_name'],
                    'job_position'    => $validated['job_position'],
                    'job_description' => $validated['job_description'],
                    'job_about'       => $validated['job_about'],
                    'qualification'   => $validated['qualification'],
                    'work_type'       => $validated['work_type'],
                    'location'        => $validated['location'],
                    'benefits'        => $validated['benefits'],

                    // Salary
                    'min_salary'      => $validated['min_salary'],
                    'max_salary'      => $validated['max_salary'],

                    // Keep old image if no new image uploaded
                    'job_image'       => $validated['job_image']
                        ?? $job->job_image,
                ]);

                // -----------------------------------------------------
                // DELETE OLD QUESTIONS
                // -----------------------------------------------------
                Question::where('transNo', $transNo)->delete();

                // -----------------------------------------------------
                // INSERT UPDATED QUESTIONS
                // -----------------------------------------------------
                foreach ($validated['question_text'] as $index => $questionText) {

                    Question::create([
                        'question_text' => $questionText,

                        // Do NOT include answer_type.
                        // The applied_questions table does not have it.

                        'job_name'      => $validated['job_name'],
                        'role_code'     => $user->role_code,
                        'code'          => $user->code,
                        'company'       => $user->company,
                        'transNo'       => $transNo,
                    ]);
                }
            }

            // ---------------------------------------------------------
            // COMMIT
            // ---------------------------------------------------------
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $isNewJob
                    ? 'Job saved successfully'
                    : 'Job updated successfully',
                'transNo' => $transNo,
            ], $isNewJob ? 201 : 200);
        } catch (\Illuminate\Validation\ValidationException $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while saving the job.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }




    public function getApplicationScore($transNo)
    {
        $rows = Question::where('transNo', $transNo)->get();

        if ($rows->isEmpty()) {
            return response()->json(['message' => 'Record not found'], 404);
        }

        $answers = $rows->map(function ($item) {
            $ansText = strtolower($item->answer_text);
            $ansType = strtolower($item->answer_type);

            // --- SCORING LOGIC ---
            $scoreValue = 0;

            if ($ansText === 'yes') {
                $scoreValue = 1.0;
            } elseif ($ansText === 'general' || $ansType === 'general') {
                $scoreValue = 0.5; // "Medium" Score
            } else {
                $scoreValue = 0.0;
            }

            return [
                'question_text' => $item->question_text,
                'answer_text'   => $item->answer_text ?? 'N/A',
                'numeric_score' => $scoreValue,
                'is_correct'    => $ansText === 'yes'
            ];
        });

        $totalScore = $answers->sum('numeric_score');
        $totalQuestions = $answers->count();

        return response()->json([
            'answers' => $answers,
            'summary' => [
                'total_score'    => $totalScore,
                'total_possible' => $totalQuestions,
                'percentage'     => $totalQuestions > 0 ? round(($totalScore / $totalQuestions) * 100) : 0
            ]
        ], 200);
    }


    public function getApplicationScorexx($transNo)
    {
        // 1. Fetch all rows for this specific transaction
        $rows = Question::where('transNo', $transNo)->get();

        if ($rows->isEmpty()) {
            return response()->json(['message' => 'No data found'], 404);
        }

        // 2. Map data to the format your Angular Component expects
        $answers = $rows->map(function ($item) {
            // Logic: 'yes' is correct, everything else (no, null, general) is false
            $isCorrect = strtolower($item->answer_text) === 'yes';

            return [
                'question_text' => $item->question_text,
                'answer_text'   => $item->answer_text ?? 'N/A',
                'correct'       => $isCorrect, // This drives your Angular this.score
                'answer_type'   => $item->answer_type
            ];
        });

        // 3. Calculate Summary for the Header
        $total = $answers->count();
        $correctCount = $answers->where('correct', true)->count();
        $percentage = $total > 0 ? round(($correctCount / $total) * 100) : 0;

        return response()->json([
            'user' => [
                'job_name' => $rows->first()->job_name,
                'company'  => $rows->first()->company,
                'status'   => $rows->first()->status,
            ],
            'scoring' => [
                'totalQuestions' => $total,
                'score' => $correctCount,
                'percentage' => $percentage
            ],
            'answers' => $answers,
            'resumes' => [] // Add logic to fetch resume URLs here if needed
        ], 200);
    }

    public function saveJobPostingss(Request $request)
    {
        try {
            $user = Auth::user();

            $lastTrans = JobPosting::orderByDesc('job_id')->first();
            $lastNumber = $lastTrans ? intval(substr($lastTrans->transNo, -6)) : 0;
            $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);

            $transNo = "TR-$newNumber";


            // ✅ Validate request
            $validated = $request->validate([
                'job_name'        => 'required|string|max:255',
                'job_position'    => 'required|string|max:255',
                'job_description' => 'required|string',
                'job_about'       => 'required|string',
                'qualification'   => 'required|string',
                'work_type'       => 'required|string',
                'job_image'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'location'        => 'required|string|max:255',
                'benefits'        => 'required|string|max:255',
                'question_text'   => 'required|array|min:1',         // 🔑 must be an array
                'question_text.*' => 'required|string|max:255',      // 🔑 each item must be string
            ]);

            // ✅ Handle file upload
            if ($request->hasFile('job_image')) {
                $file = $request->file('job_image');
                $uuid = Str::uuid();
                $folderPath = "uploads/{$user->code}/JobPosting/{$uuid}";
                $fileName = time() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs($folderPath, $fileName, 'public');
                $validated['job_image'] = "/storage/app/public/" . $filePath;
            }

            DB::beginTransaction();

            // ✅ Save Job Posting
            $job = JobPosting::create([
                'job_name'        => $validated['job_name'],
                'job_position'    => $validated['job_position'],
                'job_description' => $validated['job_description'],
                'job_about'       => $validated['job_about'],
                'qualification'   => $validated['qualification'],
                'work_type'       => $validated['work_type'],
                'job_image'       => $validated['job_image'] ?? null,
                'location'        => $validated['location'],
                'benefits'        => $validated['benefits'],
                'code'            => $user->code,
                'role_code'       => $user->role_code,
                'fullname'        => $user->fullname,
                'is_online'       => $user->is_online,
                'company'         => $user->company,
                'transNo'        => $transNo,
            ]);

            // ✅ Save multiple Questions
            foreach ($validated['question_text'] as $index => $questionText) {
                Question::create([
                    'question_text' => $questionText,
                    'answer_type'   => $validated['answer_type'][$index] ?? 'yes',
                    // 'job_id'        => $job->id,
                    'job_name'      => $validated['job_name'],
                    'role_code'     => $user->role_code,
                    'code'          => $user->code,
                    'fullname'      => $user->fullname,
                    'company'       => $user->company,
                    'transNo'      => $transNo,
                ]);
            }

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Job saved successfully',
                'transNo' => $transNo,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Validation failed.',
                'success' => false,
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Something went wrong while saving the job.',
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    public function updateJobPosting(Request $request, $transNo)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 401);
        }

        $currentUserCode = Auth::user()->code;
        $role_code = Auth::user()->role_code;
        $fname = Auth::user()->fname;
        $is_online = Auth::user()->is_online;
        // ✅ Validation
        $validator = Validator::make($request->all(), [
            'job_name'        => 'required|string|max:255',
            'job_position'    => 'required|string|max:255',
            'job_description' => 'required|string',
            'job_about'       => 'required|string',
            'qualification'   => 'required|string',
            'work_type'       => 'required|string',
            //  'comp_name'       => 'required|string|max:255',
            //    'comp_description'=> 'required|string',
            'job_image'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            // ✅ Find only if belongs to current user
            $job = JobPosting::where('transNo', $transNo)
                ->where('code', $currentUserCode)
                ->first();

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job posting not found.',
                ], 404);
            }

            // ✅ Handle image upload
            if ($request->hasFile('job_image')) {
                $file = $request->file('job_image');
                $uuid = Str::uuid();
                $folderPath = "uploads/{$currentUserCode}/JobPosting/{$uuid}";
                $fileName = time() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs($folderPath, $fileName, 'public');
                $job->job_image = "/storage/app/public/" . $filePath;
            }

            // ✅ Update fields
            $job->job_name        = $request->job_name;
            $job->job_position    = $request->job_position;
            $job->job_description = $request->job_description;
            $job->job_about       = $request->job_about;
            $job->qualification   = $request->qualification;
            $job->work_type       = $request->work_type;
            $job->comp_name       = $request->comp_name;
            $job->comp_description = $request->comp_description;
            $job->code            = $currentUserCode;
            $job->role_code       = $role_code;
            $job->fname           = $fname;
            $job->is_online       = $is_online;
            $job->updated_at      = now();

            $job->save();

            return response()->json([
                'success' => true,
                'message' => 'Jobs updated successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update job posting.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Browser-ready URL for a stored job image, for the CURRENT host. Old rows hold an absolute URL on another
     * host (local / production), /storage/app/public/... or /storage/...; all map to <this host>/storage/uploads/...
     */
    private function jobImageUrl(?string $stored): ?string
    {
        if (!$stored) {
            return null;
        }
        if (preg_match('#uploads/.+$#', $stored, $m)) {
            return url('storage/' . $m[0]);
        }
        if (preg_match('#^https?://#i', $stored)) {
            return $stored;
        }
        $stored = str_replace('/storage/app/public/', '/storage/', $stored);

        return url(ltrim($stored, '/'));
    }

    public function getJobPostingsByCode()
    {
        try {
            $code = Auth::user()->code;

            $jobs = JobPosting::where('code', $code)
                ->orderBy('created_at', 'asc')
                ->get();

            $jobs->each(function ($j) { $j->job_image = $this->jobImageUrl($j->job_image); });

            // Get all transNo values from the jobs collection
            $transNos = $jobs->pluck('transNo');

            $questions = Question::where('code', $code)
                ->whereIn('transNo', $transNos)
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'jobs' => $jobs,
                'questions' => $questions
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching job postings.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * GET getJobPostingByTransNo/{transNo}
     * Applicants load a job and its questions here. Drafts are visible only to their owner, and nothing that gives
     * the answers away (correct answers, other applicants' answers) is sent to anyone but the owner.
     */
    public function getJobPostingByTransNo($transNo)
    {
        try {
            $viewer = Auth::user();

            $job = DB::table('jobposting')->where('transNo', $transNo)->first();
            $isOwner = $job && $viewer && (string) $job->code === (string) $viewer->code;

            if (!$job || ($job->recordstatus !== 'active' && !$isOwner)) {
                return response()->json(['success' => false, 'message' => 'Job posting not found.'], 404);
            }

            $questions = DB::table('applied_questions')
                ->where('transNo', $transNo)
                ->orderBy('sort_order')->orderBy('question_id')
                ->get()
                ->map(function ($q) use ($isOwner) {
                    $q->options = $q->options ? (json_decode($q->options, true) ?: []) : [];
                    $q->is_required = $q->is_required === null ? true : (bool) $q->is_required;
                    if (!$isOwner) {
                        unset($q->correct_answer, $q->answer_text);
                    }
                    return $q;
                })->values();

            $job->required_skills = $job->required_skills ? (json_decode($job->required_skills, true) ?: []) : [];
            $job->job_image = $this->jobImageUrl($job->job_image);

            // is this job still taking applications?
            $applications = DB::table('applied_jobs')->where('transNo', $transNo)->count();
            $closedReason = null;
            if ($job->recordstatus !== 'active') {
                $closedReason = 'This job has not been published.';
            } elseif ($job->application_deadline && now()->startOfDay()->gt(\Carbon\Carbon::parse($job->application_deadline))) {
                $closedReason = 'The application deadline has passed.';
            } elseif ($job->application_limit && $applications >= $job->application_limit) {
                $closedReason = 'This job has reached its application limit.';
            }

            return response()->json([
                'success'   => true,
                'job'       => $job,
                'questions' => $questions,
                'meta'      => [
                    'is_owner' => (bool) $isOwner,
                    'closed' => $closedReason !== null,
                    'closed_reason' => $closedReason,
                    'applications' => $isOwner ? $applications : null,
                    'questions_locked' => $isOwner ? $applications > 0 : null,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteJobPosting($transNo)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.',
                ], 401);
            }

            // Find job by transNo + owner code
            $job = JobPosting::where('transNo', $transNo)
                ->where('code', $user->code)
                ->first();

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found or you are not authorized to delete it.',
                ], 404);
            }

            DB::beginTransaction();
            // Delete job image from storage if exists
            if ($job->job_image && Storage::exists($job->job_image)) {
                Storage::delete($job->job_image);
            }

            // Delete related applied questions
            DB::table('applied_questions')
                ->where('transNo', $transNo)
                ->delete();

            $job->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Job deleted successfully.',
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the job.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
