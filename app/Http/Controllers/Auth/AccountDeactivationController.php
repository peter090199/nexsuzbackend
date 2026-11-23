<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Settings > Deactivation. A job seeker or recruiter pauses their own account.
 *
 *  - status 'D' (deactivated). 'A' = active, 'I' = email not activated yet: 'D' is separate so the email activation
 *    flow can never be used to bring a deactivated account back by accident.
 *  - nothing is deleted: profile, CV, posts, connections and the subscription stay exactly as they are
 *  - searches and chat lists only include status 'A', so the account disappears from them
 *  - every sign-in session is ended; signing in again (LoginController) reactivates the account
 *  - the caller can only ever deactivate their own account (there is no user id in the route) and must type the password
 *  - admins and master admins cannot use it: they must not be able to lock the system out of its own administrators
 */
class AccountDeactivationController extends Controller
{
    public const ROLES = ['DEF-USERS', 'DEF-CLIENT'];

    public function deactivate(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        $user = Auth::user();

        if (!in_array($user->role_code, self::ROLES, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Administrator accounts cannot be deactivated here.',
            ], 403);
        }

        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The password is incorrect.',
                'errors'  => ['password' => ['The password is incorrect.']],
            ], 422);
        }

        $user->status = 'D';
        $user->is_online = false;
        $user->save();
        $user->tokens()->delete();   // ends this session and every other device

        return response()->json([
            'success' => true,
            'message' => 'Your account has been deactivated. Sign in again any time to reactivate it.',
        ]);
    }
}
