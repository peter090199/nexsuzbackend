<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * A stored CV file can be used by more than one record: the account's saved CV (user_resumes) and every job application sent with
 * it (applied_resumes). The file is written once, and only removed when nothing uses it any more, so deleting a saved CV never takes
 * away what a recruiter already received, and a recruiter deleting an application never removes the applicant's saved CV.
 */
final class StoredCv
{
    /** The path relative to the storage disk ("uploads/..."), or null when the value is not one of our files. */
    public static function relative(?string $stored): ?string
    {
        if (!$stored || str_contains($stored, '..')) { return null; }

        return preg_match('#uploads/.+$#', $stored, $m) ? $m[0] : null;
    }

    /** Is the file still somebody's saved CV, or attached to an application? A "like" match errs towards keeping the file. */
    public static function inUse(?string $stored): bool
    {
        $rel = self::relative($stored);
        if (!$rel) { return false; }

        return DB::table('user_resumes')->where('path', $rel)->exists()
            || DB::table('applied_resumes')->where('resume_pdf', 'like', '%' . addcslashes($rel, '%_\\'))->exists();
    }

    /** How many applications were sent with this file. */
    public static function applicationsUsing(?string $stored): int
    {
        $rel = self::relative($stored);

        return $rel ? DB::table('applied_resumes')->where('resume_pdf', 'like', '%' . addcslashes($rel, '%_\\'))->count() : 0;
    }
}
