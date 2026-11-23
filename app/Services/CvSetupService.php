<?php

namespace App\Services;

use App\Models\Userprofile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\FeatureAccess;

/**
 * Has a job seeker finished setting up their CV?
 *
 * "Finished" is exactly what the CV editor's Finish button already insists on (print-cv finish()): name and profession
 * (which sign-up collects) plus the profile fields saved by the editor. Only DEF-USERS have a CV, so the check does not
 * apply to anyone else. The Angular route guard asks this one place instead of keeping its own copy of the rules.
 */
class CvSetupService
{
    /** userprofiles column => label shown to the user */
    public const REQUIRED_PROFILE_FIELDS = [
        'contact_no'       => 'Contact number',
        'date_birth'       => 'Date of birth',
        'home_country'     => 'Home country',
        'home_state'       => 'Home city / state',
        'current_location' => 'Current location',
        'current_state'    => 'Current city / state',
        'summary'          => 'Summary',
    ];

    public static function appliesTo(?string $roleCode): bool
    {
        return $roleCode === 'DEF-USERS';
    }

    /** @return array{applies:bool, complete:bool, missing:string[], total:int} */
    public function status(object $user): array
    {
        if (!self::appliesTo($user->role_code ?? null)) {
            return ['applies' => false, 'complete' => true, 'missing' => [], 'total' => 0];
        }

        // A job seeker whose plan does not include building or uploading a CV cannot do the setup, so they are not asked to
        // (otherwise the "finish your CV first" rule would shut them out of a page the plan does not allow).
        if (!FeatureAccess::allows($user, FeatureAccess::CREATE_RESUME)) {
            return ['applies' => false, 'complete' => true, 'missing' => [], 'total' => 0];
        }

        $profile = Userprofile::where('code', $user->code)->first();
        $profession = DB::table('resources')->where('code', $user->code)->value('profession');

        $missing = [];
        if (self::blank($user->fname ?? null) || self::blank($user->lname ?? null)) { $missing[] = 'Name'; }
        if (self::blank($profession)) { $missing[] = 'Profession'; }
        foreach (self::REQUIRED_PROFILE_FIELDS as $col => $label) {
            if (!$profile || self::blank($profile->{$col} ?? null)) { $missing[] = $label; }
        }

        $builderComplete = $missing === [];

        // Upload-only mode (Settings > CV mode, set by the master admin): one uploaded CV / resume is all that is asked.
        // A CV that was already finished with the builder still counts, so nobody who was in is suddenly locked out.
        if (CvModeService::uploadOnly()) {
            $hasResume = Schema::hasTable('user_resumes')
                && DB::table('user_resumes')->where('code', $user->code)->exists();
            $complete = $hasResume || $builderComplete;

            return ['applies' => true, 'complete' => $complete, 'missing' => $complete ? [] : ['Upload your CV or resume'],
                'total' => 1, 'mode' => 'upload'];
        }

        return ['applies' => true, 'complete' => $builderComplete, 'missing' => $missing,
            'total' => 2 + count(self::REQUIRED_PROFILE_FIELDS), 'mode' => 'builder'];
    }

    private static function blank($v): bool
    {
        return $v === null || trim((string) $v) === '';
    }
}
