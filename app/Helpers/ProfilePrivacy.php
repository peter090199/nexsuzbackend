<?php

namespace App\Helpers;

use App\Services\BlockService;
use Illuminate\Support\Facades\DB;

/**
 * Who may see a profile and the details on it. Enforced by the API; the screens only mirror it.
 *
 *   profile / contact / experience / personal   public (every signed-in member) | connections | private (owner only)
 *   visitors   the owner opted in: people who are NOT signed in see basic info, company, open jobs and Public posts
 *   cv_link    whether the signed CV link works
 *
 * Posts keep their own visibility (PostVisibility). Contact details are additionally subject to the "Hide on my CV"
 * switches (userprofiles.*_visibility, 1 = hidden). Job applications are not affected: the candidate sent those.
 */
class ProfilePrivacy
{
    public const LEVELS = ['public', 'connections', 'private'];
    public const SECTIONS = ['profile', 'contact', 'experience', 'personal'];
    public const DEFAULTS = ['profile' => 'public', 'contact' => 'public', 'experience' => 'public', 'personal' => 'public',
        'visitors' => false, 'cv_link' => true];

    public static function get($code): array
    {
        $stored = DB::table('users')->where('code', $code)->value('privacy_settings');

        return array_merge(self::DEFAULTS, array_intersect_key(json_decode((string) $stored, true) ?: [], self::DEFAULTS));
    }

    /** May this signed-in member see that part of the owner's profile? The profile level gates every section. */
    public static function can($ownerCode, $viewerCode, string $section = 'profile'): bool
    {
        if ((string) $ownerCode === (string) $viewerCode) {
            return true;
        }
        if ($viewerCode === null || BlockService::isBlocked($ownerCode, $viewerCode)) {
            return false;
        }
        $settings = self::get($ownerCode);
        foreach (array_unique(['profile', $section]) as $key) {
            if ($settings[$key] === 'private' || ($settings[$key] === 'connections' && !PostVisibility::areFriends($ownerCode, $viewerCode))) {
                return false;
            }
        }

        return true;
    }

    /** logged-out visitors: only when the owner opted in and the profile is public */
    public static function openToVisitors($code): bool
    {
        $s = self::get($code);

        return $s['visitors'] === true && $s['profile'] === 'public';
    }

    /** accepted connections in either direction, the owner excluded */
    public static function connectionCodes($code): array
    {
        return DB::table('follows')->where('follow_status', 'accepted')
            ->where(fn ($q) => $q->where('follower_code', $code)->orWhere('following_code', $code))
            ->get(['follower_code', 'following_code'])
            ->flatMap(fn ($f) => [(string) $f->follower_code, (string) $f->following_code])
            ->unique()->reject(fn ($c) => $c === (string) $code)->values()->all();
    }

    /** accounts that limited their profile: left out of anonymous people search */
    public static function limitedCodes(): array
    {
        return DB::table('users')->whereNotNull('privacy_settings')->get(['code', 'privacy_settings'])
            ->filter(fn ($u) => (json_decode($u->privacy_settings, true)['profile'] ?? 'public') !== 'public')
            ->pluck('code')->all();
    }

    /** the profile/{code} payload, cut down to what this viewer may see */
    public static function apply(array $p, $ownerCode, $viewerCode): array
    {
        if ((string) $ownerCode === (string) $viewerCode) {
            return $p;
        }

        $emptyLines = ['education' => [], 'language' => [], 'training' => [], 'seminar' => [], 'skills' => [], 'employment' => [], 'certificate' => []];
        $blank = function (array $keys) use (&$p) {
            foreach ($keys as $k) { $p[$k] = null; }
        };

        if (($p['email_visibility'] ?? 0) == 1 || !self::can($ownerCode, $viewerCode, 'contact')) { $blank(['email']); }
        if (($p['contact_visibility'] ?? 0) == 1 || !self::can($ownerCode, $viewerCode, 'contact')) { $blank(['contact_no']); }
        if (!self::can($ownerCode, $viewerCode, 'personal')) { $blank(['date_birth', 'home_country', 'home_state']); }
        if (!self::can($ownerCode, $viewerCode, 'experience')) {
            $p['lines'] = $emptyLines;
            $p['experience_hidden'] = true;
        }
        if (!self::can($ownerCode, $viewerCode)) {
            $blank(['email', 'contact_no', 'summary', 'date_birth', 'home_country', 'home_state', 'current_location', 'current_state']);
            $p['lines'] = $emptyLines;
            $p['restricted'] = true;
        }

        return $p;
    }
}
