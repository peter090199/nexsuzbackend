<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * A user's notification preferences (table user_notification_settings, one row per user).
 *
 * Only keys the user actually changed are stored; everything else comes from DEFAULTS, so a new default
 * reaches existing users and "restore defaults" is just deleting the stored keys.
 *
 * The server uses these to decide what is really delivered (see ChatController::sendMessage and
 * ActivityNotificationController), the browser uses them to decide how a delivered notification is shown.
 */
class NotificationPrefs
{
    /** key => default. Booleans, except sound_volume (0-100). Sound is OFF and desktop is OFF until the user opts in. */
    public const DEFAULTS = [
        // messages
        'messages_enabled'     => true,    // new message notifications (toast, desktop, sound) at all
        'message_preview'      => true,    // show the message text in a notification (otherwise "sent you a message")
        'unread_badge'         => true,    // unread counts on the bell, the menu and the tab title
        'desktop_notifications' => false,  // browser notifications (also needs the browser's permission)
        'toast'                => true,    // in-app popup
        'sound'                => false,   // notification sound
        'sound_volume'         => 70,
        // social activity (delivered through the activity feed)
        'friend_requests'      => true,
        'friend_accepts'       => true,
        'post_reactions'       => true,
        'post_comments'        => true,
        // jobs
        'job_applications'     => true,    // someone applied to my job posting
        'job_interviews'       => true,    // I was invited to an interview
        'job_status'           => true,    // my application was approved / rejected
        'job_reviews'          => true,    // my application is under review
        // appearance: background colour of the navigation bars, "#rrggbb", or null for the standard look
        'nav_color'            => null,
    ];

    /** Settings that belong to the notification switches: "restore defaults" resets these and nothing else */
    public const APPEARANCE_KEYS = ['nav_color'];

    public const BOOLEAN_KEYS = [
        'messages_enabled', 'message_preview', 'unread_badge', 'desktop_notifications', 'toast', 'sound',
        'friend_requests', 'friend_accepts', 'post_reactions', 'post_comments',
        'job_applications', 'job_interviews', 'job_status', 'job_reviews',
    ];

    /** The activity feed categories and the preference that controls each */
    public const ACTIVITY = [
        'friend_request' => 'friend_requests',
        'friend_accept'  => 'friend_accepts',
        'reaction'       => 'post_reactions',
        'comment'        => 'post_comments',
        'job_application' => 'job_applications',
        'job_interview'  => 'job_interviews',
        'job_status'     => 'job_status',
        'job_review'     => 'job_reviews',
    ];

    /** Laravel validation rules for a (partial) update */
    public static function rules(): array
    {
        $rules = [
            'sound_volume' => 'sometimes|integer|between:0,100',
            'nav_color'    => ['sometimes', 'nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
        foreach (self::BOOLEAN_KEYS as $key) {
            $rules[$key] = 'sometimes|boolean';
        }

        return $rules;
    }

    private static function row(int $userId): ?object
    {
        return DB::table('user_notification_settings')->where('user_id', $userId)->first();
    }

    private static function decode(?object $row): array
    {
        $stored = $row && $row->settings ? json_decode($row->settings, true) : [];

        return is_array($stored) ? $stored : [];
    }

    /** Defaults merged with what the user saved. Always every key, always the right type. */
    public static function for(int $userId): array
    {
        $stored = self::decode(self::row($userId));
        $out = self::DEFAULTS;

        foreach (self::BOOLEAN_KEYS as $key) {
            if (array_key_exists($key, $stored)) {
                $out[$key] = (bool) $stored[$key];
            }
        }
        if (isset($stored['sound_volume'])) {
            $out['sound_volume'] = max(0, min(100, (int) $stored['sound_volume']));
        }
        if (isset($stored['nav_color']) && is_string($stored['nav_color']) && preg_match('/^#[0-9a-f]{6}$/', $stored['nav_color'])) {
            $out['nav_color'] = $stored['nav_color'];
        }

        return $out;
    }

    /** Save a partial update (already validated); returns the full settings. Unknown keys are ignored. */
    public static function update(int $userId, array $changes): array
    {
        $allowed = array_intersect_key($changes, self::DEFAULTS);
        $stored = self::decode(self::row($userId));

        foreach ($allowed as $key => $value) {
            if ($key === 'nav_color') {
                $stored[$key] = $value === null ? null : strtolower((string) $value);   // null = back to the standard look
            } else {
                $stored[$key] = $key === 'sound_volume' ? (int) $value : (bool) $value;
            }
        }

        self::write($userId, ['settings' => json_encode($stored)]);

        return self::for($userId);
    }

    /** Restore the defaults (keeps the "seen" time: that is not a preference) */
    public static function reset(int $userId): array
    {
        if (self::row($userId)) {
            // restore the notification defaults, but keep the appearance choice (the nav colour is not a notification setting)
            $keep = array_intersect_key(self::decode(self::row($userId)), array_flip(self::APPEARANCE_KEYS));
            self::write($userId, ['settings' => $keep ? json_encode($keep) : null]);
        }

        return self::for($userId);
    }

    public static function allows(int $userId, string $key): bool
    {
        return (bool) (self::for($userId)[$key] ?? false);
    }

    /**
     * Should the server push an "unread count changed" event to this user's browser?
     * Needed by new-message notifications AND by the unread badge; if the user turned both off, nothing is pushed.
     */
    public static function wantsMessagePush(int $userId): bool
    {
        $p = self::for($userId);

        return $p['messages_enabled'] || $p['unread_badge'];
    }

    // ---- activity "seen" time (the moment the user last looked at their activity notifications) ----

    public static function seenAt(int $userId): ?string
    {
        $row = self::row($userId);

        return $row->seen_at ?? null;
    }

    public static function markSeen(int $userId): void
    {
        self::write($userId, ['seen_at' => now()]);
    }

    private static function write(int $userId, array $values): void
    {
        $now = now();
        // one atomic statement: two devices saving at the same moment can never collide on the unique user_id
        DB::table('user_notification_settings')->upsert(
            [$values + ['user_id' => $userId, 'created_at' => $now, 'updated_at' => $now]],
            ['user_id'],
            array_merge(array_keys($values), ['updated_at'])
        );
    }
}
