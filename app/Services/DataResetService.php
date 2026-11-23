<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * "Reset all data": removes every account that is NOT protected (DEF-ADMIN / DEF-MASTERADMIN) together with everything
 * those accounts created. Protected accounts and everything they own are never selected.
 *
 * Safe by construction:
 *  - It only touches the tables listed in steps(). Every other table (menus, roles, plans, features, counters,
 *    migrations ...) is kept without being mentioned.
 *  - Every step is scoped to the accounts being removed (their code / id / email, or content hanging off their
 *    posts, jobs and applications), so a protected account's rows cannot match.
 *  - The id sets are computed once up front, so the deletes do not depend on the order they run in.
 *  - The controller takes a backup before calling purge().
 */
class DataResetService
{
    /** @return array{codes:string[],ids:int[],emails:string[]} accounts that would be removed */
    public function doomedUsers(): array
    {
        $rows = DB::table('users')->where(fn ($q) => $q->whereNull('role_code')
            ->orWhereNotIn('role_code', config('databaseadmin.protected_roles')))->get(['id', 'code', 'email', 'contactno']);

        return [
            'codes' => $rows->pluck('code')->map(fn ($c) => (string) $c)->all(),
            'ids' => $rows->pluck('id')->map(fn ($i) => (int) $i)->all(),
            'emails' => $rows->pluck('email')->filter()->values()->all(),
            'phones' => $rows->pluck('contactno')->filter()->values()->all(),
        ];
    }

    public function protectedUsers(): array
    {
        return DB::table('users')->whereIn('role_code', config('databaseadmin.protected_roles'))
            ->orderBy('role_code')->orderBy('fullname')->get(['code', 'fullname', 'email', 'role_code'])->all();
    }

    /**
     * The reset plan: one entry per table, in the order they are deleted (children before parents, users last).
     * @return array<int, array{group:string,label:string,table:string,scope:callable}>
     */
    public function steps(?array $doomed = null): array
    {
        $d = $doomed ?? $this->doomedUsers();
        $codes = $d['codes']; $ids = $d['ids']; $emails = $d['emails']; $phones = $d['phones'] ?? [];

        // content that hangs off the doomed accounts' posts / jobs / applications
        $postRows = $this->pluckRows('posts', fn ($q) => $q->whereIn('code', $codes), ['id', 'posts_uuid']);
        $postUuids = array_column($postRows, 'posts_uuid'); $postIds = array_column($postRows, 'id');

        $commentUuids = array_column($this->pluckRows('commentposts',
            fn ($q) => $q->whereIn('code', $codes)->orWhereIn('post_uuidOrUind', $postUuids), ['comment_uuid']), 'comment_uuid');
        $replyUuids = array_column($this->pluckRows('commentreplies',
            fn ($q) => $q->whereIn('code', $codes)->orWhereIn('comment_uuid', $commentUuids), ['comment_uuid']), 'comment_uuid');
        $reactionTargets = array_values(array_unique(array_merge($postUuids, $commentUuids, $replyUuids)));

        $jobRows = $this->pluckRows('jobposting', fn ($q) => $q->whereIn('code', $codes), ['job_id', 'transNo']);
        $jobIds = array_column($jobRows, 'job_id'); $jobTrans = array_column($jobRows, 'transNo');

        $appliedIds = array_column($this->pluckRows('applied_jobs',
            fn ($q) => $q->whereIn('code', $codes)->orWhereIn('transNo', $jobTrans), ['applied_id']), 'applied_id');
        $evalIds = array_column($this->pluckRows('candidate_evaluations', fn ($q) => $q->whereIn('applied_id', $appliedIds), ['id']), 'id');

        $S = fn (string $group, string $label, string $table, callable $scope) => compact('group', 'label', 'table', 'scope');

        return [
            // --- jobs & applications
            $S('Jobs & applications', 'Evaluation overrides', 'evaluation_overrides', fn ($q) => $q->whereIn('applied_id', $appliedIds)->orWhereIn('evaluation_id', $evalIds)),
            $S('Jobs & applications', 'Candidate evaluations', 'candidate_evaluations', fn ($q) => $q->whereIn('applied_id', $appliedIds)),
            $S('Jobs & applications', 'Candidate notes', 'candidate_notes', fn ($q) => $q->whereIn('applied_id', $appliedIds)),
            $S('Jobs & applications', 'Interviews', 'interviews', fn ($q) => $q->whereIn('applied_id', $appliedIds)),
            $S('Jobs & applications', 'Application status history', 'application_status_history', fn ($q) => $q->whereIn('applied_id', $appliedIds)),
            $S('Jobs & applications', 'Application answers', 'applied_answers', fn ($q) => $q->whereIn('code', $codes)->orWhereIn('transNo', $jobTrans)),
            $S('Jobs & applications', 'Application questions', 'applied_questions', fn ($q) => $q->whereIn('code', $codes)->orWhereIn('transNo', $jobTrans)),
            $S('Jobs & applications', 'Application resumes', 'applied_resumes', fn ($q) => $q->whereIn('code', $codes)->orWhereIn('transNo', $jobTrans)),
            $S('Jobs & applications', 'Job applications', 'applied_jobs', fn ($q) => $q->whereIn('applied_id', $appliedIds)),
            $S('Jobs & applications', 'Saved jobs', 'saved_jobs', fn ($q) => $q->whereIn('code', $codes)->orWhereIn('job_id', $jobIds)),
            $S('Jobs & applications', 'Screening settings', 'evaluation_settings', fn ($q) => $q->whereIn('transNo', $jobTrans)),
            $S('Jobs & applications', 'Job postings', 'jobposting', fn ($q) => $q->whereIn('code', $codes)),

            // --- feed
            $S('Posts & feed', 'Comment reactions', 'commentreactions', fn ($q) => $q->whereIn('code', $codes)->orWhereIn('target_ref', $reactionTargets)),
            $S('Posts & feed', 'Comment replies', 'commentreplies', fn ($q) => $q->whereIn('code', $codes)->orWhereIn('comment_uuid', $commentUuids)),
            $S('Posts & feed', 'Comments', 'commentposts', fn ($q) => $q->whereIn('code', $codes)->orWhereIn('post_uuidOrUind', $postUuids)),
            $S('Posts & feed', 'Post reactions', 'reactionpost', fn ($q) => $q->whereIn('code', $codes)->orWhereIn('post_id', $postIds)),
            $S('Posts & feed', 'Reactions', 'reactions', fn ($q) => $q->whereIn('code', $codes)->orWhereIn('post_uuidOrUind', $postUuids)),
            $S('Posts & feed', 'Post attachments', 'attachmentposts', fn ($q) => $q->whereIn('code', $codes)->orWhereIn('posts_uuid', $postUuids)),
            $S('Posts & feed', 'Posts', 'posts', fn ($q) => $q->whereIn('code', $codes)),

            // --- messaging & network
            $S('Messaging & network', 'Messages', 'messages', fn ($q) => $q->whereIn('sender_id', $ids)->orWhereIn('receiver_id', $ids)),
            $S('Messaging & network', 'Follows and connections', 'follows', fn ($q) => $q->whereIn('follower_code', $codes)->orWhereIn('following_code', $codes)),
            $S('Messaging & network', 'Blocked users', 'user_blocks', fn ($q) => $q->whereIn('blocker_code', $codes)->orWhereIn('blocked_code', $codes)),
            $S('Messaging & network', 'Profile views', 'user_activity', fn ($q) => $q->whereIn('viewer_code', $codes)->orWhereIn('viewed_code', $codes)),
            $S('Messaging & network', 'Notification settings', 'user_notification_settings', fn ($q) => $q->whereIn('user_id', $ids)),

            // --- profile & CV
            $S('Profiles & CV', 'Profiles', 'userprofiles', fn ($q) => $q->whereIn('code', $codes)),
            $S('Profiles & CV', 'Languages', 'usercapabilities', fn ($q) => $q->whereIn('code', $codes)),
            $S('Profiles & CV', 'Certificates', 'usercertificates', fn ($q) => $q->whereIn('code', $codes)),
            $S('Profiles & CV', 'Education', 'usereducations', fn ($q) => $q->whereIn('code', $codes)),
            $S('Profiles & CV', 'Employment records', 'useremploymentrecords', fn ($q) => $q->whereIn('code', $codes)),
            $S('Profiles & CV', 'Seminars', 'userseminars', fn ($q) => $q->whereIn('code', $codes)),
            $S('Profiles & CV', 'Skills', 'userskills', fn ($q) => $q->whereIn('code', $codes)),
            $S('Profiles & CV', 'Trainings', 'usertrainings', fn ($q) => $q->whereIn('code', $codes)),
            $S('Profiles & CV', 'Resource profiles', 'resources', fn ($q) => $q->whereIn('code', $codes)),

            // --- subscriptions
            $S('Subscriptions', 'Subscription features', 'usersubscription_details', fn ($q) => $q->whereIn('code', $codes)),
            $S('Subscriptions', 'Subscriptions', 'usersubscription', fn ($q) => $q->whereIn('code', $codes)),

            // --- accounts & sessions
            $S('Accounts & sign-in', 'Sign-in tokens', 'personal_access_tokens', fn ($q) => $q->where('tokenable_type', 'App\\Models\\User')->whereIn('tokenable_id', $ids)),
            $S('Accounts & sign-in', 'Email verification codes', 'email_codes', fn ($q) => $q->whereIn('email', $emails)),
            $S('Accounts & sign-in', 'Password reset tokens', 'password_reset_tokens', fn ($q) => $q->whereIn('email', $emails)),
            $S('Accounts & sign-in', 'Password resets (legacy)', 'password_resets', fn ($q) => $q->whereIn('phone', $phones)),
            $S('Accounts & sign-in', 'User accounts', 'users', fn ($q) => $q->whereIn('id', $ids)),
        ];
    }

    // ------------------------------------------------------- one account only

    /** The "accounts being removed" set for a single account. Same shape the full reset uses. */
    public function doomedForUser(object $u): array
    {
        return [
            'codes' => [(string) $u->code],
            'ids' => [(int) $u->id],
            'emails' => $u->email ? [$u->email] : [],
            'phones' => $u->contactno ? [$u->contactno] : [],
        ];
    }

    /** Why this account cannot be deleted from the Users page, or null when it can. */
    public function deleteBlocker(object $target, $actor): ?string
    {
        if ((string) $target->code === (string) ($actor->code ?? '')) {
            return 'You can\'t delete your own account.';
        }
        if (in_array($target->role_code, config('databaseadmin.protected_roles'), true)) {
            return 'Admin and master admin accounts can\'t be deleted here.';
        }
        return null;
    }

    /**
     * Everything that would go with one account, grouped, plus what it does to OTHER people's data
     * (a recruiter's jobs take the applications other users made to them along).
     */
    public function previewForUser(object $u): array
    {
        $doomed = $this->doomedForUser($u);
        $steps = []; $total = 0;
        foreach ($this->steps($doomed) as $s) {
            if (!$this->tableExists($s['table'])) { continue; }
            $n = (int) $s['scope'](DB::table($s['table']))->count();
            $total += $n;
            $steps[] = ['group' => $s['group'], 'label' => $s['label'], 'table' => $s['table'], 'rows' => $n];
        }

        $jobTrans = $this->tableExists('jobposting') ? DB::table('jobposting')->where('code', (string) $u->code)->pluck('transNo')->all() : [];
        $others = $this->tableExists('applied_jobs')
            ? (int) DB::table('applied_jobs')->whereIn('transNo', $jobTrans)->where('code', '!=', (string) $u->code)->count() : 0;

        return [
            'rows_removed' => $total,
            'steps' => $steps,
            'others_applications' => $others,
            'jobs' => count($jobTrans),
            'files' => $this->fileTargets($doomed['codes'])['count'],
        ];
    }

    /** What a reset would remove, without removing anything. */
    public function preview(): array
    {
        $out = []; $total = 0;
        foreach ($this->steps() as $s) {
            if (!$this->tableExists($s['table'])) { continue; }
            $n = (int) $s['scope'](DB::table($s['table']))->count();
            $total += $n;
            $out[] = ['group' => $s['group'], 'label' => $s['label'], 'table' => $s['table'], 'rows' => $n];
        }
        $doomed = $this->doomedUsers();

        return [
            'accounts_removed' => count($doomed['ids']),
            'rows_removed' => $total,
            'steps' => $out,
            'protected' => $this->protectedUsers(),
            'kept_tables' => $this->keptTables(array_column($out, 'table')),
            'files' => $this->fileTargets($doomed['codes'])['count'],
        ];
    }

    /** Tables the reset never touches (everything not in the plan), with their row counts. */
    public function keptTables(array $planned): array
    {
        $planned = array_flip($planned);
        $rows = DB::select('select table_name as name, table_rows as table_rows from information_schema.tables
            where table_schema = database() and table_type = "BASE TABLE" order by table_name');
        $out = [];
        foreach ($rows as $r) {
            if (!isset($planned[$r->name])) { $out[] = ['table' => $r->name, 'rows' => (int) $r->table_rows]; }
        }
        return $out;
    }

    /**
     * Delete everything the plan selects. InnoDB tables run in one transaction (all or nothing); MyISAM tables cannot
     * roll back, so they run afterwards. The caller must already have taken a backup.
     * @return array{deleted:array<int,array>,rows:int,files:int}
     */
    public function purge(bool $deleteFiles = false, ?array $doomed = null): array
    {
        $doomed = $doomed ?? $this->doomedUsers();
        $steps = array_values(array_filter($this->steps($doomed), fn ($s) => $this->tableExists($s['table'])));

        $engines = collect(DB::select('select table_name as name, engine as engine from information_schema.tables where table_schema = database()'))
            ->pluck('engine', 'name');
        $transactional = array_values(array_filter($steps, fn ($s) => strtoupper((string) $engines[$s['table']]) === 'INNODB'));
        $other = array_values(array_filter($steps, fn ($s) => strtoupper((string) $engines[$s['table']]) !== 'INNODB'));

        $deleted = [];
        $run = function (array $list) use (&$deleted) {
            foreach ($list as $s) {
                $n = (int) $s['scope'](DB::table($s['table']))->delete();
                $deleted[] = ['group' => $s['group'], 'label' => $s['label'], 'table' => $s['table'], 'rows' => $n];
            }
        };

        // the children come before the users row in $steps, and the users row is last, so keep that order inside each list
        DB::transaction(fn () => $run($transactional));
        $run($other);

        $files = $deleteFiles ? $this->deleteFiles($doomed['codes']) : 0;
        PlanService::bust();

        return ['deleted' => $deleted, 'rows' => array_sum(array_column($deleted, 'rows')), 'files' => $files];
    }

    // ------------------------------------------------------------------- files

    /** Upload folders named exactly after a removed account's numeric code. */
    public function fileTargets(array $codes): array
    {
        $roots = [storage_path('app/public/uploads'), storage_path('app/uploads')];
        $dirs = [];
        foreach ($roots as $root) {
            if (!is_dir($root)) { continue; }
            foreach ($codes as $c) {
                if (!preg_match('/^\d+$/', (string) $c)) { continue; }   // never build a path from anything but digits
                $p = $root . DIRECTORY_SEPARATOR . $c;
                if (is_dir($p) && str_starts_with(realpath($p), realpath($root) . DIRECTORY_SEPARATOR)) { $dirs[] = $p; }
            }
        }
        return ['dirs' => $dirs, 'count' => count($dirs)];
    }

    private function deleteFiles(array $codes): int
    {
        $n = 0;
        foreach ($this->fileTargets($codes)['dirs'] as $dir) {
            if (\Illuminate\Support\Facades\File::deleteDirectory($dir)) { $n++; }
        }
        return $n;
    }

    // ----------------------------------------------------------------- helpers

    private function tableExists(string $t): bool
    {
        static $cache = null;
        $cache ??= array_flip(array_map(fn ($r) => $r->name, DB::select('select table_name as name from information_schema.tables where table_schema = database()')));
        return isset($cache[$t]);
    }

    /** @return array<int,array<string,mixed>> */
    private function pluckRows(string $table, callable $scope, array $cols): array
    {
        if (!$this->tableExists($table)) { return []; }
        return $scope(DB::table($table))->get($cols)->map(fn ($r) => (array) $r)->all();
    }
}
