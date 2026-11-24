<?php

namespace App\Services;

use App\Jobs\DeleteUserFilesJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Deletes one account and everything it owns. The single entry point for "Delete user" (master admin, Users page).
 *
 *  - Database: reuses the ownership-scoped plan in DataResetService (every step is limited to this account's code / id /
 *    email, or to content hanging off its own posts, jobs and applications). Nothing global is listed, so plans, feature
 *    definitions, menus, roles and settings are kept.
 *  - Financial records: subscription rows are NOT deleted. They are anonymised (the user code becomes DELETED-<code>),
 *    so revenue and audit history stay intact without pointing at a person. Their feature assignments are deleted.
 *  - Files: folders named after this account's code / id on the local, public and any cloud (S3 / R2) disk are removed
 *    by DeleteUserFilesJob, queued so a large account cannot time the request out.
 *  - Audit: one user_deletion_audits row records who, whom, when and the result, and survives the deletion.
 */
class UserDeletionService
{
    public const ANON_PREFIX = 'DELETED-';

    public function __construct(private DataResetService $reset) {}

    /**
     * Why this account cannot be deleted by this actor, or null. Only a master admin may delete (the route's middleware
     * and this check both enforce it). Admins and other master admins are deletable; the actor's own account and the
     * last active master admin are not.
     */
    public function blocker(object $target, $actor): ?string
    {
        if (($actor->role_code ?? null) !== 'DEF-MASTERADMIN') {
            return 'Only a master admin can delete accounts.';
        }
        if ((string) $target->code === (string) ($actor->code ?? '')) {
            return 'You can\'t delete your own account.';
        }
        if ($target->role_code === 'DEF-MASTERADMIN') {
            $others = DB::table('users')->where('role_code', 'DEF-MASTERADMIN')->where('status', 'A')->where('id', '!=', $target->id)->count();
            if ($others < 1) { return 'The last active master admin can\'t be deleted.'; }
        }
        return null;
    }

    /** What would go, for the confirmation dialog. Subscriptions are shown as kept (anonymised), not as removed. */
    public function preview(object $target): array
    {
        $p = $this->reset->previewForUser($target);
        $kept = 0;
        foreach ($p['steps'] as &$s) {
            if ($s['table'] === 'usersubscription') {
                $kept = $s['rows'];
                $s['label'] = 'Subscriptions (kept anonymised for records)';
                $s['group'] = 'Kept for records';
            }
        }
        unset($s);
        $p['rows_removed'] -= $kept;
        $p['rows_anonymized'] = $kept;
        $p['files'] = count($this->fileTargets($target));

        return $p;
    }

    /**
     * Folders this account owns, per disk. Only digits ever go into a path.
     * @return array<int,array{disk:string,path:string}>
     */
    public function fileTargets(object $target): array
    {
        $code = (string) $target->code; $id = (string) $target->id;
        $paths = [];
        if (ctype_digit($code)) { array_push($paths, "uploads/$code", "uploads/posts/$code", "uploads/comments/$code", "uploads/announcements/$code"); }
        if (ctype_digit($id)) { $paths[] = "uploads/$id/cover_photo"; }   // cover photos are filed under the row id

        $out = [];
        foreach ($this->disks() as $disk) {
            foreach ($paths as $p) { $out[] = ['disk' => $disk, 'path' => $p]; }
        }
        return $out;
    }

    /** local + public, plus every configured cloud disk (S3, Cloudflare R2 is S3-compatible). */
    private function disks(): array
    {
        $disks = ['public', 'local'];
        foreach ((array) config('filesystems.disks') as $name => $cfg) {
            if (($cfg['driver'] ?? null) === 's3' && !empty($cfg['bucket'])) { $disks[] = $name; }
        }
        return array_values(array_unique($disks));
    }

    /**
     * @return array{audit_id:int,rows:int,anonymized:int,files_queued:int}
     * @throws \Throwable the audit row is marked failed first
     */
    public function delete(object $target, object $actor, ?string $backup = null): array
    {
        $auditId = DB::table('user_deletion_audits')->insertGetId([
            'target_code' => (string) $target->code, 'target_name' => $target->fullname, 'target_email_masked' => $this->mask((string) $target->email),
            'target_role' => $target->role_code, 'deleted_by_code' => (string) $actor->code, 'deleted_by_name' => $actor->fullname,
            'status' => 'started', 'backup' => $backup, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $targets = $this->fileTargets($target);   // before the account row is gone

        try {
            $anon = $this->anonymizeSubscriptions((string) $target->code);
            $result = $this->reset->purge(false, $this->reset->doomedForUser($target), ['usersubscription']);
        } catch (\Throwable $e) {
            DB::table('user_deletion_audits')->where('id', $auditId)->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500), 'completed_at' => now(), 'updated_at' => now()]);
            Log::error('users: delete failed', ['audit' => $auditId, 'target' => $target->code, 'by' => $actor->code, 'error' => $e->getMessage()]);
            throw $e;
        }

        DB::table('user_deletion_audits')->where('id', $auditId)->update([
            'status' => 'completed', 'rows_deleted' => $result['rows'], 'rows_anonymized' => $anon, 'files_queued' => count($targets),
            'completed_at' => now(), 'updated_at' => now(),
        ]);
        DeleteUserFilesJob::dispatch($auditId, $targets);   // inline on the sync queue, background otherwise
        Log::info('users: delete finished', ['audit' => $auditId, 'target' => $target->code, 'by' => $actor->code, 'rows' => $result['rows']]);

        return ['audit_id' => $auditId, 'rows' => $result['rows'], 'anonymized' => $anon, 'files_queued' => count($targets)];
    }

    /** Keep the financial row, drop the link to the person. Rows already anonymised are not matched again. */
    private function anonymizeSubscriptions(string $code): int
    {
        if (!DB::getSchemaBuilder()->hasTable('usersubscription')) { return 0; }
        return DB::table('usersubscription')->where('code', $code)->update(['code' => self::ANON_PREFIX . $code]);
    }

    private function mask(string $email): string
    {
        [$u, $d] = array_pad(explode('@', $email, 2), 2, '');
        return $u === '' ? '' : mb_substr($u, 0, 1) . '***@' . $d;
    }
}
