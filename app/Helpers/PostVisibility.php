<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * Who may see a post, react to it and comment on it. Enforced on the server, the UI only mirrors it.
 *
 * posts.status (tinyint, existing column):
 *   0  PRIVATE  only the owner
 *   1  PUBLIC   everyone
 *   2  FRIENDS  the owner and accepted friends
 *
 * "Friends" = an accepted row in `follows` in either direction, which is the same relation the
 * Home feed already uses to decide whose posts you see.
 */
class PostVisibility
{
    public const PRIVATE = 0;
    public const PUBLIC  = 1;
    public const FRIENDS = 2;

    public static function areFriends($a, $b): bool
    {
        if ($a === null || $b === null || (string) $a === (string) $b) {
            return false;
        }

        return DB::table('follows')
            ->where('follow_status', 'accepted')
            ->where(function ($q) use ($a, $b) {
                $q->where(fn ($x) => $x->where('follower_code', $a)->where('following_code', $b))
                  ->orWhere(fn ($x) => $x->where('follower_code', $b)->where('following_code', $a));
            })
            ->exists();
    }

    /** Can $viewerCode see a post owned by $ownerCode with this status? */
    public static function canView($ownerCode, $status, $viewerCode): bool
    {
        if ((string) $ownerCode === (string) $viewerCode) {
            return true;                                   // owners always see their own posts
        }

        switch ((int) $status) {
            case self::PUBLIC:  return true;
            case self::FRIENDS: return self::areFriends($ownerCode, $viewerCode);
            default:            return false;              // private (0) and any unknown value
        }
    }

    public static function canViewPostId($postId, $viewerCode): bool
    {
        $post = DB::table('posts')->where('id', $postId)->first(['code', 'status']);

        return $post ? self::canView($post->code, $post->status, $viewerCode) : false;
    }

    /**
     * posts_uuid, or an attachment's posts_uuind. A uuid that matches nothing returns true so the
     * caller's own "not found" handling still answers (there is nothing to protect).
     */
    public static function canViewUuid(?string $uuidOrUind, $viewerCode): bool
    {
        if (!$uuidOrUind) {
            return true;
        }
        $post = DB::table('posts')->where('posts_uuid', $uuidOrUind)->first(['code', 'status']);
        if (!$post) {
            $parent = DB::table('attachmentposts')->where('posts_uuind', $uuidOrUind)->value('posts_uuid');
            $post = $parent ? DB::table('posts')->where('posts_uuid', $parent)->first(['code', 'status']) : null;
        }

        return $post ? self::canView($post->code, $post->status, $viewerCode) : true;
    }

    /** Statuses the viewer may see on posts of $ownerCode. */
    public static function statusesFor($ownerCode, $viewerCode): array
    {
        if ((string) $ownerCode === (string) $viewerCode) {
            return [self::PRIVATE, self::PUBLIC, self::FRIENDS];
        }

        return self::areFriends($ownerCode, $viewerCode) ? [self::PUBLIC, self::FRIENDS] : [self::PUBLIC];
    }

    /** Limit a posts query (Eloquent or DB builder) to what $viewerCode may see on $ownerCode's posts. */
    public static function limit($query, $ownerCode, $viewerCode)
    {
        return $query->whereIn('status', self::statusesFor($ownerCode, $viewerCode));
    }
}
