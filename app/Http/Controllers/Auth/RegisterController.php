<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Resource;
use App\Mail\Registeractivation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\PlanService;



class RegisterController extends Controller
{
    public function register(Request $request)
    {
        try {
            DB::beginTransaction();

            // ✅ Validation rules (no default assignment here)
            $validator = Validator::make($request->all(), [
                'fname' => 'required|string|max:255',
                'lname' => 'required|string|max:255',
                'contactno' => 'nullable|string|max:15',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|confirmed|min:8',
                'company' => 'nullable|string|max:255',
                'industry' => 'nullable|string|max:255',
                'companywebsite' => 'nullable|string|max:255',
                'designation' => 'nullable|string|max:255',
                'age' => 'nullable|integer|min:1|max:150',
                'profession' => 'nullable|string|max:255',
                'statuscode' => 'required|integer|in:0,1',
                'coverphoto' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->all(),
                ]);
            }

            // ✅ Generate unique user code
            $lastCode = User::max('code');
            $newCode = empty($lastCode) ? 701 : $lastCode + 1;

            // ✅ Create User
            $user = User::create([
                'fname' => $request->fname,
                'lname' => $request->lname,
                'mname' => '',
                'fullname' => ucfirst($request->fname . ' ' . $request->lname),
                'contactno' => $request->contactno,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'company' => $request->company,
                'code' => $newCode,
                'role_code' => $request->statuscode == 0 ? 'DEF-USERS' : 'DEF-CLIENT',
            ]);
            // ✅ Automatically assign Free Plan
            PlanService::ensureStarterPlan((string) $newCode, $user->role_code);
            // ✅ Create Resource (add default coverphoto)
            Resource::create([
                'code' => $newCode,
                'fname' => $request->fname,
                'lname' => $request->lname,
                'mname' => '',
                'fullname' => ucfirst($request->fname . ' ' . $request->lname),
                'contact_no' => $request->contactno,
                'age' => $request->age,
                'email' => $request->email,
                'profession' => $request->profession,
                'company' => $request->company,
                'industry' => $request->industry,
                'companywebsite' => $request->companywebsite,
                'role_code' => $request->statuscode == 0 ? 'DEF-USERS' : 'DEF-CLIENT',
                'designation' => $request->designation,
                'coverphoto' => $request->coverphoto ?? 'default.jpg', // ✅ FIXED HERE
            ]);

            // ✅ Generate verification code
            $verificationCode = Str::random(7);

            DB::insert('INSERT INTO email_codes (email, code) VALUES (?, ?)', [
                $request->email,
                $verificationCode,
            ]);

            // The account is saved first. A mail server problem must not undo a valid registration or show the user a
            // raw SMTP error: the email is sent after the commit and the user can ask for it again.
            DB::commit();

            $sent = $this->sendActivationEmail($request->fname, $request->email, $verificationCode);

            return response()->json([
                'success' => true,
                'email_sent' => $sent,
                'message' => $sent
                    ? "You have registered successfully. Please check your email to activate your account."
                    : "You have registered successfully, but we could not send the activation email right now. Use \"Resend activation email\" on the activation page in a few minutes, or contact support.",
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /** Send the activation email. Never throws: returns false (and logs why, without secrets) when it cannot be sent. */
    private function sendActivationEmail(string $fname, string $email, string $code): bool
    {
        try {
            Mail::to($email)->send(new Registeractivation(['fname' => $fname, 'email' => $email, 'code' => $code]));

            return true;
        } catch (\Throwable $e) {
            Log::warning('Activation email could not be sent', ['to_domain' => substr(strrchr($email, '@') ?: '', 1), 'reason' => mb_substr($e->getMessage(), 0, 200)]);

            return false;
        }
    }

    /**
     * POST resend-activation { email }
     * Issues a fresh code (the old ones are removed) and emails it. The answer is always the same generic message, so the
     * endpoint cannot be used to find out which addresses have accounts. Rate limited per address and IP.
     */
    public function resendActivation(Request $request)
    {
        $data = $request->validate(['email' => 'required|string|email|max:255']);
        $generic = ['success' => true, 'message' => 'If that address has an account waiting for activation, a new activation email is on its way.'];

        $user = User::where('email', $data['email'])->first();
        if (!$user || $user->status === 'A') {
            return response()->json($generic);
        }

        $code = Str::random(7);
        DB::transaction(function () use ($user, $code) {
            DB::table('email_codes')->where('email', $user->email)->delete();
            DB::insert('INSERT INTO email_codes (email, code) VALUES (?, ?)', [$user->email, $code]);
        });
        $this->sendActivationEmail((string) $user->fname, $user->email, $code);

        return response()->json($generic);
    }

    public function registerxx(Request $request)
    {
        try {
            // Begin Transaction
            DB::beginTransaction();

            // Validate the request data
            $validator = Validator::make($request->all(), [
                'fname' => 'required|string|max:255',
                'lname' => 'required|string|max:255',
                'contactno' => 'nullable|string|max:15',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|confirmed|min:8',
                'company' => 'nullable|string|max:255',
                'industry' => 'nullable|string|max:255',
                'companywebsite' => 'nullable',
                'designation' => 'nullable|string|max:255',
                'age' => 'nullable|integer|min:1|max:150',
                'profession' => 'nullable|string|max:255',
                'statuscode' => 'required|integer|in:0,1',
                'coverphoto' => $request->coverphoto ?? 'default.jpg',
            ]);

            // Check for validation errors
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->all()
                ]);
            }

            // Generate a new user code
            $lastCode = User::max('code');
            $newCode = empty($lastCode) ? 701 : $lastCode + 1;

            // Create the user
            $user = User::create([
                'fname' => $request->fname,
                'lname' => $request->lname,
                'mname' => '',
                'contactno' => $request->contactno,
                'fullname' => ucfirst($request->fname . ' ' . $request->lname),
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'company' => $request->company,
                'code' => $newCode,
                'role_code' => $request->statuscode == 0 ? 'DEF-USERS' : 'DEF-CLIENT',
            ]);

            // Create the resource
            Resource::create([
                'code' => $newCode,
                'fname' => $request->fname,
                'lname' => $request->lname,
                'mname' => '',
                'fullname' => ucfirst($request->fname . ' ' . $request->lname),
                'contact_no' => $request->contactno,
                'age' => $request->age,
                'email' => $request->email,
                'profession' => $request->profession,
                'company' => $request->company,
                'industry' => $request->industry,
                'companywebsite' => $request->companywebsite,
                'role_code' => $request->statuscode == 0 ? 'DEF-USERS' : 'DEF-CLIENT',
                'designation' => $request->designation,
            ]);

            // Generate token and code for email verification
            $token = Str::random(10);
            $verificationCode = Str::random(7);

            // Insert the password reset token
            DB::insert('INSERT INTO email_codes (email, code) VALUES (?, ?)', [
                $request->email,
                $verificationCode,
            ]);

            // Prepare email data
            $data = [
                'fname' => $request->fname,
                'email' => $request->email,
                'code' => $verificationCode,
            ];

            // Send the activation email
            Mail::to($request->email)->send(new Registeractivation($data));

            // Commit transaction
            DB::commit();

            // Return a success response
            return response()->json([
                'success' => true,
                'message' => "You have registered successfully. Please check your email to activate your account.",
            ], 201);
        } catch (\Throwable $th) {
            // Rollback transaction and return error
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function accountactivation(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validate the request data
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
                'code' => 'required|string',
            ]);

            // Check for validation errors
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->all(),
                ]);
            }

            // Check if the record exists
            $exists = DB::table('email_codes')
                ->where('email', $request->email)
                ->where('code', $request->code)
                ->exists();

            if ($exists) {
                // Activate the user account
                $user = User::where('email', $request->email)->first();
                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User not found.',
                    ]);
                }
                $user->status = 'A';
                $user->save();

                // Delete the token record
                DB::table('email_codes')
                    ->where('email', $request->email)
                    ->where('code', $request->code)
                    ->delete();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Account has been activated successfully.',
                ]);
            } else {
                // If no record exists, return an error response
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or code.',
                ]);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }
}

// register POST

// DEF-USERS
// {
//     "fname": "rein june",
//     "lname": "ediral",
//     "contactno": "1234567890",
//     "email": "reinjunelaride34@gmail.com",
//     "password": "123123123",
//     "password_confirmation": "123123123",
//     "age": 25,
//     "profession": "Developer",
//     "statuscode": 0
// }

// DEF-CLIENTS
// {
//     "fname": "John", //FIRSTNAME
//     "lname": "Doe", //LNAME
//     "contactno": "1234567890", //CONTACT NO
//     "email": "john.doe@example.com", //EMAIL
//     "password": "password123", //PASSWORD
//     "password_confirmation": "password123", //PASSWORD
//     "company": "ABC Corp.", //COMPANY
//     "industry": "Technology", //INDUSTRY
//     "companywebsite": "https://www.abccorp.com", //COMPANY WEBSITE
//    "designation": "Software Engineer",
//     "statuscode": 1
// }
