<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Removes a deleted account's uploaded files from local, public and cloud (S3 / Cloudflare R2) disks. The paths are
 * worked out by UserDeletionService before the account is removed and are only ever folders named after that account.
 * With QUEUE_CONNECTION=sync this runs inline; with a real queue it runs in the background so big accounts never time out.
 */
class DeleteUserFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @param array<int,array{disk:string,path:string}> $targets */
    public function __construct(public int $auditId, public array $targets) {}

    public function handle(): void
    {
        $deleted = 0; $errors = [];
        foreach ($this->targets as $t) {
            // defence in depth: only relative folders under uploads/, never a traversal
            if (!str_starts_with($t['path'], 'uploads/') || str_contains($t['path'], '..')) { continue; }
            try {
                $disk = Storage::disk($t['disk']);
                if ($disk->directoryExists($t['path'])) {
                    $disk->deleteDirectory($t['path']);
                    $deleted++;
                }
            } catch (\Throwable $e) {
                $errors[] = "{$t['disk']}:{$t['path']} " . mb_substr($e->getMessage(), 0, 120);
                Log::warning('users: file cleanup failed', ['audit' => $this->auditId, 'disk' => $t['disk'], 'path' => $t['path'], 'error' => $e->getMessage()]);
            }
        }
        DB::table('user_deletion_audits')->where('id', $this->auditId)->update([
            'files_deleted' => $deleted,
            'error' => $errors ? implode(' | ', $errors) : null,
            'updated_at' => now(),
        ]);
    }
}
