<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * Reaction rules and summaries shared by post reactions (table reactionpost)
 * and comment/reply reactions (table commentreactions).
 */
class ReactionSummary
{
    /** The reactions the UI offers; anything else is rejected by validation. */
    public const ALLOWED = ['Like', 'Love', 'Care', 'Haha', 'Wow', 'Sad', 'Angry'];

    /**
     * Summary for one set of reaction rows ({reaction, code}).
     *
     * @param  iterable $rows   objects/arrays with a "reaction" and a "code"
     * @return array{total:int, breakdown:array<int,array{reaction:string,count:int}>, mine:?string}
     */
    public static function build(iterable $rows, $myCode): array
    {
        $counts = [];
        $mine = null;

        foreach ($rows as $row) {
            $row = (object) $row;
            if (!$row->reaction || $row->reaction === 'Unlike') {
                continue;
            }
            $counts[$row->reaction] = ($counts[$row->reaction] ?? 0) + 1;
            if ((string) $row->code === (string) $myCode) {
                $mine = $row->reaction;
            }
        }

        arsort($counts);
        $breakdown = [];
        foreach ($counts as $reaction => $count) {
            $breakdown[] = ['reaction' => $reaction, 'count' => $count];
        }

        return ['total' => array_sum($counts), 'breakdown' => $breakdown, 'mine' => $mine];
    }

    public static function forPost(int $postId, $myCode): array
    {
        return self::build(
            DB::table('reactionpost')->where('post_id', $postId)->get(['reaction', 'code']),
            $myCode
        );
    }

    public static function forComment(string $type, string $ref, $myCode): array
    {
        return self::build(
            DB::table('commentreactions')->where('target_type', $type)->where('target_ref', $ref)->get(['reaction', 'code']),
            $myCode
        );
    }

    /** Summaries for many comments/replies of one type in a single query: [ref => summary]. */
    public static function forMany(string $type, array $refs, $myCode): array
    {
        $refs = array_map('strval', array_values(array_unique($refs)));
        $grouped = [];
        if ($refs) {
            $rows = DB::table('commentreactions')->where('target_type', $type)->whereIn('target_ref', $refs)
                ->get(['target_ref', 'reaction', 'code']);
            foreach ($rows as $r) {
                $grouped[$r->target_ref][] = $r;
            }
        }

        $out = [];
        foreach ($refs as $ref) {
            $out[$ref] = self::build($grouped[$ref] ?? [], $myCode);
        }

        return $out;
    }
}
