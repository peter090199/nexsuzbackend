<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * The one place that knows about blocks. Every API that lets two accounts interact asks it.
 *
 *   - A block always works in BOTH directions: if A blocked B (or B blocked A) they cannot interact.
 *   - Accounts are identified by users.code. It works the same for every account type.
 *   - The caller is told only "not available", never who blocked whom.
 *   - Blocking never deletes messages, posts or accounts; it only removes follow/connection rows.
 */
class BlockService
{
    public const MESSAGE = 'You can\'t interact with this account.';

    /**
     * Platform restriction: account types that may NOT block certain other account types.
     * actor role => target roles it cannot block. Everything not listed here is allowed.
     */
    public const CANNOT_BLOCK = [
        'DEF-USERS' => ['DEF-ADMIN', 'DEF-MASTERADMIN'],
    ];

    /** Why $actorRole may not block $targetRole, or null when it may. */
    public static function restriction(?string $actorRole, ?string $targetRole): ?string
    {
        if ($actorRole && $targetRole && in_array($targetRole, self::CANNOT_BLOCK[$actorRole] ?? [], true)) {
            return 'You can\'t block an admin account.';
        }

        return null;
    }

    /** @var array<string, int[]> per-request cache: viewer code => codes blocked in either direction */
    private static array $cache = [];

    /** Codes that have a block with $code in either direction. */
    public static function relatedCodes($code): array
    {
        if ($code === null || $code === '') {
            return [];
        }
        $key = (string) $code;

        if (!isset(self::$cache[$key])) {
            $mine = DB::table('user_blocks')->where('blocker_code', $code)->pluck('blocked_code')->all();
            $theirs = DB::table('user_blocks')->where('blocked_code', $code)->pluck('blocker_code')->all();
            self::$cache[$key] = array_values(array_unique(array_map('intval', array_merge($mine, $theirs))));
        }

        return self::$cache[$key];
    }

    /** Is there a block between these two accounts (either direction)? */
    public static function isBlocked($a, $b): bool
    {
        if ($a === null || $b === null || (string) $a === (string) $b) {
            return false;
        }

        return in_array((int) $b, self::relatedCodes($a), true);
    }

    public static function blockedByMe($me, $other): bool
    {
        return DB::table('user_blocks')->where('blocker_code', $me)->where('blocked_code', $other)->exists();
    }

    /** A ready 403 when $me and $other are blocked, otherwise null. */
    public static function guard($me, $other): ?JsonResponse
    {
        return self::isBlocked($me, $other) ? self::denied() : null;
    }

    public static function denied(int $status = 403): JsonResponse
    {
        return response()->json(['success' => false, 'status' => false, 'blocked' => true, 'message' => self::MESSAGE], $status);
    }

    /** ' AND column NOT IN (1,2,3)' for raw SQL (codes are cast to int, so nothing can be injected). */
    public static function sqlNotIn(string $column, $me): string
    {
        $codes = self::relatedCodes($me);

        return $codes ? " AND {$column} NOT IN (" . implode(',', $codes) . ')' : '';
    }

    /** Hide blocked accounts from a query builder / Eloquent query. */
    public static function exclude($query, string $column, $me)
    {
        $codes = self::relatedCodes($me);

        return $codes ? $query->whereNotIn($column, $codes) : $query;
    }

    // ------------------------------------------------------------------ write

    /**
     * $blocker blocks $target. Existing follow / connection rows between them are removed
     * (both directions, pending or accepted). Messages, posts and accounts stay.
     */
    public static function block($blocker, $target): void
    {
        $now = now();

        DB::table('user_blocks')->updateOrInsert(
            ['blocker_code' => $blocker, 'blocked_code' => $target],
            ['created_at' => $now, 'updated_at' => $now]
        );

        DB::table('follows')->where(function ($q) use ($blocker, $target) {
            $q->where(fn ($x) => $x->where('follower_code', $blocker)->where('following_code', $target))
              ->orWhere(fn ($x) => $x->where('follower_code', $target)->where('following_code', $blocker));
        })->delete();

        self::$cache = [];
    }

    public static function unblock($blocker, $target): bool
    {
        $deleted = DB::table('user_blocks')->where('blocker_code', $blocker)->where('blocked_code', $target)->delete();
        self::$cache = [];

        return $deleted > 0;
    }

    /** Accounts $me has blocked (newest first), with the details the Blocked Users screen shows. */
    public static function listFor($me, string $search = ''): array
    {
        $rows = DB::table('user_blocks as b')
            ->join('users as u', 'u.code', '=', 'b.blocked_code')
            ->leftJoin('userprofiles as p', 'p.code', '=', 'u.code')
            ->where('b.blocker_code', $me)
            ->when($search !== '', fn ($q) => $q->where('u.fullname', 'like', '%' . $search . '%'))
            ->orderByDesc('b.created_at')
            ->get(['u.code', 'u.fullname', 'u.role_code', 'u.company', 'p.photo_pic', 'b.created_at as blocked_at']);

        return $rows->all();
    }
}
