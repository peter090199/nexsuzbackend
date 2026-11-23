<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Applicant CVs used to be saved on the PUBLIC disk, where anyone who knew the file's address could open it.
 * New CVs now go to the private disk. This moves the existing ones there too (the database paths stay valid:
 * the review endpoint looks in private storage first). Copy, verify, then delete the public copy.
 *
 *   php artisan recruitment:secure-cvs --dry-run     show what would move
 *   php artisan recruitment:secure-cvs               move them
 */
class SecureApplicantCvs extends Command
{
    protected $signature = 'recruitment:secure-cvs {--dry-run : only list what would be moved}';
    protected $description = 'Move applicant CV files from the public disk to private storage';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $moved = 0; $already = 0; $missing = 0;

        foreach (DB::table('applied_resumes')->get(['resume_id', 'resume_pdf']) as $r) {
            $rel = preg_match('#uploads/.+$#', (string) $r->resume_pdf, $m) ? $m[0] : null;
            if (!$rel) { $this->warn("#{$r->resume_id}: unrecognised path, skipped"); continue; }

            if (!$public->exists($rel)) {
                $private->exists($rel) ? $already++ : $missing++;
                if (!$private->exists($rel)) { $this->warn("#{$r->resume_id}: file not found ({$rel})"); }
                continue;
            }

            if ($this->option('dry-run')) { $this->line("would move: {$rel}"); $moved++; continue; }

            $private->put($rel, $public->get($rel));
            if ($private->exists($rel) && $private->size($rel) === $public->size($rel)) {
                $public->delete($rel);
                $moved++;
                $this->info("moved: {$rel}");
            } else {
                $this->error("copy could not be verified, left in place: {$rel}");
            }
        }

        $this->line(($this->option('dry-run') ? 'Would move' : 'Moved') . " {$moved}; already private {$already}; missing {$missing}.");

        return self::SUCCESS;
    }
}
