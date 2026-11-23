<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Userprofile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Settings > Account Information: DEF-ADMIN and DEF-MASTERADMIN edit their own account details.
 *
 *  - only those two roles; everybody else keeps the read-only view (403 here whatever the UI shows)
 *  - there is no user id in the route: the caller can only ever change their own account
 *  - the role and the account code are never editable (that would let anyone promote themselves)
 *  - changing the sign-in email needs the current password, and the new email must be unused
 *  - the same values are written to every table that holds a copy (users, resources, userprofiles.email)
 */
class AccountDetailsController extends Controller
{
    public const ROLES = ['DEF-ADMIN', 'DEF-MASTERADMIN'];

    public function update(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->role_code, self::ROLES, true)) {
            return response()->json(['success' => false, 'message' => 'Only administrators can edit these details.'], 403);
        }

        $data = $request->validate([
            'fname'           => 'required|string|max:255',
            'lname'           => 'required|string|max:255',
            'mname'           => 'nullable|string|max:255',
            'email'           => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'profession'      => 'nullable|string|max:255',
            'company'         => 'nullable|string|max:255',
            'companywebsite'  => 'nullable|string|max:255',
            'current_password' => 'nullable|string',
        ], [
            'email.unique' => 'That email is already used by another account.',
        ]);

        $emailChanged = strcasecmp(trim($data['email']), (string) $user->email) !== 0;
        if ($emailChanged && !Hash::check((string) ($data['current_password'] ?? ''), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Enter your current password to change your sign-in email.',
                'errors'  => ['current_password' => ['Enter your current password to change your sign-in email.']],
            ], 422);
        }

        $fname = trim($data['fname']);
        $lname = trim($data['lname']);
        $mname = trim((string) ($data['mname'] ?? ''));
        $fullname = trim($fname . ' ' . ($mname !== '' ? $mname . ' ' : '') . $lname);
        $email = trim($data['email']);
        $now = now();

        DB::transaction(function () use ($user, $data, $fname, $lname, $mname, $fullname, $email, $now) {
            DB::table('users')->where('id', $user->id)->update([
                'fname' => $fname, 'lname' => $lname, 'mname' => $mname, 'fullname' => $fullname,
                'email' => $email, 'company' => $data['company'] ?? null, 'updated_at' => $now,
            ]);

            DB::table('resources')->where('code', $user->code)->update([
                'fname' => $fname, 'lname' => $lname, 'mname' => $mname, 'fullname' => $fullname, 'email' => $email,
                'profession' => $data['profession'] ?? null, 'company' => $data['company'] ?? null,
                'companywebsite' => $data['companywebsite'] ?? null, 'updated_at' => $now,
            ]);

            // the basic profile keeps a copy of the email
            Userprofile::where('code', $user->code)->update(['email' => $email, 'updated_at' => $now]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Account details saved.',
            'data'    => DB::table('resources')->where('code', $user->code)->first(),
        ]);
    }
}
