<?php

namespace App\Http\Controllers;

use App\Services\BlockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Block / unblock. Every operation acts on the SIGNED-IN account's own block list only: there is no way to
 * read or change another account's blocks through these routes.
 */
class BlockController extends Controller
{
    /** GET blocks?search= : the accounts I have blocked */
    public function index(Request $request)
    {
        $data = BlockService::listFor(Auth::user()->code, trim((string) $request->query('search', '')));

        return response()->json(['success' => true, 'count' => count($data), 'data' => $data]);
    }

    /** POST blocks { code } */
    public function store(Request $request)
    {
        $request->validate(['code' => 'required|integer|exists:users,code']);

        $me = Auth::user()->code;
        $target = (int) $request->code;

        if ($target === (int) $me) {
            return response()->json(['success' => false, 'message' => 'You can\'t block yourself.'], 422);
        }

        // platform restriction (e.g. a regular user cannot block an admin) is enforced here, not only in the UI
        $targetRole = DB::table('users')->where('code', $target)->value('role_code');
        if ($why = BlockService::restriction(Auth::user()->role_code, $targetRole)) {
            return response()->json(['success' => false, 'message' => $why], 403);
        }

        $name = DB::table('users')->where('code', $target)->value('fullname');
        $already = BlockService::blockedByMe($me, $target);

        BlockService::block($me, $target);

        return response()->json([
            'success' => true,
            'message' => $already ? "{$name} is already blocked." : "You blocked {$name}.",
            'data' => ['code' => $target, 'fullname' => $name],
        ]);
    }

    /** DELETE blocks/{code} */
    public function destroy($code)
    {
        $me = Auth::user()->code;

        if (!BlockService::unblock($me, (int) $code)) {
            return response()->json(['success' => false, 'message' => 'This account is not in your blocked list.'], 404);
        }

        $name = DB::table('users')->where('code', $code)->value('fullname');

        return response()->json(['success' => true, 'message' => "You unblocked {$name}."]);
    }

    /**
     * GET blocks/status/{code} : used by profile pages and menus.
     * `blocked_by_me` lets the UI show Block or Unblock. `restricted` is true when a block exists in
     * EITHER direction; it never says who blocked whom.
     */
    public function status($code)
    {
        $me = Auth::user()->code;

        return response()->json([
            'success' => true,
            'blocked_by_me' => BlockService::blockedByMe($me, $code),
            'restricted' => BlockService::isBlocked($me, $code),
            'can_block' => BlockService::restriction(
                Auth::user()->role_code,
                DB::table('users')->where('code', $code)->value('role_code')
            ) === null,
        ]);
    }
}
