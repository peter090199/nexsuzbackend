<?php

namespace App\Http\Controllers\SearchAccount;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Resource;

class UserController extends Controller
{
    // public function searchUsers(Request $request)
    // {
    //     $search = trim($request->input('search', ''));

    //     $users = DB::table('users')
    //         ->leftJoin('userprofiles', 'userprofiles.code', '=', 'users.code')
    //         ->leftJoin('userskills', 'userskills.code', '=', 'users.code')
    //         ->select(
    //             'users.code', 
    //             'users.role_code',
    //             'users.status', 
    //             'users.fullname', 
    //             'users.is_online', 
    //             DB::raw('GROUP_CONCAT(DISTINCT userskills.skills ORDER BY userskills.skills SEPARATOR ", ") as skills'),
    //             DB::raw('MIN(userprofiles.photo_pic) as photo_pic') // ✅ Replaced ANY_VALUE with MIN
    //         )
    //         ->where('users.status', 'A')
    //         ->when($search, function ($query, $search) {
    //             return $query->where(function ($q) use ($search) {
    //                 $q->where('users.fullname', 'LIKE', "%$search%")
    //                   ->orWhere('userskills.skills', 'LIKE', "%$search%");
    //             });
    //         })
    //         ->groupBy('users.code','users.role_code','users.status', 'users.fullname', 'users.is_online') // ✅ Ensure all selected fields are grouped
    //         ->orderByRaw("
    //             CASE 
    //                 WHEN users.fullname = ? THEN 1 
    //                 WHEN users.fullname LIKE ? THEN 2
    //                 WHEN GROUP_CONCAT(userskills.skills ORDER BY userskills.skills SEPARATOR ', ') LIKE ? THEN 3
    //                 ELSE 4 
    //             END ASC", [$search, "$search%", "%$search%"])
    //         ->orderByRaw("LOWER(users.fullname) ASC")
    //         ->get();

    //     // Separate online and offline users
    //     $onlineUsers = $users->where('is_online', true)->values();
    //     $offlineUsers = $users->where('is_online', false)->values();

    //     return response()->json([
    //         'success' => true,
    //         'online' => $onlineUsers,
    //         'offline' => $offlineUsers
    //     ]);
    // }

    //  public function searchUsersBypublic(Request $request)
    // {
    //     $search = trim($request->input('search', ''));

    //     $users = DB::table('users')
    //         ->leftJoin('userprofiles', 'userprofiles.code', '=', 'users.code')
    //         ->leftJoin('userskills', 'userskills.code', '=', 'users.code')
    //         ->select(
    //             'users.code', 
    //             'users.role_code',
    //             'users.status', 
    //             'users.fullname', 
    //             'users.is_online', 
    //             DB::raw('GROUP_CONCAT(DISTINCT userskills.skills ORDER BY userskills.skills SEPARATOR ", ") as skills'),
    //             DB::raw('MIN(userprofiles.photo_pic) as photo_pic') // ✅ Replaced ANY_VALUE with MIN
    //         )
    //         ->where('users.status', 'A')
    //         ->when($search, function ($query, $search) {
    //             return $query->where(function ($q) use ($search) {
    //                 $q->where('users.fullname', 'LIKE', "%$search%")
    //                   ->orWhere('userskills.skills', 'LIKE', "%$search%");
    //             });
    //         })
    //         ->groupBy('users.code','users.role_code','users.status', 'users.fullname', 'users.is_online') // ✅ Ensure all selected fields are grouped
    //         ->orderByRaw("
    //             CASE 
    //                 WHEN users.fullname = ? THEN 1 
    //                 WHEN users.fullname LIKE ? THEN 2
    //                 WHEN GROUP_CONCAT(userskills.skills ORDER BY userskills.skills SEPARATOR ', ') LIKE ? THEN 3
    //                 ELSE 4 
    //             END ASC", [$search, "$search%", "%$search%"])
    //         ->orderByRaw("LOWER(users.fullname) ASC")
    //         ->get();

    //     // Separate online and offline users
    //     $onlineUsers = $users->where('is_online', true)->values();
    //     $offlineUsers = $users->where('is_online', false)->values();

    //     return response()->json([
    //         'success' => true,
    //         'online' => $onlineUsers,
    //         'offline' => $offlineUsers
    //     ]);
    // }

    public function searchUsers(Request $request)
    {
        $search  = trim($request->input('search', ''));
        $page    = max((int) $request->input('page', 1), 1);
        $perPage = max((int) $request->input('per_page', 10), 1);

        $users = DB::table('users')
            ->leftJoin('userprofiles', 'userprofiles.code', '=', 'users.code')
            ->leftJoin('userskills', 'userskills.code', '=', 'users.code')
            ->leftJoin('resources', 'resources.code', '=', 'users.code')
            ->select(
                'users.code',
                'users.role_code',
                'users.status',
                'users.fullname',
                'users.is_online',
                DB::raw('MIN(resources.profession) as profession'),
                DB::raw('GROUP_CONCAT(DISTINCT userskills.skills ORDER BY userskills.skills SEPARATOR ", ") as skills'),
                DB::raw('MIN(userprofiles.photo_pic) as photo_pic'),
                DB::raw('MIN(users.coverphoto) as coverphoto') // ✅ Replaced ANY_VALUE with MIN
            )
            ->where('users.status', 'A')
            ->where(fn ($q) => $q->whereNull('users.role_code')->orWhereNotIn('users.role_code', ['DEF-ADMIN', 'DEF-MASTERADMIN']))   // admin accounts are not people
            ->whereNotIn('users.code', \App\Services\BlockService::relatedCodes(Auth::user()->code))
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('users.fullname', 'LIKE', "%$search%")
                        ->orWhere('userskills.skills', 'LIKE', "%$search%")
                        ->orWhere('resources.profession', 'LIKE', "%$search%");
                });
            })
            ->groupBy('users.code', 'users.role_code', 'users.status', 'users.fullname', 'users.is_online') // ✅ Ensure all selected fields are grouped
            ->orderByRaw("
                CASE
                    WHEN users.fullname = ? THEN 1
                    WHEN users.fullname LIKE ? THEN 2
                    WHEN GROUP_CONCAT(userskills.skills ORDER BY userskills.skills SEPARATOR ', ') LIKE ? THEN 3
                    ELSE 4
                END ASC", [$search, "$search%", "%$search%"])
            ->orderByRaw("LOWER(users.fullname) ASC")
            ->get();

        return $this->paginateOnlineOffline($users, $page, $perPage, true);
    }

    public function searchUsersBypublic(Request $request)
    {
        $search  = trim($request->input('search', ''));
        $page    = max((int) $request->input('page', 1), 1);
        $perPage = max((int) $request->input('per_page', 10), 1);

        $users = DB::table('users')
            ->leftJoin('userprofiles', 'userprofiles.code', '=', 'users.code')
            ->leftJoin('userskills', 'userskills.code', '=', 'users.code')
            ->leftJoin('resources', 'resources.code', '=', 'users.code')
            ->select(
                'users.code',
                'users.role_code',
                'users.status',
                'users.fullname',
                'users.is_online',
                DB::raw('MIN(resources.profession) as profession'),
                DB::raw('GROUP_CONCAT(DISTINCT userskills.skills ORDER BY userskills.skills SEPARATOR ", ") as skills'),
                DB::raw('MIN(userprofiles.photo_pic) as photo_pic'),
                DB::raw('MIN(users.coverphoto) as coverphoto') // ✅ Replaced ANY_VALUE with MIN
            )
            ->where('users.status', 'A')
            ->where(fn ($q) => $q->whereNull('users.role_code')->orWhereNotIn('users.role_code', ['DEF-ADMIN', 'DEF-MASTERADMIN']))
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('users.fullname', 'LIKE', "%$search%")
                        ->orWhere('userskills.skills', 'LIKE', "%$search%")
                        ->orWhere('resources.profession', 'LIKE', "%$search%");
                });
            })
            ->groupBy('users.code', 'users.role_code', 'users.status', 'users.fullname', 'users.is_online') // ✅ Ensure all selected fields are grouped
            ->orderByRaw("
                CASE
                    WHEN users.fullname = ? THEN 1
                    WHEN users.fullname LIKE ? THEN 2
                    WHEN GROUP_CONCAT(userskills.skills ORDER BY userskills.skills SEPARATOR ', ') LIKE ? THEN 3
                    ELSE 4
                END ASC", [$search, "$search%", "%$search%"])
            ->orderByRaw("LOWER(users.fullname) ASC")
            ->get();

        return $this->paginateOnlineOffline($users, $page, $perPage);
    }
    /**
     * Splits the full (already-filtered/sorted) user collection into
     * online/offline groups, then slices each to the requested page.
     *
     * NOTE: this re-runs the full query on every page request (same as the
     * original code did once). Fine for typical staff/user directory sizes;
     * if the users table gets very large, paginate online/offline with two
     * separate DB queries (adding an is_online filter + LIMIT/OFFSET)
     * instead of slicing an in-memory collection.
     */
    /** follow_status / id per person from the signed-in user's view (same values as "people you may know") */
    private function withFollowStatus($people)
    {
        $me    = Auth::user()->code;
        $codes = $people->pluck('code')->all();
        if (empty($codes)) return $people;

        $rows = DB::table('follows')
            ->where(function ($q) use ($me, $codes) {
                $q->where('follower_code', $me)->whereIn('following_code', $codes);
            })
            ->orWhere(function ($q) use ($me, $codes) {
                $q->where('following_code', $me)->whereIn('follower_code', $codes);
            })
            ->get(['id', 'follower_code', 'following_code', 'follow_status']);

        return $people->map(function ($p) use ($rows, $me) {
            $mine   = $rows->first(fn($r) => $r->follower_code === $me && $r->following_code === $p->code);
            $theirs = $rows->first(fn($r) => $r->following_code === $me && $r->follower_code === $p->code);

            if (($mine && $mine->follow_status === 'accepted') || ($theirs && $theirs->follow_status === 'accepted')) {
                $p->follow_status = 'accepted';
            } elseif ($mine && $mine->follow_status === 'pending') {
                $p->follow_status = 'pending';
            } elseif ($theirs && $theirs->follow_status === 'pending') {
                $p->follow_status = 'connect_back';
            } else {
                $p->follow_status = 'not_following';
            }
            $p->id = optional($mine ?: $theirs)->id;
            return $p;
        })->values();
    }

    /**
     * GET company/people/{code}[?search=&page=&per_page=]
     * The people connected to a company account (accepted follows in either direction), in the same online / offline
     * shape as searchUsers so the Company profile "People" tab can use the search page's layout.
     */
    public function companyPeople(Request $request, string $code)
    {
        $viewer = Auth::user()->code;
        if (\App\Services\BlockService::isBlocked($viewer, $code)) {
            return response()->json(['success' => false, 'blocked' => true, 'message' => 'This profile is not available.'], 404);
        }

        $search  = trim($request->input('search', ''));
        $page    = max((int) $request->input('page', 1), 1);
        $perPage = min(max((int) $request->input('per_page', 10), 1), 50);

        $connected = DB::table('follows')->where('follow_status', 'accepted')
            ->where(fn ($q) => $q->where('follower_code', $code)->orWhere('following_code', $code))
            ->get(['follower_code', 'following_code'])
            ->flatMap(fn ($f) => [(string) $f->follower_code, (string) $f->following_code])
            ->unique()->reject(fn ($c) => $c === (string) $code)->values()->all();

        $users = DB::table('users')
            ->leftJoin('userprofiles', 'userprofiles.code', '=', 'users.code')
            ->leftJoin('userskills', 'userskills.code', '=', 'users.code')
            ->leftJoin('resources', 'resources.code', '=', 'users.code')
            ->select(
                'users.code', 'users.role_code', 'users.status', 'users.fullname', 'users.is_online',
                DB::raw('MIN(resources.profession) as profession'),
                DB::raw('GROUP_CONCAT(DISTINCT userskills.skills ORDER BY userskills.skills SEPARATOR ", ") as skills'),
                DB::raw('MIN(userprofiles.photo_pic) as photo_pic'),
                DB::raw('MIN(users.coverphoto) as coverphoto')
            )
            ->where('users.status', 'A')
            ->where(fn ($q) => $q->whereNull('users.role_code')->orWhereNotIn('users.role_code', ['DEF-ADMIN', 'DEF-MASTERADMIN']))
            ->whereIn('users.code', $connected)
            ->whereNotIn('users.code', \App\Services\BlockService::relatedCodes($viewer))
            ->when($search, fn ($query, $search) => $query->where(function ($q) use ($search) {
                $q->where('users.fullname', 'LIKE', "%$search%")
                    ->orWhere('userskills.skills', 'LIKE', "%$search%")
                    ->orWhere('resources.profession', 'LIKE', "%$search%");
            }))
            ->groupBy('users.code', 'users.role_code', 'users.status', 'users.fullname', 'users.is_online')
            ->orderByRaw('LOWER(users.fullname) ASC')
            ->get();

        return $this->paginateOnlineOffline($users, $page, $perPage, true);
    }

    private function paginateOnlineOffline($users, int $page, int $perPage, bool $withFollow = false)
    {
        $onlineUsers  = $users->where('is_online', true)->values();
        $offlineUsers = $users->where('is_online', false)->values();

        $onlineTotal  = $onlineUsers->count();
        $offlineTotal = $offlineUsers->count();

        $offset = ($page - 1) * $perPage;

        $onlinePage  = $onlineUsers->slice($offset, $perPage)->values();
        $offlinePage = $offlineUsers->slice($offset, $perPage)->values();

        if ($withFollow) {
            $onlinePage  = $this->withFollowStatus($onlinePage);
            $offlinePage = $this->withFollowStatus($offlinePage);
        }

        return response()->json([
            'success' => true,
            'online'  => $onlinePage,
            'offline' => $offlinePage,
            'meta' => [
                'current_page'    => $page,
                'per_page'        => $perPage,
                'online_total'    => $onlineTotal,
                'offline_total'   => $offlineTotal,
                'online_has_more'  => ($offset + $perPage) < $onlineTotal,
                'offline_has_more' => ($offset + $perPage) < $offlineTotal,
            ],
        ]);
    }
}
