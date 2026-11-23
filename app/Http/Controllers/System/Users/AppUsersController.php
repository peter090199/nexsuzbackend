<?php

namespace App\Http\Controllers\System\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DataResetService;
use App\Services\DatabaseAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AppUsersController extends Controller
{
    /** A recent backup is reused for a delete so removing several accounts does not write a backup each time. */
    private const BACKUP_REUSE_SECONDS = 600;

    /** account types that may see the Users table */
    private const VIEWERS = ['DEF-ADMIN', 'DEF-MASTERADMIN'];

    /**
     * GET getUsers : the Users table.
     * Only admins may read it, and it never returns password hashes or remember tokens.
     * `photo_pic` is the profile photo (media URLs are normalised centrally by NormalizeMediaUrls).
     */
    public function getUsers()
    {
        if (!in_array(Auth::user()->role_code, self::VIEWERS, true)) {
            return response()->json(['success' => false, 'message' => 'You are not allowed to view users.'], 403);
        }

        $users = DB::table('users')
            ->leftJoin('userprofiles', 'userprofiles.code', '=', 'users.code')
            ->orderBy('users.id')
            ->get([
                'users.id', 'users.code', 'users.fname', 'users.lname', 'users.mname', 'users.fullname',
                'users.email', 'users.contactno', 'users.company', 'users.status', 'users.is_online',
                'users.role_code', 'users.planId', 'users.coverphoto', 'users.created_at', 'users.updated_at',
                'userprofiles.photo_pic',
            ]);

        return response()->json(['success' => true, 'data' => $users]);
    }

    /**
     * PATCH users/{code}/status { status: 'A' | 'I' }   (route is DEF-MASTERADMIN only)
     *
     * 'I' = inactive: the account can't sign in and any open session is ended. 'A' = active again.
     * Nothing is deleted. Safety rules: you can't deactivate yourself, and the last active master admin
     * can't be deactivated (that would lock everyone out of administration).
     */
    public function setStatus(Request $request, $code)
    {
        $request->validate(['status' => 'required|in:A,I']);

        $me = Auth::user();
        $target = User::where('code', $code)->first();

        if (!$target) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        $status = $request->status;

        if ($status === 'I') {
            if ((string) $target->code === (string) $me->code) {
                return response()->json(['success' => false, 'message' => 'You can\'t deactivate your own account.'], 422);
            }

            if ($target->role_code === 'DEF-MASTERADMIN') {
                $otherActive = User::where('role_code', 'DEF-MASTERADMIN')
                    ->where('status', 'A')
                    ->where('code', '!=', $target->code)
                    ->exists();

                if (!$otherActive) {
                    return response()->json(['success' => false, 'message' => 'You can\'t deactivate the last active master admin.'], 409);
                }
            }
        }

        DB::transaction(function () use ($target, $status) {
            $target->status = $status;
            if ($status === 'I') {
                $target->is_online = 0;
            }
            $target->save();

            if ($status === 'I') {
                $target->tokens()->delete();   // end every open session at once
            }
        });

        return response()->json([
            'success' => true,
            'message' => $status === 'I'
                ? "{$target->fullname} was deactivated."
                : "{$target->fullname} was activated.",
            'data' => ['code' => $target->code, 'status' => $status],
        ]);
    }

    // ---------------------------------------------------------------- delete an account and everything it made

    /** GET users/{code}/delete-preview : what deleting this account would remove. Read-only. */
    public function deletePreview(DataResetService $reset, $code)
    {
        $target = User::where('code', $code)->first();
        if (!$target) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }
        $blocked = $reset->deleteBlocker($target, Auth::user());

        return response()->json(['success' => true, 'data' => [
            'user' => ['code' => $target->code, 'fullname' => $target->fullname, 'email' => $target->email, 'role_code' => $target->role_code],
            'can_delete' => $blocked === null,
            'reason' => $blocked,
        ] + ($blocked === null ? $reset->previewForUser($target) : [])]);
    }

    /**
     * DELETE users/{code}  { password, confirm (the account's email), delete_files? }
     * Removes the account and everything it created (profile and CV, posts, comments, messages, jobs, applications,
     * subscriptions, sign-in tokens). It cannot be undone, so: master admin only, the master admin's password, the
     * account's email typed back, and a database backup (or one made in the last 10 minutes) is in place first.
     */
    public function destroy(Request $request, DataResetService $reset, DatabaseAdminService $db, $code)
    {
        $me = Auth::user();
        $target = User::where('code', $code)->first();
        if (!$target) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }
        if ($blocked = $reset->deleteBlocker($target, $me)) {
            return response()->json(['success' => false, 'message' => $blocked], 422);
        }

        $data = $request->validate([
            'password' => ['required', 'string'],
            'confirm' => ['required', 'string'],
            'delete_files' => ['boolean'],
        ]);
        if (strcasecmp(trim($data['confirm']), (string) $target->email) !== 0) {
            return response()->json(['success' => false, 'message' => 'Type the account\'s email address exactly to confirm.'], 422);
        }
        if (!Hash::check($data['password'], (string) $me->password)) {
            return response()->json(['success' => false, 'message' => 'Your password is incorrect.'], 422);
        }

        // no backup, no delete (a backup from the last few minutes counts)
        try {
            $newest = $db->listBackups()[0] ?? null;
            $reused = $newest && (time() - strtotime($newest['created_at'])) <= self::BACKUP_REUSE_SECONDS;
            $backup = $reused ? $newest : $db->createBackup();
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Nothing was deleted because the safety backup could not be created.'], 500);
        }

        Log::info('users: delete started', ['by' => $me->code, 'target' => $target->code, 'role' => $target->role_code, 'backup' => $backup['name']]);
        try {
            $result = $reset->purge((bool) ($data['delete_files'] ?? false), $reset->doomedForUser($target));
        } catch (\Throwable $e) {
            report($e);
            Log::error('users: delete failed', ['by' => $me->code, 'target' => $target->code, 'backup' => $backup['name'], 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'The delete failed part-way. Your safety backup is ' . $backup['name'] . '.'], 500);
        }
        Log::info('users: delete finished', ['by' => $me->code, 'target' => $target->code, 'rows' => $result['rows'], 'files' => $result['files']]);

        return response()->json([
            'success' => true,
            'message' => "{$target->fullname} and all of their data were deleted.",
            'backup' => $backup + ['reused' => (bool) $reused],
            'data' => ['rows' => $result['rows'], 'files' => $result['files']],
        ]);
    }
}
