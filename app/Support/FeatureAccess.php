<?php

namespace App\Support;

use App\Services\PlanService;

/**
 * The ONE list of subscription features the app enforces, and who they are enforced for.
 *
 * What a plan includes is decided by the master admin in Plan Feature Assignment (the `userplandetails` rows, merged
 * up the plan ladder by PlanService). This class only says which of those catalog features guard which part of the app
 * and for which account types. Both the API (the `planfeature:CODE` middleware) and the screens (the feature-access
 * answer that /account-plan-details/my-plan-features sends to Angular) read it, so they can never disagree.
 *
 * Rules:
 *  - enforced for DEF-USERS and DEF-CLIENT only. Admins and master admins are never plan-limited, here or anywhere.
 *  - STRICT: a feature that is off (or not assigned) on the account's plan is not available.
 *  - the account's ROLE permissions still apply on top (EnsureCanCreateCv, route guards ...): access needs both.
 *  - an unknown code is refused, so a typo can never open a door.
 */
final class FeatureAccess
{
    public const APPLY_JOBS = 'APPLY_JOBS';
    public const CREATE_RESUME = 'CREATE_RESUME';
    public const CREATE_PROFILE = 'CREATE_PROFILE';
    public const NETWORK_FEED = 'NETWORK_FEED';
    public const SEARCH_ACCESS = 'SEARCH_ACCESS';
    public const MESSAGING_ACCESS = 'MESSAGING_ACCESS';
    public const JOB_POSTING = 'JOB_POSTING';
    public const JOB_APPLICANTS = 'JOB_APPLICANTS';

    private const SEEKER = ['DEF-USERS'];
    private const RECRUITER = ['DEF-CLIENT'];
    private const BOTH = ['DEF-USERS', 'DEF-CLIENT'];

    /** code => what it guards and the account types it is enforced for */
    public const FEATURES = [
        self::APPLY_JOBS => ['label' => 'Applying to jobs', 'roles' => self::SEEKER],
        self::CREATE_RESUME => ['label' => 'Building and uploading a CV or resume', 'roles' => self::SEEKER],
        self::CREATE_PROFILE => ['label' => 'Creating and editing your profile', 'roles' => self::SEEKER],
        self::NETWORK_FEED => ['label' => 'The networking feed and connections', 'roles' => self::BOTH],
        self::SEARCH_ACCESS => ['label' => 'Search', 'roles' => self::BOTH],
        self::MESSAGING_ACCESS => ['label' => 'Messaging', 'roles' => self::BOTH],
        self::JOB_POSTING => ['label' => 'Posting jobs', 'roles' => self::RECRUITER],
        self::JOB_APPLICANTS => ['label' => 'Reviewing applicants and recruitment reports', 'roles' => self::RECRUITER],
    ];

    public static function isKnown(string $code): bool
    {
        return isset(self::FEATURES[$code]);
    }

    /** is this feature enforced for this account type at all? (an admin: never) */
    public static function enforcedFor(?string $role, string $code): bool
    {
        return self::isKnown($code) && in_array($role, self::FEATURES[$code]['roles'], true);
    }

    /** may this account use the feature? Access needs the subscription to include it (the role rules apply separately) */
    public static function allows(object $user, string $code): bool
    {
        if (!PlanService::roleCanHavePlan($user->role_code ?? null)) {
            return true;                                   // admins and master admins are never plan-limited
        }
        if (!self::isKnown($code)) {
            return false;                                  // a typo must not open a door
        }
        if (!self::enforcedFor($user->role_code, $code)) {
            return true;                                   // this feature does not apply to this account type
        }

        return PlanService::hasFeature((string) $user->code, $code);
    }

    /** @return array<string,bool> every feature enforced for this account type: code => allowed */
    public static function access(object $user): array
    {
        $out = [];
        foreach (self::FEATURES as $code => $f) {
            if (self::enforcedFor($user->role_code ?? null, $code)) {
                $out[$code] = self::allows($user, $code);
            }
        }

        return $out;
    }

    /** the message to show someone who is refused: it says why (expired, suspended, or simply not in the plan) */
    public static function deniedMessage(object $user, string $code): string
    {
        $label = self::FEATURES[$code]['label'] ?? 'This feature';
        $ent = PlanService::entitlement((string) $user->code, $user->role_code ?? null);

        return match ($ent['status']) {
            'expired' => "Your subscription has expired, so {$label} is no longer included. Renew or upgrade your plan to use it again.",
            'suspended' => "Your subscription is suspended, so {$label} is not available. Please contact an administrator.",
            default => "{$label} is not included in your plan. Upgrade your plan to use it.",
        };
    }

    /** @return array<string,string> code => message, for the features this account is refused */
    public static function deniedMessages(object $user): array
    {
        $out = [];
        foreach (self::access($user) as $code => $allowed) {
            if (!$allowed) { $out[$code] = self::deniedMessage($user, $code); }
        }

        return $out;
    }
}
