<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationPrefs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Social activity addressed to the signed-in user: friend requests, accepted requests, reactions and comments
 * (and replies) on their posts.
 *
 * There is no stored notification list: these events already live in follows / reactionpost / commentposts /
 * commentreplies, so this endpoint reads them. That is what makes the preferences real: a category the user
 * switched off is simply not read, so it is not delivered here (feed, count or badge), whatever the browser does.
 * The only thing stored is "seen_at", the moment the user last opened their activity.
 *
 *   GET  activity-notifications[?count_only=1]   -> { data: [...], unread_count }
 *   POST activity-notifications/read             -> marks everything seen
 */
class ActivityNotificationController extends Controller
{
    private const WINDOW_DAYS = 30;
    private const PER_CATEGORY = 30;
    private const MAX_ITEMS = 40;

    public function index(Request $request)
    {
        $me = Auth::user();
        $prefs = NotificationPrefs::for($me->id);

        // First look: everything that already exists counts as seen, so a user is not greeted by 30 days of history
        $seen = NotificationPrefs::seenAt($me->id);
        if ($seen === null) {
            NotificationPrefs::markSeen($me->id);
            $seen = now()->toDateTimeString();
        }

        $items = $this->collect((int) $me->code, $prefs)
            ->sortByDesc('created_at')->values()->take(self::MAX_ITEMS);

        $unread = $items->filter(fn ($i) => strtotime($i['created_at']) > strtotime($seen))->count();

        if ($request->boolean('count_only')) {
            return response()->json(['success' => true, 'unread_count' => $unread]);
        }

        $actors = $this->actors($items->pluck('actor_code')->unique()->all());
        $data = $items->map(function ($i) use ($actors, $seen) {
            $a = $actors[(int) $i['actor_code']] ?? null;
            $i['actor'] = $a ? ['code' => $a->code, 'user_id' => $a->id, 'fullname' => $a->fullname, 'photo_pic' => $a->photo_pic] : null;
            $i['unread'] = strtotime($i['created_at']) > strtotime($seen);
            unset($i['actor_code']);

            return $i;
        })->all();

        return response()->json(['success' => true, 'data' => $data, 'unread_count' => $unread]);
    }

    public function markRead()
    {
        NotificationPrefs::markSeen(Auth::id());

        return response()->json(['success' => true, 'message' => 'Marked as read.', 'unread_count' => 0]);
    }

    /** Raw events for this user, only for the categories their preferences allow */
    private function collect(int $code, array $prefs)
    {
        $since = now()->subDays(self::WINDOW_DAYS);
        $items = collect();

        if ($prefs['friend_requests']) {
            DB::table('follows')->where('following_code', $code)->where('follow_status', 'pending')
                ->where('created_at', '>=', $since)->orderByDesc('created_at')->limit(self::PER_CATEGORY)
                ->get(['id', 'follower_code', 'created_at'])
                ->each(fn ($r) => $items->push([
                    'id' => 'fr-' . $r->id, 'type' => 'friend_request', 'actor_code' => $r->follower_code,
                    'text' => 'sent you a friend request', 'created_at' => $r->created_at,
                ]));
        }

        if ($prefs['friend_accepts']) {
            // accepting a request stamps the row's created_at with the accept time
            DB::table('follows')->where('follower_code', $code)->where('follow_status', 'accepted')
                ->where('created_at', '>=', $since)->orderByDesc('created_at')->limit(self::PER_CATEGORY)
                ->get(['id', 'following_code', 'created_at'])
                ->each(fn ($r) => $items->push([
                    'id' => 'fa-' . $r->id, 'type' => 'friend_accept', 'actor_code' => $r->following_code,
                    'text' => 'accepted your friend request', 'created_at' => $r->created_at,
                ]));
        }

        if ($prefs['post_reactions']) {
            DB::table('reactionpost as r')->join('posts as p', 'p.id', '=', 'r.post_id')
                ->where('p.code', $code)->where('r.code', '<>', (string) $code)
                ->whereNotNull('r.reaction')->where('r.reaction', '<>', 'Unlike')
                ->whereRaw('COALESCE(r.create_at, r.updated_at) >= ?', [$since])
                ->orderByRaw('COALESCE(r.create_at, r.updated_at) DESC')->limit(self::PER_CATEGORY)
                ->get(['r.id', 'r.code as actor', 'r.reaction', 'p.posts_uuid', DB::raw('COALESCE(r.create_at, r.updated_at) as at')])
                ->each(fn ($r) => $items->push([
                    'id' => 're-' . $r->id, 'type' => 'reaction', 'actor_code' => $r->actor, 'post_uuid' => $r->posts_uuid,
                    'reaction' => $r->reaction, 'text' => 'reacted ' . $r->reaction . ' to your post', 'created_at' => $r->at,
                ]));
        }

        if ($prefs['post_comments']) {
            DB::table('commentposts as c')->join('posts as p', 'p.posts_uuid', '=', 'c.post_uuidOrUind')
                ->where('p.code', $code)->where('c.code', '<>', $code)->where('c.created_at', '>=', $since)
                ->orderByDesc('c.created_at')->limit(self::PER_CATEGORY)
                ->get(['c.id', 'c.code as actor', 'c.comment', 'c.created_at', 'p.posts_uuid'])
                ->each(fn ($r) => $items->push([
                    'id' => 'co-' . $r->id, 'type' => 'comment', 'actor_code' => $r->actor, 'post_uuid' => $r->posts_uuid,
                    'text' => 'commented on your post', 'excerpt' => $this->excerpt($r->comment), 'created_at' => $r->created_at,
                ]));

            // a reply to MY comment, on anyone's post
            DB::table('commentreplies as rp')->join('commentposts as c', 'c.comment_uuid', '=', 'rp.comment_uuid')
                ->where('c.code', $code)->where('rp.code', '<>', $code)->where('rp.created_at', '>=', $since)
                ->orderByDesc('rp.created_at')->limit(self::PER_CATEGORY)
                ->get(['rp.id', 'rp.code as actor', 'rp.comment', 'rp.created_at', 'c.post_uuidOrUind as posts_uuid'])
                ->each(fn ($r) => $items->push([
                    'id' => 'rp-' . $r->id, 'type' => 'comment', 'actor_code' => $r->actor, 'post_uuid' => $r->posts_uuid,
                    'text' => 'replied to your comment', 'excerpt' => $this->excerpt($r->comment), 'created_at' => $r->created_at,
                ]));
        }

        // ---- jobs (read from applied_jobs / jobPosting; updated_at moves when the status changes) ----
        if ($prefs['job_applications']) {
            DB::table('applied_jobs as aj')->join('jobPosting as jp', 'aj.transNo', '=', 'jp.transNo')
                ->where('jp.code', $code)->where('aj.code', '<>', (string) $code)->where('aj.created_at', '>=', $since)
                ->orderByDesc('aj.created_at')->limit(self::PER_CATEGORY)
                ->get(['aj.applied_id', 'aj.code as actor', 'aj.job_name', 'aj.created_at'])
                ->each(fn ($r) => $items->push([
                    'id' => 'ja-' . $r->applied_id, 'type' => 'job_application', 'actor_code' => $r->actor,
                    'text' => 'applied to your job posting', 'excerpt' => $r->job_name, 'created_at' => $r->created_at,
                ]));
        }

        // status changes on MY applications; the actor is the employer who owns the posting
        $mine = fn () => DB::table('applied_jobs as aj')->join('jobPosting as jp', 'aj.transNo', '=', 'jp.transNo')
            ->where('aj.code', (string) $code)->where('aj.updated_at', '>=', $since)
            ->orderByDesc('aj.updated_at')->limit(self::PER_CATEGORY);
        $push = fn ($rows, string $prefix, string $type, string $text) => $rows->each(fn ($r) => $items->push([
            'id' => $prefix . $r->applied_id, 'type' => $type, 'actor_code' => $r->actor,
            'text' => $text, 'excerpt' => $r->job_name, 'created_at' => $r->at,
        ]));
        $cols = ['aj.applied_id', 'jp.code as actor', 'aj.job_name', 'aj.updated_at as at'];

        if ($prefs['job_interviews']) {
            $push($mine()->where('aj.applied_status', 'interview')->get($cols), 'ji-', 'job_interview', 'invited you to an interview');
        }
        if ($prefs['job_reviews']) {
            $push($mine()->where('aj.applied_status', 'review')->get($cols), 'jr-', 'job_review', 'is reviewing your application');
        }
        if ($prefs['job_status']) {
            $push($mine()->where('aj.applied_status', 'approved')->get($cols), 'jh-', 'job_status', 'approved your application');
            $push($mine()->where('aj.applied_status', 'reject')->get($cols), 'jx-', 'job_status', 'did not move forward with your application');
        }

        return $items;
    }

    private function actors(array $codes): array
    {
        if (!$codes) {
            return [];
        }

        return DB::table('users')->whereIn('code', $codes)
            ->select('id', 'code', 'fullname', DB::raw('(SELECT photo_pic FROM userprofiles WHERE userprofiles.code = users.code LIMIT 1) AS photo_pic'))
            ->get()->keyBy('code')->all();
    }

    private function excerpt(?string $text): string
    {
        $text = trim(preg_replace('/\s+/', ' ', (string) $text));

        return mb_strlen($text) > 90 ? mb_substr($text, 0, 90) . '…' : $text;
    }
}
