<?php

namespace App\Services\Recruitment;

use App\Models\Jobs\JobPosting;
use App\Services\CvImport\CvParser;
use App\Services\CvImport\CvTextExtractor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * AI-assisted candidate evaluation.
 *
 * FAIRNESS: the evaluator is only ever given job-related facts: education, work history, skills, certifications and the
 * screening answers. Never the candidate's name, age, date of birth, gender, photo, address, family details or any other
 * personal or protected attribute, and it is told never to infer them. (Candidate rows hold `age` and `date_birth`: they
 * are not selected.) The result is a recommendation for a person to review: nothing here changes an application status.
 *
 * PROVIDER: Anthropic when ANTHROPIC_API_KEY is configured, otherwise (or if a call fails) the built-in rules engine,
 * which scores the same categories with transparent heuristics. Every stored evaluation records which one produced it.
 */
class EvaluationEngine
{
    public const CATEGORIES = ['skills', 'experience', 'education', 'requirements', 'certifications'];
    public const ENGINE_VERSION = '2';

    // ===================================================================== settings (weights, thresholds, criteria)

    public static function settings(?string $transNo): array
    {
        $global = DB::table('evaluation_settings')->whereNull('transNo')->first();
        $job = $transNo ? DB::table('evaluation_settings')->where('transNo', $transNo)->first() : null;
        $row = $job ?: $global;

        $weights = $row ? (json_decode($row->weights, true) ?: []) : [];
        // weights saved for the earlier category set can't be mapped onto these five: use the defaults
        if (!isset($weights['requirements'])) { $weights = []; }

        return [
            'scope'         => $job ? 'job' : 'global',
            'weights'       => array_merge(config('recruitment.default_weights'), array_intersect_key($weights, array_flip(self::CATEGORIES))),
            'thresholds'    => config('recruitment.levels'),
            'passing_score' => (int) ($row->passing_score ?? 70),
            'competencies'  => self::lines($row->competencies ?? ''),
        ];
    }

    private static function lines(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $text)), fn ($l) => $l !== ''));
    }

    // ===================================================================== what the evaluator is allowed to see

    /** the job's requirements and the candidate's work-related facts. No identity, no protected attributes. */
    public static function buildInput(object $app, array $settings): array
    {
        $job = $app->job;
        $code = $app->code;

        $questions = DB::table('applied_questions')->where('transNo', $app->transNo)->orderBy('sort_order')->orderBy('question_id')->get();
        $answers = DB::table('applied_answers')->where('applied_id', $app->applied_id)->pluck('answer_text', 'question_id');

        $skills = [];
        foreach (DB::table('userskills')->where('code', $code)->pluck('skills') as $s) {
            foreach (preg_split('/[,;\n]/', (string) $s) as $one) { if (trim($one) !== '') { $skills[] = trim($one); } }
        }

        $required = $job->required_skills ? (json_decode($job->required_skills, true) ?: []) : [];

        $input = [
            'job' => [
                'title' => $job->job_name, 'department' => $job->job_position, 'employment_type' => $job->employment_type,
                'experience_level' => $job->experience_level, 'education_requirement' => $job->education,
                'qualifications' => $job->qualification, 'preferred_qualifications' => $job->preferred_qualifications,
                'required_skills' => $required, 'description' => $job->job_description, 'summary' => $job->job_about,
                'competencies' => $settings['competencies'],
            ],
            'candidate' => [
                'education' => DB::table('usereducations')->where('code', $code)->get(['highest_education', 'school_name', 'start_year', 'end_year'])
                    ->map(fn ($e) => ['level' => $e->highest_education, 'school' => $e->school_name, 'from' => $e->start_year, 'to' => $e->end_year])->all(),
                'experience' => DB::table('useremploymentrecords')->where('code', $code)->get(['company_name', 'position', 'job_description', 'date_completed'])
                    ->map(fn ($e) => ['company' => $e->company_name, 'position' => $e->position, 'description' => $e->job_description, 'until' => $e->date_completed])->all(),
                'skills' => array_values(array_unique($skills)),
                'certifications' => DB::table('usercertificates')->where('code', $code)->pluck('certificate_title')->all(),
                'trainings' => DB::table('usertrainings')->where('code', $code)->pluck('training_title')->all(),
            ],
            'questions' => $questions->map(function ($q) use ($answers) {
                return [
                    'id' => (int) $q->question_id, 'text' => $q->question_text, 'type' => $q->question_type ?: 'short_text',
                    'required' => $q->is_required === null ? true : (bool) $q->is_required,
                    'options' => $q->options ? (json_decode($q->options, true) ?: []) : [],
                    'expected_answer' => ($q->correct_answer !== null && $q->correct_answer !== '') ? $q->correct_answer : null,
                    'answer' => (string) ($answers[$q->question_id] ?? ''),
                ];
            })->all(),
        ];

        return self::fillFromResume($app, $input);
    }

    /**
     * Sections the profile leaves empty are read from the PDF sent with this application. Only work-related sections are
     * taken from it (never the name, address, birth date or photo). An unreadable file is simply not used.
     */
    private static function fillFromResume(object $app, array $input): array
    {
        $input['from_resume'] = [];
        $c = $input['candidate'];
        if ($c['education'] && $c['experience'] && $c['skills'] && $c['certifications']) { return $input; }

        $stored = DB::table('applied_resumes')->where('transNo', $app->transNo)->where('code', $app->code)->orderByDesc('resume_id')->value('resume_pdf');
        if (!$stored || str_contains($stored, '..') || !preg_match('#uploads/.+\.pdf$#i', $stored, $m)) { return $input; }

        try {
            $disk = collect(['local', 'public'])->first(fn ($d) => Storage::disk($d)->exists($m[0]));
            if (!$disk) { return $input; }
            $cv = (new CvParser())->parse((new CvTextExtractor())->extract(Storage::disk($disk)->path($m[0]), 'pdf'));
        } catch (\Throwable $e) {
            Log::info('Evaluation: the applicant\'s CV file could not be read: ' . $e->getMessage());
            return $input;
        }

        $found = [
            'education' => array_map(fn ($x) => ['level' => $x['highest_education'] ?? '', 'school' => $x['school_name'] ?? '', 'from' => $x['start_year'] ?? null, 'to' => $x['end_year'] ?? null], $cv['education']),
            'experience' => array_map(fn ($x) => ['company' => $x['company_name'] ?? '', 'position' => $x['position'] ?? '', 'description' => $x['job_description'] ?? '', 'until' => $x['date_completed'] ?? null], $cv['employment']),
            'skills' => array_column($cv['skills'], 'skills'),
            'certifications' => array_column($cv['certificate'], 'certificate_title'),
        ];
        foreach ($found as $section => $rows) {
            if (!$input['candidate'][$section] && $rows) {
                $input['candidate'][$section] = $rows;
                $input['from_resume'][] = $section;
            }
        }

        return $input;
    }

    /** the automatic run when someone applies: once per application, and never allowed to break anything */
    public static function evaluateNew(int $appliedId): void
    {
        try {
            $app = RecruitmentPipeline::load($appliedId);
            if ($app && $app->job && !DB::table('candidate_evaluations')->where('applied_id', $appliedId)->exists()) {
                self::evaluate($app, null);
            }
        } catch (\Throwable $e) {
            Log::error('Automatic candidate evaluation failed for application ' . $appliedId . ': ' . $e->getMessage());
        }
    }

    // ===================================================================== run an evaluation

    /** evaluate, store (history is kept) and return the stored evaluation */
    public static function evaluate(object $app, $user): array
    {
        $settings = self::settings($app->transNo);
        $input = self::buildInput($app, $settings);

        $provider = 'local';
        $model = 'rules-v1';
        $note = null;
        $raw = null;

        if (self::wantsExternal()) {
            try {
                [$raw, $model] = self::callAnthropic($input, $settings);
                $provider = 'anthropic';
            } catch (\Throwable $e) {
                Log::warning('AI evaluation provider failed, using the rules engine: ' . $e->getMessage());
                $note = 'The AI provider could not be reached, so the built-in rules engine was used.';
            }
        }
        if ($raw === null) {
            $raw = self::analyzeLocally($input);
        }

        $result = self::finalize($raw, $input, $settings);
        if ($input['from_resume']) { $note = trim($note . ' Taken from the uploaded CV because the profile has none: ' . implode(', ', $input['from_resume']) . '.'); }
        if ($note) { $result['explanation'] = $note . ' ' . $result['explanation']; }

        $id = DB::table('candidate_evaluations')->insertGetId([
            'applied_id' => $app->applied_id, 'transNo' => $app->transNo,
            'overall_score' => $result['overall_score'], 'recommendation' => $result['recommendation'],
            'category_scores' => json_encode($result['categories']),
            'strengths' => json_encode($result['strengths']), 'weaknesses' => json_encode($result['weaknesses']),
            'matched' => json_encode($result['matched']), 'missing' => json_encode($result['missing']),
            'answer_evals' => json_encode($result['answers']), 'explanation' => $result['explanation'],
            'interview_questions' => json_encode($result['interview_questions']),
            'provider' => $provider, 'model' => $model, 'version' => self::ENGINE_VERSION,
            'weights' => json_encode($settings['weights']),
            'criteria' => json_encode(['required_skills' => $input['job']['required_skills'], 'education' => $input['job']['education_requirement'],
                'competencies' => $settings['competencies'], 'passing_score' => $settings['passing_score'], 'scope' => $settings['scope']]),
            'thresholds' => json_encode($settings['thresholds']),
            'status' => 'ai', 'evaluated_by' => $user->code ?? null, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return self::format(DB::table('candidate_evaluations')->where('id', $id)->first());
    }

    private static function wantsExternal(): bool
    {
        $mode = config('recruitment.ai.provider', 'auto');
        if ($mode === 'local') { return false; }

        return (bool) config('recruitment.ai.anthropic.key');
    }

    // ===================================================================== Anthropic

    private const SYSTEM_PROMPT = <<<'TXT'
You are a careful recruiting analyst helping a human recruiter. You evaluate ONE candidate against ONE job.

RULES
- Use only the job requirements, the candidate's education, work history, skills, certifications, trainings and screening answers that you are given.
- NEVER consider or infer age, gender, race, ethnicity, nationality, religion, disability, health, marital or family status, sexual orientation, appearance, name, location of origin, or personality. If something in the data hints at one of these, ignore it completely.
- Judge the MEANING and relevance of answers and experience, not exact keywords. A different wording of the same skill counts as a match.
- Missing information is not evidence that the candidate lacks something. Say it is missing; do not score it as a failure.
- You only advise. You never decide to accept or reject anyone.
- Scores are integers 0-100. Evidence must quote or point to facts in the data you were given.

Reply with ONE JSON object and nothing else, with exactly these keys:
{
 "categories": { "skills": {"score":0,"evidence":""}, "experience": {...}, "education": {...}, "requirements": {...}, "certifications": {...} },
 "strengths": [""], "weaknesses": [""], "matched": [""], "missing": [""],
 "answers": [ {"question_id":0,"score":0,"status":"complete|incomplete|unclear|not_answered","explanation":"","follow_up":""} ],
 "interview_questions": [""],
 "explanation": ""
}
"requirements" covers the stated qualifications, competencies and screening answers.
Use null for a category's score when the job asks nothing of that kind.
TXT;

    private static function callAnthropic(array $input, array $settings): array
    {
        $cfg = config('recruitment.ai.anthropic');
        $res = Http::withHeaders(['x-api-key' => $cfg['key'], 'anthropic-version' => $cfg['version']])
            ->timeout((int) $cfg['timeout'])
            ->post($cfg['url'], [
                'model' => $cfg['model'], 'max_tokens' => 4000, 'system' => self::SYSTEM_PROMPT,
                'messages' => [['role' => 'user', 'content' => json_encode(['evaluate' => $input, 'weights' => $settings['weights']], JSON_UNESCAPED_UNICODE)]],
            ]);
        if (!$res->successful()) { throw new \RuntimeException('HTTP ' . $res->status()); }

        $text = collect($res->json('content', []))->where('type', 'text')->pluck('text')->implode('');
        if (!preg_match('/\{.*\}/s', $text, $m)) { throw new \RuntimeException('No JSON in the reply'); }
        $data = json_decode($m[0], true);
        if (!is_array($data) || !isset($data['categories'])) { throw new \RuntimeException('Unexpected reply'); }

        return [$data, $res->json('model', $cfg['model'])];
    }

    // ===================================================================== validate + score (applies to both engines)

    private static function clamp($v): ?int
    {
        return ($v === null || !is_numeric($v)) ? null : (int) max(0, min(100, round((float) $v)));
    }

    private static function strings($v, int $max = 12): array
    {
        return array_slice(array_values(array_filter(array_map(fn ($s) => mb_substr(trim((string) $s), 0, 300), is_array($v) ? $v : []), fn ($s) => $s !== '')), 0, $max);
    }

    /** never trust a provider's JSON: clamp scores, drop unknown questions, compute the weighted total ourselves */
    public static function finalize(array $raw, array $input, array $settings): array
    {
        $cand = $input['candidate'];
        $work = $cand['skills'] || $cand['experience'] || $cand['certifications'] || $cand['trainings'];
        $has = [
            'skills' => $work, 'experience' => (bool) $cand['experience'], 'education' => (bool) $cand['education'],
            'certifications' => (bool) $cand['certifications'],
            'requirements' => $work || $cand['education'] || collect($input['questions'])->contains(fn ($q) => trim($q['answer']) !== ''),
        ];

        $cats = [];
        foreach (self::CATEGORIES as $c) {
            $score = self::clamp($raw['categories'][$c]['score'] ?? null);
            $cats[$c] = ['score' => $score, 'evidence' => mb_substr(trim((string) ($raw['categories'][$c]['evidence'] ?? '')), 0, 600)];
            if (isset($raw['categories'][$c]['other'])) { $cats[$c]['other'] = self::clamp($raw['categories'][$c]['other']); }
            // missing information never counts as "unqualified": at least a neutral score, and the recruiter is told
            if ($score !== null && !$has[$c]) {
                $cats[$c]['score'] = max($score, 50);
                $cats[$c]['no_info'] = true;
                $cats[$c]['evidence'] = trim($cats[$c]['evidence'] . ' Not enough information: this is a neutral score to verify, not a finding that the candidate is unqualified.');
            }
        }

        $ids = collect($input['questions'])->pluck('id')->all();
        $texts = collect($input['questions'])->keyBy('id');
        $answers = [];
        foreach (($raw['answers'] ?? []) as $a) {
            $qid = (int) ($a['question_id'] ?? 0);
            if (!in_array($qid, $ids, true)) { continue; }
            $status = in_array($a['status'] ?? '', ['complete', 'incomplete', 'unclear', 'not_answered'], true) ? $a['status'] : 'complete';
            $answers[] = [
                'question_id' => $qid, 'question' => $texts[$qid]['text'], 'type' => $texts[$qid]['type'], 'answer' => $texts[$qid]['answer'],
                'score' => self::clamp($a['score'] ?? null) ?? 0, 'final_score' => null, 'overridden' => false, 'status' => $status,
                'explanation' => mb_substr(trim((string) ($a['explanation'] ?? '')), 0, 600),
                'follow_up' => mb_substr(trim((string) ($a['follow_up'] ?? '')), 0, 300),
            ];
        }

        $overall = self::weighted($cats, $settings['weights']);

        return [
            'categories' => $cats, 'overall_score' => $overall, 'recommendation' => self::recommend($overall),
            'strengths' => self::strings($raw['strengths'] ?? []), 'weaknesses' => self::strings($raw['weaknesses'] ?? []),
            'matched' => self::strings($raw['matched'] ?? [], 30), 'missing' => self::strings($raw['missing'] ?? [], 30),
            'answers' => $answers, 'interview_questions' => self::strings($raw['interview_questions'] ?? [], 8),
            'explanation' => mb_substr(trim((string) ($raw['explanation'] ?? '')), 0, 2000),
        ];
    }

    /** weights of the categories that apply (score not null), re-balanced to 100. Older stored runs have other categories. */
    public static function weighted(array $cats, array $weights): int
    {
        $sum = 0; $total = 0;
        foreach ($weights as $c => $w) {
            $s = $cats[$c]['score'] ?? null;
            if ($s === null || (float) $w <= 0) { continue; }
            $sum += $s * (float) $w; $total += (float) $w;
        }

        return $total > 0 ? (int) round($sum / $total) : 0;
    }

    public static function recommend(int $score): string
    {
        foreach (config('recruitment.levels') as $level => $from) {
            if ($score >= $from) { return $level; }
        }

        return 'low';
    }

    // ===================================================================== reading a stored evaluation

    public static function format(object $row): array
    {
        $j = fn ($v) => $v ? (json_decode($v, true) ?: []) : [];
        $level = self::recommend((int) ($row->final_score ?? $row->overall_score));      // older runs get today's levels too

        return [
            'id' => (int) $row->id, 'applied_id' => (int) $row->applied_id,
            'overall_score' => (int) $row->overall_score, 'final_score' => $row->final_score !== null ? (int) $row->final_score : null,
            'score' => (int) ($row->final_score ?? $row->overall_score),
            'recommendation' => $level, 'recommendation_label' => config('recruitment.recommendations')[$level],
            'incomplete' => array_keys(array_filter($j($row->category_scores), fn ($c) => !empty($c['no_info']))),
            'categories' => $j($row->category_scores), 'strengths' => $j($row->strengths), 'weaknesses' => $j($row->weaknesses),
            'matched' => $j($row->matched), 'missing' => $j($row->missing), 'answers' => $j($row->answer_evals),
            'explanation' => $row->explanation, 'interview_questions' => $j($row->interview_questions),
            'provider' => $row->provider, 'model' => $row->model, 'version' => $row->version,
            'weights' => $j($row->weights), 'criteria' => $j($row->criteria), 'thresholds' => $j($row->thresholds),
            'status' => $row->status, 'created_at' => $row->created_at, 'reviewed_at' => $row->reviewed_at,
            'human_review_required' => true,
        ];
    }

    /**
     * A recruiter changes AI scores (answers, or the overall result). Every change is logged with a reason.
     * $changes = [['question_id'=>12,'score'=>80,'reason'=>'...'], ['question_id'=>null,'score'=>65,'reason'=>'...']]
     */
    public static function applyOverrides(object $row, array $changes, $user): array
    {
        $answers = json_decode($row->answer_evals, true) ?: [];
        $cats = json_decode($row->category_scores, true) ?: [];
        $weights = json_decode($row->weights, true) ?: config('recruitment.default_weights');
        $finalOverall = $row->final_score !== null ? (int) $row->final_score : null;
        $overallOverride = null;

        DB::transaction(function () use (&$answers, $changes, $user, $row, &$overallOverride) {
            foreach ($changes as $c) {
                $new = self::clamp($c['score']);
                if ($new === null) { continue; }
                if ($c['question_id'] === null) {
                    DB::table('evaluation_overrides')->insert(['evaluation_id' => $row->id, 'applied_id' => $row->applied_id, 'question_id' => null,
                        'ai_score' => (int) $row->overall_score, 'new_score' => $new, 'reason' => $c['reason'],
                        'changed_by' => $user->code, 'changed_by_name' => $user->fullname, 'created_at' => now()]);
                    $overallOverride = $new;
                    continue;
                }
                foreach ($answers as &$a) {
                    if ((int) $a['question_id'] === (int) $c['question_id']) {
                        DB::table('evaluation_overrides')->insert(['evaluation_id' => $row->id, 'applied_id' => $row->applied_id, 'question_id' => $a['question_id'],
                            'ai_score' => (int) $a['score'], 'new_score' => $new, 'reason' => $c['reason'],
                            'changed_by' => $user->code, 'changed_by_name' => $user->fullname, 'created_at' => now()]);
                        $a['final_score'] = $new;
                        $a['overridden'] = true;
                    }
                }
                unset($a);
            }
        });

        // the category holding the answers follows the recruiter's scores; the total is recomputed from it
        if ($answers && collect($changes)->contains(fn ($c) => $c['question_id'] !== null)) {
            $applicable = array_filter($answers, fn ($a) => ($a['status'] ?? '') !== 'not_answered' || ($a['final_score'] ?? null) !== null);
            if ($applicable) {
                $avg = array_sum(array_map(fn ($a) => $a['final_score'] ?? $a['score'], $applicable)) / count($applicable);
                if (isset($cats['requirements'])) {
                    $other = $cats['requirements']['other'] ?? null;
                    $cats['requirements']['score'] = (int) round($other === null ? $avg : ($avg + $other) / 2);
                } else {
                    $cats['answers']['score'] = (int) round($avg);      // a run from before the five criteria
                }
            }
        }
        $recomputed = self::weighted($cats, $weights);
        $finalOverall = $overallOverride ?? $recomputed;

        DB::table('candidate_evaluations')->where('id', $row->id)->update([
            'answer_evals' => json_encode($answers), 'category_scores' => json_encode($cats),
            'final_score' => $finalOverall, 'recommendation' => self::recommend($finalOverall),
            'status' => 'reviewed', 'reviewed_by' => $user->code, 'reviewed_at' => now(), 'updated_at' => now(),
        ]);

        return self::format(DB::table('candidate_evaluations')->where('id', $row->id)->first());
    }

    // ===================================================================== built-in rules engine

    private const STOP = ['a','an','the','and','or','of','to','in','on','for','with','at','by','from','as','is','are','was','were','be','been','it','this','that','these','those','i','we','you','they','my','our','your','their','have','has','had','will','would','can','could','should','do','does','did','not','no','yes','but','if','so','than','then','also','all','any','more','most','such','into','about','over','per','etc','using','used','work','working','experience','years','year','ability','strong','good','knowledge'];

    /** words that mean the same thing share one canonical token */
    private const SYN = [
        'javascript' => ['js', 'ecmascript'], 'typescript' => ['ts'], 'angular' => ['angularjs'], 'react' => ['reactjs'], 'node' => ['nodejs'],
        'vue' => ['vuejs'], 'database' => ['db', 'sql', 'mysql', 'postgres', 'postgresql', 'mariadb', 'sqlite', 'rdbms'],
        'frontend' => ['front', 'ui', 'client-side'], 'backend' => ['back', 'server-side', 'api'], 'laravel' => ['php'],
        'customer' => ['client', 'customers'], 'sales' => ['selling'], 'manage' => ['lead', 'supervise', 'oversee', 'head'],
        'communicate' => ['communication', 'presenting', 'presentation'], 'team' => ['collaborate', 'collaboration', 'cooperate'],
        'analyze' => ['analysis', 'analytics', 'analyst'], 'design' => ['designer', 'designing'], 'test' => ['testing', 'qa', 'quality'],
        'account' => ['accounting', 'bookkeeping'], 'market' => ['marketing'], 'teach' => ['teacher', 'teaching', 'instructor'],
    ];

    private static array $synMap = [];

    private static function canon(string $w): string
    {
        if (!self::$synMap) {
            foreach (self::SYN as $root => $alts) { self::$synMap[$root] = $root; foreach ($alts as $a) { self::$synMap[$a] = $root; } }
        }
        if (isset(self::$synMap[$w])) { return self::$synMap[$w]; }
        foreach (['ing', 'ed', 'es', 's', 'ly', 'er'] as $suf) {
            if (strlen($w) > strlen($suf) + 3 && str_ends_with($w, $suf)) { $w = substr($w, 0, -strlen($suf)); break; }
        }

        return self::$synMap[$w] ?? $w;
    }

    /** canonical content words of a text */
    private static function tokens(?string $text): array
    {
        $text = mb_strtolower((string) $text);
        $text = str_replace(['node.js', 'vue.js', 'react.js', 'c#', 'c++', '.net'], ['nodejs', 'vuejs', 'reactjs', 'csharp', 'cplusplus', 'dotnet'], $text);
        $words = preg_split('/[^a-z0-9]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        foreach ($words as $w) {
            if (strlen($w) < 2 || in_array($w, self::STOP, true)) { continue; }
            $out[self::canon($w)] = true;
        }

        return array_keys($out);
    }

    private static function overlap(array $needle, array $haystack): float
    {
        if (!$needle) { return 0.0; }
        $h = array_flip($haystack);

        return count(array_filter($needle, fn ($t) => isset($h[$t]))) / count($needle);
    }

    private static function eduLevel(?string $text): int
    {
        $t = mb_strtolower((string) $text);
        return match (true) {
            (bool) preg_match('/ph\.?d|doctor/', $t) => 5,
            (bool) preg_match('/master|msc|m\.s\b|mba|postgrad/', $t) => 4,
            (bool) preg_match('/bachelor|bsc|b\.s\b|undergrad|college|degree|licen[sc]ed/', $t) => 3,
            (bool) preg_match('/associate|diploma|vocational|technical|certificate course|tesda/', $t) => 2,
            (bool) preg_match('/high school|secondary|senior high|k-12|k12/', $t) => 1,
            default => 0,
        };
    }

    private static function eduName(int $l): string
    {
        return [0 => 'no stated level', 1 => 'high school', 2 => 'diploma / vocational', 3 => "bachelor's degree", 4 => "master's degree", 5 => 'doctorate'][$l];
    }

    public static function analyzeLocally(array $in): array
    {
        $job = $in['job'];
        $cand = $in['candidate'];

        // --- text pools
        $jobPool = self::tokens(implode(' ', array_filter([$job['title'], $job['department'], $job['description'], $job['summary'], $job['qualifications'], $job['preferred_qualifications'], implode(' ', $job['required_skills'] ?? [])])));
        $expText = implode(' ', array_map(fn ($e) => ($e['position'] ?? '') . ' ' . ($e['description'] ?? ''), $cand['experience']));
        $answerText = implode(' ', array_map(fn ($q) => $q['answer'], $in['questions']));
        $candPool = self::tokens(implode(' ', array_merge($cand['skills'], $cand['certifications'], $cand['trainings'], [$expText])));
        $everything = array_merge($candPool, self::tokens($answerText));

        $strengths = []; $weaknesses = []; $matched = []; $missing = []; $iq = [];
        $cats = [];

        // --- education
        $req = self::eduLevel($job['education_requirement'] ?? '');
        $have = 0; $haveText = '';
        foreach ($cand['education'] as $e) { $l = self::eduLevel($e['level'] ?? ''); if ($l >= $have) { $have = $l; $haveText = trim(($e['level'] ?? '') . ($e['school'] ? ' – ' . $e['school'] : '')); } }
        if (!$cand['education']) {
            $cats['education'] = ['score' => $req ? 50 : null, 'evidence' => 'No education was listed on the candidate\'s CV.'];
            if ($req) { $missing[] = 'Not provided: education'; }
        } elseif ($req === 0) {
            $cats['education'] = ['score' => null, 'evidence' => 'The job states no specific education requirement. Candidate lists: ' . $haveText . '.'];
        } elseif ($have >= $req) {
            $cats['education'] = ['score' => 100, 'evidence' => 'Requirement: ' . self::eduName($req) . '. Candidate lists ' . $haveText . '.'];
            $matched[] = 'Education: meets ' . self::eduName($req) . ' requirement';
        } else {
            $cats['education'] = ['score' => max(10, 100 - 35 * ($req - $have)), 'evidence' => 'Requirement: ' . self::eduName($req) . '. Highest listed: ' . ($haveText ?: self::eduName($have)) . '.'];
            $missing[] = 'Education: ' . self::eduName($req) . ' required';
            $weaknesses[] = 'Education is below the stated requirement (' . self::eduName($req) . ').';
        }

        // --- skills
        $required = array_values(array_filter(array_map('trim', $job['required_skills'] ?? [])));
        if ($required) {
            $score = 0.0;
            foreach ($required as $skill) {
                $t = self::tokens($skill);
                $ov = self::overlap($t, $everything);
                if ($ov >= 0.999) { $score += 1; $matched[] = "Skill: {$skill}"; }
                elseif ($ov >= 0.5) { $score += 0.5; $matched[] = "Skill (partial): {$skill}"; }
                else { $missing[] = "Skill: {$skill}"; $iq[] = "You didn't mention {$skill}. What experience do you have with it, or how quickly could you learn it?"; }
            }
            $pct = (int) round($score / count($required) * 100);
            $cats['skills'] = ['score' => $pct, 'evidence' => 'Matched ' . count(array_filter($matched, fn ($m) => str_starts_with($m, 'Skill'))) . ' of ' . count($required) . ' required skills against the CV skills, work history, certifications and answers.'];
            if ($pct >= 75) { $strengths[] = 'Covers most of the required skills.'; }
            if ($pct < 50) { $weaknesses[] = 'Many required skills are not evident on the CV.'; }
        } else {
            $cats['skills'] = ['score' => null, 'evidence' => 'The job lists no required skills.'];
        }

        // --- experience (the CV has no start dates, so relevance and number of roles are used, not years)
        $roles = $cand['experience'];
        $relevant = 0; $best = null;
        foreach ($roles as $r) {
            $ov = self::overlap(self::tokens(($r['position'] ?? '') . ' ' . ($r['description'] ?? '')), $jobPool);
            if ($ov >= 0.2) { $relevant++; if ($best === null) { $best = $r; } }
        }
        $needed = match (mb_strtolower((string) ($job['experience_level'] ?? ''))) { 'entry', 'entry level', 'junior' => 0, 'mid', 'mid level', 'intermediate' => 1, 'senior', 'lead', 'manager', 'executive' => 2, default => 1 };
        if (!$roles) {
            $cats['experience'] = ['score' => 50, 'evidence' => 'No work history was listed on the CV.'];
            $missing[] = 'Not provided: work history';
        } else {
            $score = 25 + 30 * min($relevant, 2) + 10 * min(count($roles) - $relevant, 1) + ($relevant >= $needed ? 15 : 0);
            $cats['experience'] = ['score' => min(100, $score), 'evidence' => "{$relevant} of " . count($roles) . ' listed roles relate to this job' . ($best ? ' (e.g. ' . ($best['position'] ?? 'a related role') . ')' : '') . '. Years of experience cannot be measured because the CV stores no start dates.'];
            if ($relevant >= max(1, $needed)) { $strengths[] = 'Has directly relevant work experience' . ($best ? ' as ' . ($best['position'] ?? 'a related role') : '') . '.'; $matched[] = 'Relevant work experience'; }
            else { $weaknesses[] = 'Little directly relevant work experience is evident.'; $missing[] = 'Relevant work experience'; }
        }

        // --- screening answers
        $answers = [];
        $answerScore = null;
        foreach ($in['questions'] as $q) {
            $a = trim($q['answer']);
            $row = ['question_id' => $q['id'], 'score' => 0, 'status' => 'complete', 'explanation' => '', 'follow_up' => ''];
            if ($a === '') {
                $row['status'] = 'not_answered';
                $row['score'] = 0;
                $row['explanation'] = $q['required'] ? 'This required question was not answered.' : 'This optional question was not answered.';
                $row['follow_up'] = 'Ask about: ' . $q['text'];
            } elseif ($q['expected_answer'] !== null) {
                $ok = match ($q['type']) { 'numeric' => is_numeric($a) && (float) $a == (float) $q['expected_answer'], default => mb_strtolower($a) === mb_strtolower((string) $q['expected_answer']) };
                $row['score'] = $ok ? 100 : 0;
                $row['explanation'] = $ok ? 'Matches the answer the recruiter marked as correct.' : "Does not match the expected answer ('{$q['expected_answer']}').";
                if (!$ok) { $row['follow_up'] = 'Clarify: ' . $q['text']; }
            } elseif (in_array($q['type'], ['short_text', 'long_text'], true)) {
                $words = preg_split('/\s+/', $a, -1, PREG_SPLIT_NO_EMPTY);
                $ctx = array_merge($jobPool, self::tokens($q['text']));
                $content = self::tokens($a);
                $rel = $content ? self::overlap($content, $ctx) : 0;
                $hits = count(array_intersect($content, $ctx));
                $row['score'] = (int) max(0, min(100, round(25 + 45 * min(1, $hits / 4) + 30 * min(1, count($words) / 40))));
                if (count($words) < 5) { $row['status'] = 'incomplete'; $row['score'] = min($row['score'], 35); $row['explanation'] = 'Very short: not enough detail to judge.'; $row['follow_up'] = 'Could you expand on: ' . $q['text']; }
                elseif ($hits === 0) { $row['status'] = 'unclear'; $row['score'] = min($row['score'], 45); $row['explanation'] = 'The answer does not clearly relate to the role or the question.'; $row['follow_up'] = 'How does your answer relate to ' . $job['title'] . '?'; }
                else { $row['explanation'] = 'Relevant to the role (' . $hits . ' related term' . ($hits > 1 ? 's' : '') . ', ' . count($words) . ' words)' . ($rel < 0.2 ? ', but fairly general.' : '.'); }
            } else {
                $row['score'] = 70;
                $row['explanation'] = 'Answered. The recruiter set no correct answer for this question, so it is scored neutrally.';
            }
            $answers[] = $row;
        }
        $applicable = array_filter($answers, fn ($r) => !($r['status'] === 'not_answered' && !collect($in['questions'])->firstWhere('id', $r['question_id'])['required']));
        if ($answers && $applicable) {
            $avg = (int) round(array_sum(array_column($applicable, 'score')) / count($applicable));
            $answerScore = $avg;
            foreach ($answers as $r) {
                if ($r['score'] >= 80) { $strengths[] = 'Strong answer: ' . mb_substr($in['questions'][array_search($r['question_id'], array_column($in['questions'], 'id'))]['text'], 0, 80); }
                if ($r['follow_up'] !== '') { $iq[] = $r['follow_up']; }
            }
            if ($avg < 50) { $weaknesses[] = 'Screening answers are weak, incomplete or unclear.'; }
        }

        // --- job requirements: stated qualifications and competencies, together with the screening answers
        $certWords = self::tokens('certification certificate certified license licensed licensure required preferred must have valid');
        $lines = array_values(array_filter(array_map(fn ($l) => trim($l, " \t-*•."),
            preg_split('/[\r\n;•]+|(?<=\.)\s+/u', strip_tags((string) ($job['qualifications'] ?? '')))), fn ($l) => mb_strlen($l) > 2));
        $certLines = array_values(array_filter($lines, fn ($l) => preg_match('/certifi|licen[sc]/i', $l)));
        $wanted = array_merge(array_values(array_diff($lines, $certLines)), $job['competencies'] ?? []);
        $pool = array_merge($everything, self::tokens(implode(' ', array_column($cand['education'], 'level'))));
        $hit = 0;
        foreach ($wanted as $w) {
            if (self::overlap(self::tokens($w), $pool) >= 0.5) { $hit++; $matched[] = 'Requirement: ' . mb_substr($w, 0, 120); }
            else { $missing[] = 'Requirement (verify): ' . mb_substr($w, 0, 120); $iq[] = 'Give an example that shows: ' . mb_substr($w, 0, 160); }
        }
        $other = $wanted ? (int) round($hit / count($wanted) * 100) : null;
        $parts = array_values(array_filter([$other, $answerScore], fn ($v) => $v !== null));
        $cats['requirements'] = [
            'score' => $parts ? (int) round(array_sum($parts) / count($parts)) : null, 'other' => $other,
            'evidence' => $parts
                ? trim(($wanted ? "{$hit} of " . count($wanted) . ' stated requirements are evident. ' : '') . ($answerScore !== null ? "Screening answers scored {$answerScore}." : ''))
                : 'The job states no further requirements or screening questions.',
        ];

        // --- certifications and licenses
        $held = array_values(array_filter(array_map('trim', array_map('strval', $cand['certifications']))));
        $heldPool = self::tokens(implode(' ', array_merge($held, $cand['trainings'])));
        if ($certLines) {
            $hit = 0;
            foreach ($certLines as $w) {
                if (self::overlap(array_values(array_diff(self::tokens($w), $certWords)), $heldPool) >= 0.5) { $hit++; $matched[] = 'Certification: ' . mb_substr($w, 0, 120); }
                else { $missing[] = ($held ? 'Certification: ' : 'Not provided: ') . mb_substr($w, 0, 120); }
            }
            $cats['certifications'] = ['score' => (int) round($hit / count($certLines) * 100), 'evidence' => "{$hit} of " . count($certLines) . ' required certifications or licenses are listed.'];
        } else {
            $relevantCerts = array_values(array_filter($held, fn ($c) => array_intersect(array_diff(self::tokens($c), $certWords), $jobPool)));
            $cats['certifications'] = $relevantCerts
                ? ['score' => 100, 'evidence' => 'The job requires none; related certification listed: ' . implode(', ', array_slice($relevantCerts, 0, 3)) . '.']
                : ['score' => null, 'evidence' => 'The job requires no certification, so this is not counted.'];
        }

        if ($best) { $iq[] = 'Walk us through your role as ' . ($best['position'] ?? 'your most relevant position') . ' and what you achieved.'; }
        $iq[] = 'What interests you about this role, and what would you want to learn in the first 90 days?';

        $explanation = 'Rules-based review of the candidate\'s education, work history, skills and answers against this job. '
            . implode(', ', array_map(fn ($c) => $c . ' ' . ($cats[$c]['score'] ?? 'n/a'), self::CATEGORIES)) . '. '
            . 'Matching uses meaning-aware word groups (synonyms and word forms) rather than exact keywords, but it cannot read between the lines like a person: please review the evidence below.';

        return [
            'categories' => $cats, 'strengths' => array_values(array_unique($strengths)), 'weaknesses' => array_values(array_unique($weaknesses)),
            'matched' => $matched, 'missing' => $missing, 'answers' => $answers,
            'interview_questions' => array_slice(array_values(array_unique($iq)), 0, 8), 'explanation' => $explanation,
        ];
    }
}
