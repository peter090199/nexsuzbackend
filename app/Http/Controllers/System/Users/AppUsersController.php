<?php

namespace App\Http\Controllers\System\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DataResetService;
use App\Services\DatabaseAdminService;
use App\Services\UserDeletionService;
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
    public function deletePreview(UserDeletionService $deletion, $code)
    {
        $target = User::where('code', $code)->first();
        if (!$target) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }
        $blocked = $deletion->blocker($target, Auth::user());

        return response()->json(['success' => true, 'data' => [
            'user' => ['code' => $target->code, 'fullname' => $target->fullname, 'email' => $target->email, 'role_code' => $target->role_code],
            'can_delete' => $blocked === null,
            'reason' => $blocked,
        ] + ($blocked === null ? $deletion->preview($target) : [])]);
    }

    /**
     * DELETE users/{code}  { password, confirm (the account's email), delete_files? }
     * Removes the account and everything it created (profile and CV, posts, comments, messages, jobs, applications,
     * subscriptions, sign-in tokens). It cannot be undone, so: master admin only, the master admin's password, the
     * account's email typed back, and a database backup (or one made in the last 10 minutes) is in place first.
     */
    public function destroy(Request $request, UserDeletionService $deletion, DatabaseAdminService $db, $code)
    {
        $me = Auth::user();
        $target = User::where('code', $code)->first();
        if (!$target) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }
        if ($blocked = $deletion->blocker($target, $me)) {
            return response()->json(['success' => false, 'message' => $blocked], 422);
        }

        $data = $request->validate([
            'password' => ['required', 'string'],
            'confirm' => ['required', 'string'],
        ]);
        if (trim($data['confirm']) !== 'DELETE') {
            return response()->json(['success' => false, 'message' => 'Type DELETE exactly to confirm.'], 422);
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

        try {
            $result = $deletion->delete($target, $me, $backup['name']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'The delete failed part-way. Your safety backup is ' . $backup['name'] . '.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => "{$target->fullname} and all of their data were deleted.",
            'backup' => $backup + ['reused' => (bool) $reused],
            'data' => ['rows' => $result['rows'], 'anonymized' => $result['anonymized'], 'files' => $result['files_queued'], 'audit_id' => $result['audit_id']],
        ]);
    }

    // ---------------------------------------------------------------- edit an account's details

    /**
     * PATCH users/{code}  { fname, lname, email, contactno?, company? }   (DEF-MASTERADMIN only)
     * Changes the contact details of an account. It can NOT change the role, status, password or code: those fields are
     * not read at all, so a request that sends them has no effect on them.
     */
    public function update(Request $request, $code)
    {
        $me = Auth::user();
        if (!$me || $me->role_code !== 'DEF-MASTERADMIN') {
            return response()->json(['success' => false, 'message' => 'Only a master administrator can do this.'], 403);
        }
        $target = User::where('code', $code)->first();
        if (!$target) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        $data = $request->validate([
            'fname' => ['required', 'string', 'max:255'],
            'lname' => ['required', 'string', 'max:255'],
            'contactno' => ['nullable', 'string', 'max:15'],
            'email' => ['required', 'string', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($target->id)],
            'company' => ['nullable', 'string', 'max:255'],
        ]);
        $fullname = ucfirst($data['fname'] . ' ' . $data['lname']);

        try {
            DB::transaction(function () use ($target, $data, $fullname) {
                $target->forceFill([
                    'fname' => $data['fname'], 'lname' => $data['lname'], 'fullname' => $fullname,
                    'contactno' => $data['contactno'] ?? null, 'email' => $data['email'], 'company' => $data['company'] ?? null,
                ])->save();
                // the profile row mirrors these fields (see sign-up)
                DB::table('resources')->where('code', $target->code)->update([
                    'fname' => $data['fname'], 'lname' => $data['lname'], 'fullname' => $fullname,
                    'contact_no' => $data['contactno'] ?? null, 'email' => $data['email'], 'company' => $data['company'] ?? null,
                ]);
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'The account could not be updated.'], 500);
        }
        Log::info('users: details edited', ['by' => $me->code, 'code' => $target->code]);

        return response()->json(['success' => true, 'message' => "{$fullname} was updated.", 'data' => [
            'code' => $target->code, 'fullname' => $fullname, 'email' => $data['email'], 'role_code' => $target->role_code, 'status' => $target->status,
        ]]);
    }

    // ---------------------------------------------------------------- add a DEF-ADMIN account

    /**
     * POST users/admin  { fname, lname, email, password, password_confirmation, contactno?, company? }
     * Creates a DEF-ADMIN account. DEF-MASTERADMIN only (route middleware + the check below). The role is always
     * DEF-ADMIN: a role in the request is ignored unless it is DEF-ADMIN, and anything else (DEF-MASTERADMIN included)
     * is rejected, so this endpoint can never create another master admin or a job seeker / recruiter.
     */
    public function storeAdmin(Request $request)
    {
        $me = Auth::user();
        if (!$me || $me->role_code !== 'DEF-MASTERADMIN') {
            return response()->json(['success' => false, 'message' => 'Only a master administrator can do this.'], 403);
        }

        $data = $request->validate([
            'fname' => ['required', 'string', 'max:255'],
            'lname' => ['required', 'string', 'max:255'],
            'contactno' => ['nullable', 'string', 'max:15'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
            'company' => ['nullable', 'string', 'max:255'],
            'role_code' => ['nullable', \Illuminate\Validation\Rule::in(['DEF-ADMIN'])],
        ], ['role_code.in' => 'Only the DEF-ADMIN role can be assigned here.']);

        try {
            $user = DB::transaction(function () use ($data) {
                // same code scheme as sign-up; lock the last row so two creations cannot take the same code
                $last = DB::table('users')->orderByDesc('code')->lockForUpdate()->value('code');
                $code = empty($last) ? 701 : $last + 1;
                $fullname = ucfirst($data['fname'] . ' ' . $data['lname']);

                $user = User::create([
                    'fname' => $data['fname'], 'lname' => $data['lname'], 'mname' => '', 'fullname' => $fullname,
                    'contactno' => $data['contactno'] ?? null, 'email' => $data['email'], 'password' => Hash::make($data['password']),
                    'company' => $data['company'] ?? null, 'code' => $code,
                    'role_code' => 'DEF-ADMIN',            // fixed here, never taken from the request
                    'status' => 'A',                       // created by a master admin: no email activation step
                ]);
                \App\Models\Resource::create([
                    'code' => $code, 'fname' => $data['fname'], 'lname' => $data['lname'], 'mname' => '', 'fullname' => $fullname,
                    'contact_no' => $data['contactno'] ?? null, 'email' => $data['email'], 'company' => $data['company'] ?? null,
                    'role_code' => 'DEF-ADMIN', 'coverphoto' => 'default.jpg',
                ]);
                return $user;
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'The admin account could not be created.'], 500);
        }
        Log::info('users: admin created', ['by' => $me->code, 'code' => $user->code]);

        return response()->json([
            'success' => true,
            'message' => "{$user->fullname} was added as DEF-ADMIN.",
            'data' => ['code' => $user->code, 'fullname' => $user->fullname, 'email' => $user->email, 'role_code' => 'DEF-ADMIN'],
        ], 201);
    }
}
