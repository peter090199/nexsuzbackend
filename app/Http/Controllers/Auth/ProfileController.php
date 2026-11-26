<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\MediaUrl;
use App\Helpers\ProfilePrivacy;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Resource;
use App\Models\Usercapabilitie;
use App\Models\Usereducation;
use App\Models\Userprofile;
use App\Models\Userseminar;
use App\Models\Usertraining;
use App\Models\Userskill;
use App\Models\Useremploymentrecord;
use App\Models\Usercertificate;
use Illuminate\Support\Facades\File; 
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;




class ProfileController extends Controller
{
    // ------------------------------------------------------------------
    //  Profile photo and cover photo (own account only)
    //
    //  The browser sends the already cropped image as multipart/form-data. Files live on the
    //  public disk under uploads/{code}/cvphoto|coverphoto/{uuid}/; the existing columns
    //  (userprofiles.photo_pic, users.coverphoto, resources.coverphoto) keep the URL.
    // ------------------------------------------------------------------

    private const IMAGE_RULES = 'required|file|mimes:jpeg,jpg,png,gif,webp|max:5120';

    private function imageMessages(): array
    {
        return [
            'photo.mimes'            => 'The photo must be a JPG, PNG, GIF or WEBP image.',
            'photo.image'            => 'The photo must be an image.',
            'photo.max'              => 'The photo must be 5 MB or smaller.',
            'photo.uploaded'         => 'The photo could not be uploaded. It may be larger than the server allows.',
            'coverphoto.mimes'       => 'The cover photo must be a JPG, PNG, GIF or WEBP image.',
            'coverphoto.image'       => 'The cover photo must be an image.',
            'coverphoto.max'         => 'The cover photo must be 5 MB or smaller.',
            'coverphoto.uploaded'    => 'The cover photo could not be uploaded. It may be larger than the server allows.',
        ];
    }

    private function imageValidationFailed($validator)
    {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors'  => $validator->errors(),
        ], 422);
    }

    /** Write the file under uploads/{code}/{kind}/{uuid}/ and return its URL on this server. */
    private function storeProfileImage($file, $userCode, string $kind): string
    {
        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        $folder = "uploads/{$userCode}/{$kind}/" . Str::uuid();
        $name = time() . '.' . $ext;
        $file->storeAs($folder, $name, 'public');

        return url("storage/{$folder}/{$name}");
    }

    /** Delete the folder holding a stored image, but ONLY if it is one of this user's own photo folders. */
    private function deleteOwnImageFolder(?string $stored, $userCode, string $kind): void
    {
        $relative = MediaUrl::relativePath($stored);
        $prefix = "uploads/{$userCode}/{$kind}/";

        if ($relative && str_starts_with($relative, $prefix) && dirname($relative) !== rtrim($prefix, '/')) {
            Storage::disk('public')->deleteDirectory(dirname($relative));
        }
    }

    /** POST uploadCoverPhoto  { coverphoto: image } */
    public function uploadCoverPhoto(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 401);
        }

        $validator = Validator::make($request->all(), ['coverphoto' => self::IMAGE_RULES], $this->imageMessages());
        if ($validator->fails()) {
            return $this->imageValidationFailed($validator);
        }

        $userCode = Auth::user()->code;
        $old = DB::table('users')->where('code', $userCode)->value('coverphoto');
        $url = $this->storeProfileImage($request->file('coverphoto'), $userCode, 'coverphoto');

        try {
            DB::beginTransaction();
            DB::table('users')->where('code', $userCode)->update(['coverphoto' => $url, 'updated_at' => now()]);
            DB::table('resources')->where('code', $userCode)->update(['coverphoto' => $url, 'updated_at' => now()]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->deleteOwnImageFolder($url, $userCode, 'coverphoto');   // do not leave the new upload behind
            report($e);

            return response()->json(['success' => false, 'message' => 'The cover photo could not be saved. Please try again.'], 500);
        }

        $this->deleteOwnImageFolder($old, $userCode, 'coverphoto');       // the replaced photo's file

        return response()->json([
            'success'    => true,
            'message'    => 'Cover photo uploaded successfully.',
            'coverphoto' => MediaUrl::normalize($url),
        ], 201);
    }

    /** POST removeCoverPhoto — back to "no cover" (the column's own default). */
    public function removeCoverPhoto()
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 401);
        }

        $userCode = Auth::user()->code;
        $old = DB::table('users')->where('code', $userCode)->value('coverphoto');

        DB::transaction(function () use ($userCode) {
            DB::table('users')->where('code', $userCode)->update(['coverphoto' => 'default.jpg', 'updated_at' => now()]);
            DB::table('resources')->where('code', $userCode)->update(['coverphoto' => 'default.jpg', 'updated_at' => now()]);
        });
        $this->deleteOwnImageFolder($old, $userCode, 'coverphoto');

        return response()->json(['success' => true, 'message' => 'Cover photo removed.', 'coverphoto' => null]);
    }

    /**
     * POST uploadProfilePhoto  { photo: image }
     * Changes ONLY the photo: unlike saveProfile it never touches the other profile fields.
     */
    public function uploadProfilePhoto(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 401);
        }

        $validator = Validator::make($request->all(), ['photo' => self::IMAGE_RULES], $this->imageMessages());
        if ($validator->fails()) {
            return $this->imageValidationFailed($validator);
        }

        $user = Auth::user();
        $userCode = $user->code;
        $old = UserProfile::where('code', $userCode)->value('photo_pic');
        $url = $this->storeProfileImage($request->file('photo'), $userCode, 'cvphoto');

        try {
            DB::beginTransaction();
            if (UserProfile::where('code', $userCode)->exists()) {
                UserProfile::where('code', $userCode)->update(['photo_pic' => $url, 'updated_at' => now()]);
            } else {
                UserProfile::insert([
                    'code'               => $userCode,
                    'transNo'            => (int) UserProfile::max('transNo') + 1,
                    'email'              => $user->email,
                    'contact_visibility' => false,
                    'email_visibility'   => false,
                    'photo_pic'          => $url,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->deleteOwnImageFolder($url, $userCode, 'cvphoto');
            report($e);

            return response()->json(['success' => false, 'message' => 'The photo could not be saved. Please try again.'], 500);
        }

        $this->deleteOwnImageFolder($old, $userCode, 'cvphoto');

        return response()->json([
            'success'   => true,
            'message'   => 'Profile photo updated successfully.',
            'photo_pic' => MediaUrl::normalize($url),
        ], 201);
    }

    /**
     * GET media?path=<stored url or uploads/... path>
     *
     * Streams an uploaded image through Laravel so the response carries the CORS headers
     * (files under /storage are served straight from disk and never get them). The browser needs
     * this to draw a photo into a canvas, e.g. when exporting the CV to PDF.
     * Only images under uploads/ on the public disk are served.
     */
    public function media(Request $request)
    {
        $relative = MediaUrl::relativePath((string) $request->query('path'));

        if (!$relative
            || !str_starts_with($relative, 'uploads/')
            || str_contains($relative, '..')
            || !preg_match('/\.(jpe?g|png|gif|webp|jfif)$/i', $relative)
            || !Storage::disk('public')->exists($relative)) {
            return response()->json(['success' => false, 'message' => 'Image not found.'], 404);
        }

        return response()->file(Storage::disk('public')->path($relative), [
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    /** POST removeProfilePhoto — the default avatar is shown again. */
    public function removeProfilePhoto()
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 401);
        }

        $userCode = Auth::user()->code;
        $old = UserProfile::where('code', $userCode)->value('photo_pic');

        UserProfile::where('code', $userCode)->update(['photo_pic' => null, 'updated_at' => now()]);
        $this->deleteOwnImageFolder($old, $userCode, 'cvphoto');

        return response()->json(['success' => true, 'message' => 'Profile photo removed.', 'photo_pic' => null]);
    }

    public function index()
    {
         if (Auth::check()) {

            $userprofile = Userprofile::where('code',Auth::user()->code)->get();


            $result =[];
            for($up = 0; $up<count($userprofile); $up++){
                $resources = Resource::select('fname','lname','email','profession')->where('code',Auth::user()->code)->get();
                
                 $result = [
                    "code" => Auth::user()->code,
                    "email" => $resources[0]->email,
                    "fname" => $resources[0]->fname,
                    "lname" => $resources[0]->lname,
                    "photo_pic" =>  $userprofile[$up]->photo_pic?? 'https://api.nexsuz.com/storage/uploads/DEFAULTPROFILE/DEFAULTPROFILE.png'  ,
                    "contact_no" => $userprofile[$up]->contact_no,
                    "contact_visibility" => $userprofile[$up]->contact_visibility,
                    "email_visibility" => $userprofile[$up]->email_visibility,
                    "summary" => $userprofile[$up]->summary,
                    "date_birth" => $userprofile[$up]->date_birth,
                    "home_country" => $userprofile[$up]->home_country,
                    "current_location" => $userprofile[$up]->current_location,
                    "home_state" => $userprofile[$up]->home_state,
                    "current_state" => $userprofile[$up]->current_state,
                     "profession" => $resources[0]->profession,
                    "lines" => [
                        "education" =>  Usereducation::select('highest_education','school_name','start_month','start_year','end_month','end_year','status')->where('code',Auth::user()->code)->get(),
                        "language"=>  Usercapabilitie::select('language')->where('code', Auth::user()->code)->Where('transNo', $userprofile[$up]->transNo)->get(),
                        "training" => Usertraining::select('training_title','training_provider','date_completed')->where('code', Auth::user()->code)->Where('transNo', $userprofile[$up]->transNo)->get(),
                        "seminar" =>  Userseminar::select('seminar_title','seminar_provider','date_completed')->where('code', Auth::user()->code)->Where('transNo', $userprofile[$up]->transNo)->get(),
                        "skills" => Userskill::select('skills')->where('code', Auth::user()->code)
                        ->Where('transNo', $userprofile[$up]->transNo)->get(),
                        "employment" => Useremploymentrecord::select('company_name','position','job_description','date_completed')->where('code', Auth::user()->code)
                        ->Where('transNo', $userprofile[$up]->transNo)->get(),
                        "certificate" =>  Usercertificate::where('code', Auth::user()->code)
                        ->Where('transNo', $userprofile[$up]->transNo)->get()
                    ]
                ];
            }

            return response()->json(['success' => true,'message' => $result]);
        } 
        else {
            return response()->json(['success'=>false,'message' => 'User is not authenticated']);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */

     public function store(Request $request)
     {
            // $data = $request->all();
        //  return response()->json(['testn' => $data]);
         // Check if the user is authenticated
         if (Auth::check()) {
             // Only DEF-USERS create a CV. Other roles may still update the contact / privacy fields of a
             // profile row that already exists (Settings uses this same endpoint), but they can never create
             // the profile row or send CV sections (education, skills, ...).
             if (!\App\Http\Middleware\EnsureCanCreateCv::allows(Auth::user())) {
                 $alreadyHasProfile = UserProfile::where('code', Auth::user()->code)->exists()
                     || \App\Http\Middleware\EnsureCanCreateCv::allowsProfileOnly(Auth::user());   // administrators may create the basic profile
                 if (!$alreadyHasProfile || $request->has('lines')) {
                     return response()->json([
                         'success' => false,
                         'message' => 'Your account type cannot create or edit a CV.',
                     ], 403);
                 }
             }

             try {
                 DB::beginTransaction();
                 $data = $request->all();
                 // Validate the request data for the user profile
                 $validator = Validator::make($data, [
                     //'photo_pic' => 'nullable|file|image|max:2048', // Validate file upload
                     'contact_no' => 'nullable|string|max:255',
                     'contact_visibility' => 'nullable|integer',
                     'email_visibility' => 'nullable|integer',
                     'summary' => 'nullable|string',
                     'date_birth' => 'nullable|date',
                     'home_country' => 'nullable|string|max:255',
                     'current_location' => 'nullable|string|max:255',
                 ]);
     
                 // Check for validation errors
                 if ($validator->fails()) {
                     return response()->json([
                         'success' => false,
                         'message' => $validator->errors()->all(),
                     ]);
                 }
     
                 $exist = UserProfile::where('code', Auth::user()->code)->exists();
     
                // Get the new transaction number
                $transNo = UserProfile::max('transNo');
                $newtrans = empty($transNo) ? 1 : $transNo + 1;
                $transNoToUse = $exist ? UserProfile::where('code', Auth::user()->code)->value('transNo') : $newtrans;


                if ($exist) {
                    UserProfile::where('code',Auth::user()->code)->update([
                        'code' => Auth::user()->code,
                        'transNo' => $transNoToUse,
                        'contact_no' => $data['contact_no'],
                        'contact_visibility' => $data['contact_visibility'],
                        'email' => Auth::user()->email,
                        'email_visibility' => $data['email_visibility'],
                        'summary' => $data['summary'],
                        'date_birth' => $data['date_birth'],
                        'home_country' => $data['home_country'],
                        'home_state' => $data['home_state'],
                        'current_location' => $data['current_location'],
                        'current_state' => $data['current_state']
                    ]);
                    // Replace the CV sections ONLY when the request actually carries sections (legacy payload).
                    // A plain profile update (Settings, CV builder steps) must not wipe existing CV records.
                    if ($request->has('lines')) {
                        Usereducation::where('code', Auth::user()->code)->delete();
                        Usercapabilitie::where('code', Auth::user()->code)->delete();
                        Usertraining::where('code', Auth::user()->code)->delete();
                        Userseminar::where('code', Auth::user()->code)->delete();
                        Userskill::where('code', Auth::user()->code)->delete();
                        Useremploymentrecord::where('code', Auth::user()->code)->delete();
                        Usercertificate::where('code', Auth::user()->code)->delete();
                    }
                }
                 else{
                    // $photoPath = null;
                   // if ($request->hasFile('photo_pic')) {
                    //    $userCode = Auth::user()->code;
                       // $file = $request//->file('photo_pic');
                        // Define the folder path based on the user's code
                     //   $folderPath = "uploads/{$userCode}/cvphoto";
                        // Store the file and get the stored path
                   //     $photoPath = $file->store($folderPath, 'public');
                    //}
                    UserProfile::create([
                        'code' => Auth::user()->code,
                        'transNo' => $transNoToUse,
                        // 'photo_pic' => $photoPath,
                        'contact_no' => $data['contact_no'],
                        'contact_visibility' => $data['contact_visibility'],
                        'email' => Auth::user()->email,
                        'email_visibility' => $data['email_visibility'],
                        'summary' => $data['summary'],
                        'date_birth' => $data['date_birth'],
                        'home_country' => $data['home_country'],
                        'home_state' => $data['home_state'],
                        'current_location' => $data['current_location'],
                        'current_state' => $data['current_state']
                    ]);

                 }
     
                 // Validate and insert capabilities (languages)
                 if (isset($data['lines']['language'])) {
                     foreach ($data['lines']['language'] as $language) {
                         Usercapabilitie::create([
                             'code' => Auth::user()->code,
                             'transNo' => $transNoToUse,
                             'language' => $language['language']
                         ]);
                     }
                 }
     
                 // Validate and insert education data
                 if (isset($data['lines']['education'])) {
                     foreach ($data['lines']['education'] as $education) {
                         Usereducation::create([
                             'code' => Auth::user()->code,
                             'transNo' => $transNoToUse,
                             'highest_education' => $education['highest_education'],
                             'school_name' => $education['school_name'],
                             'start_month' => $education['start_month'],
                             'start_year'=> $education['start_year'],
                             'end_month'=> $education['end_month'],
                             'end_year'=> $education['end_year'],
                             'status' => $education['status'],
                         ]);
                     }
                 }
     
                 if (isset($data['lines']['training'])) {
                     foreach ($data['lines']['training'] as $trainings) {
                         Usertraining::create([
                             'code' => Auth::user()->code,
                             'transNo' => $transNoToUse,
                             'training_title' => $trainings['training_title'],
                             'training_provider' => $trainings['training_provider'],
                             'date_completed' => $trainings['trainingdate'],
                         ]);
                     }
                 }
     
                 if (isset($data['lines']['seminar'])) {
                     foreach ($data['lines']['seminar'] as $seminar) {
                         Userseminar::create([
                             'code' => Auth::user()->code,
                             'transNo' => $transNoToUse,
                             'seminar_title' => $seminar['seminar_title'],
                             'seminar_provider' => $seminar['seminar_provider'],
                             'date_completed' => $seminar['seminardate'],
                         ]);
                     }
                 }
     
                 if (isset($data['lines']['skills'])) {
                     foreach ($data['lines']['skills'] as $skills) {
                         Userskill::create([
                             'code' => Auth::user()->code,
                             'transNo' => $transNoToUse,
                             'skills' =>  $skills['skills']
                         ]);
                     }
                 }
     
                 if (isset($data['lines']['employment'])) {
                     foreach ($data['lines']['employment'] as $employment) {
                         Useremploymentrecord::create([
                             'code' => Auth::user()->code,
                             'transNo' => $transNoToUse,
                             'company_name' => $employment['company_name'],
                             'position' => $employment['position'],
                             'job_description' => $employment['job_description'],
                             'date_completed' => $employment['date_completed'],
                         ]);
                     }
                 }
     
                 if (isset($data['lines']['certificate'])) {
                     foreach ($data['lines']['certificate'] as $certificate) {
                         Usercertificate::create([
                             'code' => Auth::user()->code,
                             'transNo' => $transNoToUse,
                             'certificate_title' => $certificate['certificate_title'],
                             'certificate_provider' => $certificate['certificate_provider'],
                             'date_completed' => $certificate['date_completed'],
                         ]);
                     }
                 }
     
                 // Update resource data
                 Resource::where('code', Auth::user()->code)
                     ->update([
                         'contact_no' => $data['contact_no'],
                         'date_birth' => $data['date_birth'],
                         'home_country' => $data['home_country'],
                         'current_location' => $data['current_location'],
                    ]);
                     
                    $msg = $exist ? 'updated' : 'saved';
                    // Commit the transaction if everything is successful
                    DB::commit();
                    // Return success response
                    return response()->json([
                        'success' => true,
                        'message' => "Profile and related information " . $msg . " successfully.",
                    ]);
             } catch (\Throwable $th) {
                 // Rollback transaction on error
                 DB::rollBack();
     
                 // Return error response
                 return response()->json([
                     'success' => false,
                     'message' => 'Error occurred: ' . $th->getMessage(),
                ], 500);
            }
        }
     
         // Return unauthorized response if user is not authenticated
         return response()->json([
             'success' => false,
             'message' => 'Unauthorized access.',
        ], 401);
    }
     

    /** @return array{has_resume:bool, count:int, default:?array{id:int,name:string,size:int,uploaded_at:?string}} */
    private function resumeSummary(int $code): array
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('user_resumes')) {
            return ['has_resume' => false, 'count' => 0, 'default' => null];
        }

        $rows = DB::table('user_resumes')->where('code', $code)->orderByDesc('is_default')->orderByDesc('id')->get();
        $default = $rows->first();     // the default one sorts first (or the newest, if none is marked)

        return [
            'has_resume' => $rows->isNotEmpty(),
            'count' => $rows->count(),
            'default' => $default ? [
                'id' => (int) $default->id, 'name' => $default->original_name,
                'size' => (int) $default->size, 'uploaded_at' => $default->created_at,
            ] : null,
        ];
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // a blocked account's profile is "not available" in both directions
        if (Auth::check() && \App\Services\BlockService::isBlocked(Auth::user()->code, $id)) {
            return response()->json(['success' => false, 'blocked' => true, 'message' => 'This profile is not available.'], 404);
        }

        try {
            $resource = Resource::where('code', $id)->get();
            
            // return $resource;
    
            $result = [];
            if ($resource) {  
                for ($r = 0; $r < count($resource); $r++) {
                   
                  
        
                     $userprofile = Userprofile::where('code', $resource[$r]->code)->first() ?? null;
                
                    // The profile table is what the CV import and Settings write; the resource row is the older copy
                    // made at sign up. Read the profile first, so what was imported shows up here straight away.
                    $contactEmail = ($userprofile->email ?? null) ?: $resource[$r]->email;
                    $contactNo = ($userprofile->contact_no ?? null) ?: $resource[$r]->contact_no;

                    $result= [
                        "role_code" => $resource[$r]->role_code,
                        // the "New Curriculum Vitae" button: own profile AND an account type that may create a CV
                        "btnCurriculum" => ($resource[$r]->code == Auth::user()->code
                            && \App\Http\Middleware\EnsureCanCreateCv::allows(Auth::user())) ? 1  : '',
                        "email" => $userprofile ? $contactEmail : null,
                        "fname" => $resource[$r]->fname,
                        "lname" => $resource[$r]->lname,
                        "photo_pic" => $userprofile->photo_pic ?? Storage::disk('public')->url('uploads/DEFAULTPROFILE/DEFAULTPROFILE.png'),
                        "contact_no" => $userprofile ? $contactNo : null,
                        "contact_visibility" => $userprofile->contact_visibility ??  null,
                        "email_visibility" => $userprofile->email_visibility ??  null,
                        "summary" => $userprofile->summary ?? null,
                        "date_birth" => ($userprofile->date_birth ?? null) ?: $resource[$r]->date_birth,
                        "home_country" => ($userprofile->home_country ?? null) ?: $resource[$r]->home_country,   // was returning the date of birth
                        "current_location" => ($userprofile->current_location ?? null) ?: $resource[$r]->current_location,
                        "home_state" => $userprofile->home_state??  null,
                        "current_state" => $userprofile->current_state??  null,
                        "profession" => $resource[$r]->profession,
                        "lines" => [
                            "education" => 
                            $userprofile ?  Usereducation::select('highest_education', 'school_name', 'start_month', 'start_year', 'end_month', 'end_year','status')
                                ->where('code', $id)->get() : 'null',
    
                            "language" => Usercapabilitie::select('language')->where('code', $id)->get() ??  'null',
                            
                            "training" => Usertraining::select('training_title', 'training_provider', 'date_completed')
                                ->where('code', $id)->get() ??  'null',
                                
                            "seminar" => Userseminar::select('seminar_title', 'seminar_provider', 'date_completed')
                                ->where('code', $id)->get() ??  'null',
                                
                                
                            "skills" => Userskill::select('skills')->where('code', $id)
                                ->get() ??  'null',
                            "employment" => Useremploymentrecord::select('company_name', 'position', 'job_description', 'date_completed')
                                ->where('code', $id)->get() ??  'null',
                            "certificate" => Usercertificate::where('code', $id)
                                ->get() ??  'null'
                        ]
                    ];
                }

                // Admin and master admin accounts are staff, not job seekers or recruiters: they have no CV, so none is shown
                $result['cv_available'] = !in_array($result['role_code'] ?? null, ['DEF-ADMIN', 'DEF-MASTERADMIN'], true);
                if (!$result['cv_available']) {
                    $result['summary'] = null;
                    $result['date_birth'] = null;
                    $result['home_country'] = null;
                    $result['lines'] = ['education' => [], 'language' => [], 'training' => [], 'seminar' => [], 'skills' => [], 'employment' => [], 'certificate' => []];
                }

                // How job seekers provide their CV (master admin's setting): 'builder' (fill up the form) or 'upload' (upload only)
                $result['cv_mode'] = \App\Services\CvModeService::mode();

                // The owner's uploaded resumes / CV, detected here (Settings > Resume / CV). They are personal data, so
                // only the owner of the profile gets them: everybody else sees `resume: null` and learns nothing.
                $isOwn = Auth::check() && (string) $id === (string) Auth::user()->code;
                $result['resume'] = ($isOwn && ($result['role_code'] ?? null) === 'DEF-USERS')
                    ? $this->resumeSummary((int) $id)
                    : null;

                $viewer = Auth::user()->code;
                $result = ProfilePrivacy::apply($result, $id, $viewer);
                $connections = ProfilePrivacy::connectionCodes($id);
                $result['connections_count'] = count($connections);
                $result['mutual_count'] = $isOwn ? 0 : count(array_intersect($connections, ProfilePrivacy::connectionCodes($viewer)));
                $result['user_id'] = (int) DB::table('users')->where('code', $id)->value('id');
                $result['company'] = $resource[0]->company ?? null;
                $result['industry'] = $resource[0]->industry ?? null;
                $result['companywebsite'] = $resource[0]->companywebsite ?? null;
                $result['share_url'] = rtrim((string) config('app.frontend_url'), '/') . '/p/' . (int) $id;

                return response()->json([
                    'success' => true,
                    'message' => $result,
                ]);
            } else {

                return response()->json([
                    'success' => false,
                    'message' => 'User not defined.',
                ]);
            }
    
        } catch (\Throwable $th) {
            // Return error response if something goes wrong
            return response()->json([
                'success' => false,
                'message' => 'Error occurred: ' . $th->getMessage(),
            ], 500);
        }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request, $id)
    {
        
    }


    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getProfileData()
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'User is not authenticated'
            ]);
        }

        $code = Auth::user()->code;

        $profile = UserProfile::where('code', $code)->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $profile->code,
                'transNo' => $profile->transNo ?? null,
                'contact_visibility' => (bool) $profile->contact_visibility,
                'email_visibility' => (bool) $profile->email_visibility,
                'date_birth' => $profile->date_birth,
                // stored value is already a full URL (normalized to this server by NormalizeMediaUrls)
                'photo_pic' => $profile->photo_pic ?: null,
                'home_state' => $profile->home_state,
                'current_state' => $profile->current_state,
                'home_country' => $profile->home_country,
                'current_location' => $profile->current_location,
                'summary' => $profile->summary,
                'created_at' => $profile->created_at,
                'updated_at' => $profile->updated_at,
            ],
            'message' => 'Profile loaded successfully.'
        ]);
    }

    public function userAuth() {
        if (Auth::check()) {
            $user = Resource::where('code', Auth::user()->code)->first();
            if ($user) {
                return response()->json([
                    'success' => true,
                    'message' => $user
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'User is not authenticated'
            ]);
        }
    }


    public function userAuthByCode($code)
    {
        if (Auth::check()) {
            if (\App\Services\BlockService::isBlocked(Auth::user()->code, $code)) {
                return response()->json(['success' => false, 'blocked' => true, 'message' => 'This profile is not available.'], 404);
            }

            $viewer = Auth::user()->code;
            $user = DB::table('resources')
                ->leftJoin('userprofiles', 'resources.code', '=', 'userprofiles.code')
                ->select('resources.code', 'resources.fname', 'resources.lname', 'resources.fullname', 'resources.profession',
                    'resources.company', 'resources.industry', 'resources.companywebsite', 'resources.designation', 'resources.role_code',
                    'resources.current_location', 'resources.coverphoto', 'resources.email', 'resources.contact_no',
                    'userprofiles.photo_pic', 'userprofiles.summary as about', 'userprofiles.contact_visibility', 'userprofiles.email_visibility')
                ->where('resources.code', $code)
                ->first();

            if ($user) {
                $own = (string) $code === (string) $viewer;
                if (!$own) {
                    $contactOk = ProfilePrivacy::can($code, $viewer, 'contact');
                    if (!$contactOk || $user->email_visibility == 1) { $user->email = null; }
                    if (!$contactOk || $user->contact_visibility == 1) { $user->contact_no = null; }
                    if (!ProfilePrivacy::can($code, $viewer)) {
                        $user->about = $user->current_location = $user->companywebsite = null;
                    }
                }
                unset($user->contact_visibility, $user->email_visibility);
                $user->followers = $user->following = count(ProfilePrivacy::connectionCodes($code));

                return response()->json([
                    'success' => true,
                    'message' => $user
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'User is not authenticated'
        ]);
    }

    // public function userAuthByCode($code) {
    //     if (Auth::check()) {
    //         $user = Resource::where('code', $code)->first();
    //         if ($user) {
    //             return response()->json([
    //                 'success' => true,
    //                 'message' => $user
    //             ]);
    //         }
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'User not found'
    //         ]);
    //     }

    //     return response()->json([
    //         'success' => false,
    //         'message' => 'User is not authenticated'
    //     ]);
    // }




    public function userAuthXX(){
        if (Auth::check()) {
            $user = Resource::where('code',Auth::user()->code)->get();
            // $result = [];
            // for($i = 0 ; $i < count($user); $i++){
            //     $result [] = [
            //         "fullname" => $user[$i]->fullname,
            //         "email" =>  $user[$i]->email,
            //         "fname" => $user[$i]->fname,
            //         "lname" => $user[$i]->lname,
            //         "code" => $user[$i]->code,
            //         "contact_no" => $user[$i]->contact_no,
            //         "profession" => $user[$i]->profession,
            //         "industry" => $user[$i]->industry,
            //         "companywebsite" =>  $user[$i]->companywebsite,
            //         "role_code"=>$user[$i]->role_code,
            //         "company"=>$user[$i]->company,
            //     ];
            // }         
            return response()->json(['success' => true,'message' => $user]);
        } 
        else {
            return response()->json(['success'=>false,'message' => 'User is not authenticated']);
        }
    }

    public function saveProfile(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 401);
        }

        // Only DEF-USERS create a CV. Other roles may still update the contact / privacy fields of a profile
        // row that already exists (Settings uses this endpoint) but can never create the profile row.
        // Administrators are the exception: they may create the basic profile row (no photo, no CV data required).
        $profileOnly = \App\Http\Middleware\EnsureCanCreateCv::allowsProfileOnly(Auth::user());
        if (!\App\Http\Middleware\EnsureCanCreateCv::allows(Auth::user())
            && !$profileOnly
            && !UserProfile::where('code', Auth::user()->code)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Your account type cannot create or edit a CV.',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $data = $request->all();

            $validator = Validator::make($data, [
                'contact_no' => 'nullable|string|max:255',
                'contact_visibility' => 'nullable|boolean',
                'email_visibility' => 'nullable|boolean',
                'date_birth' => 'nullable|string',
                'home_country' => 'nullable|string|max:255', // ✅ validate home_state
                'home_state' => 'nullable|string|max:255',
                'current_state' => 'nullable|string|max:255', // ✅ validate home_state
                'current_location' => 'nullable|string|max:255',
                'photo_pic' => 'nullable|file|image|max:2048',
                'summary' => 'nullable|string|max:255',

            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->all(),
                ]);
            }

            $userCode = Auth::user()->code;
            $email = Auth::user()->email;
            $now = now();
            $photoPath = null;
            $formattedDateBirth = null;
            if (!empty($data['date_birth'])) {
                try {
                    $formattedDateBirth = Carbon::createFromFormat('n/j/Y', $data['date_birth'])->format('Y-m-d');
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid date format. Please use MM/DD/YYYY format.',
                    ], 422);
                }
            }

            if ($request->hasFile('photo_pic')) {
                $file = $request->file('photo_pic');
                $uuid = Str::uuid();
                $folderPath = "uploads/{$userCode}/cvphoto/{$uuid}";
                $fileName = time() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs($folderPath, $fileName, 'public');
                $photoPath = url("storage/{$folderPath}/{$fileName}");
            }

            $contactVisibility = isset($data['contact_visibility']) ? (bool) $data['contact_visibility'] : false;
            $emailVisibility = isset($data['email_visibility']) ? (bool) $data['email_visibility'] : false;

            $exists = UserProfile::where('code', $userCode)->first();

            if ($exists) {
                if (!$exists->photo_pic && !$photoPath && !$profileOnly) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please select a profile photo to complete your profile.',
                    ], 404);
                }

                $updateData = [
                    'contact_no' => $data['contact_no'] ?? null,
                    'contact_visibility' => $contactVisibility,
                    'email' => $exists->email ?: $email,         // keep a contact email imported from a CV
                    'email_visibility' => $emailVisibility,
                    'date_birth' => $formattedDateBirth,
                    'home_state' => $data['home_state'] ?? null,
                    'home_country' => $data['home_country'] ?? null,
                    'current_state' => $data['current_state'] ?? null,
                    'current_location' => $data['current_location'] ?? null,
                    'summary' => $data['summary'] ?? null,
                    'updated_at' => $now,
                ];
                if ($photoPath) {
                    $updateData['photo_pic'] = $photoPath;
                }

                UserProfile::where('code', $userCode)->update($updateData);

            } else {
                if (!$photoPath && !$profileOnly) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please select a photo before saving your profile.',
                    ], 422);
                }

                $transNo = UserProfile::max('transNo');
                $newTrans = empty($transNo) ? 1 : $transNo + 1;

                UserProfile::insert([
                    'code' => $userCode,
                    'transNo' => $newTrans,
                    'contact_no' => $data['contact_no'] ?? null,
                    'contact_visibility' => $contactVisibility,
                    'email' => $email,
                    'email_visibility' => $emailVisibility,
                    'date_birth' => $formattedDateBirth,
                    'home_state' => $data['home_state'] ?? null,
                    'home_country' => $data['home_country'] ?? null,
                    'current_state' => $data['current_state'] ?? null,
                    'current_location' => $data['current_location'] ?? null,
                    'photo_pic' => $photoPath,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('resources')->where('code', $userCode)->update([
                'contact_no' => $data['contact_no'] ?? null,
                'date_birth' => $formattedDateBirth,
                'updated_at' => $now,
            ]);

            DB::commit();

            $profile = UserProfile::where('code', $userCode)->first();
            return response()->json([
                'success' => true,
                'message' => $exists ? 'Profile updated successfully.' : 'Profile created successfully.',
            ]);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error occurred: ' . $th->getMessage(),
            ], 500);
        }
    }


    public function getProfileByCode()
    {
        try {
            $code = Auth::user()->code;
            $profile = DB::table('userprofiles as u')
                ->leftJoin('resources as r', 'u.code', '=', 'r.code')
                ->where('u.code', $code)
                ->select(
                    'u.code',
                    'u.contact_no',
                    'u.contact_visibility',
                    'u.email',
                    'u.email_visibility',
                    'u.date_birth',
                    'u.photo_pic',
                    'r.profession',
                    'r.company',
                    'r.industry',
                    'u.created_at',
                    'u.updated_at'
                )
                ->first();

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $profile,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }



    public function getProfileCV()
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'User is not authenticated']);
        }

        return response()->json([
            'success' => true,
            'message' => $this->cvRows(Auth::user()->code)
        ]);
    }

    // ------------------------------------------------------------------
    //  Shared CV link (no sign in needed)
    //
    //  The link is {frontend}/cv/{code}/{signature}. The signature is an HMAC of the code with the app key, so only
    //  links handed out by shareCvLink() work: changing the code in the address does not open somebody else's CV.
    //  Nothing is stored, so one link cannot be revoked on its own; deactivating the account switches it off.
    // ------------------------------------------------------------------

    /** GET cv/share-link — the signed-in job seeker's own link (the route is behind 'cvwriter'). */
    public function shareCvLink()
    {
        $code = Auth::user()->code;
        $privacy = ProfilePrivacy::get($code);
        if (!$privacy['cv_link'] || $privacy['profile'] !== 'public') {
            return response()->json(['success' => false, 'message' => 'Your CV link is switched off in Settings > Profile & Privacy.'], 409);
        }
        $path = 'cv/' . $code . '/' . $this->cvSignature($code);

        return response()->json([
            'success' => true,
            'url' => rtrim((string) config('app.frontend_url'), '/') . '/' . $path,
        ]);
    }

    /** GET cv/shared/{code}/{signature} — public. */
    public function sharedCv(string $code, string $signature)
    {
        $cv = $this->sharedCvRow($code, $signature);
        if (!$cv) {
            return $this->sharedCvNotFound();
        }

        // the owner's "Hide on my CV" switches (1 = hidden) are applied here, so hidden values never leave the server
        if ($cv['email_visibility']) {
            $cv['email'] = null;
        }
        if ($cv['contact_visibility']) {
            $cv['contact_no'] = null;
        }
        // an anonymous visitor gets only what is public
        $privacy = ProfilePrivacy::get($code);
        if ($privacy['contact'] !== 'public') {
            $cv['email'] = $cv['contact_no'] = null;
        }
        if ($privacy['personal'] !== 'public') {
            $cv['date_birth'] = $cv['home_country'] = $cv['home_state'] = $cv['current_location'] = $cv['current_state'] = null;
        }
        if ($privacy['experience'] !== 'public') {
            $cv['lines'] = array_map(fn () => collect(), $cv['lines']);
        }
        $cv['lines']['certificate'] = $cv['lines']['certificate']->map(fn ($c) => [
            'certificate_title' => $c->certificate_title,
            'certificate_provider' => $c->certificate_provider,
            'date_completed' => $c->date_completed,
        ]);

        return response()->json(['success' => true, 'message' => [$cv]])
            ->header('Cache-Control', 'no-store, private');
    }

    /** GET cv/shared/{code}/{signature}/photo — the same picture with CORS headers, for the PDF export. */
    public function sharedCvPhoto(string $code, string $signature)
    {
        $cv = $this->sharedCvRow($code, $signature);
        $relative = $cv ? MediaUrl::relativePath($cv['photo_pic']) : null;

        // only this owner's own profile photo: nothing else on the disk is reachable through a share link
        if (!$relative
            || !preg_match('#^uploads/' . (int) $code . '/cvphoto/[^.][^?]*\.(jpe?g|png|gif|webp|jfif)$#i', $relative)
            || str_contains($relative, '..')
            || !Storage::disk('public')->exists($relative)) {
            return response()->json(['success' => false, 'message' => 'Image not found.'], 404);
        }

        return response()->file(Storage::disk('public')->path($relative), [
            'Cache-Control' => 'max-age=300',
        ])->setPrivate();
    }

    private function cvSignature($code): string
    {
        return rtrim(strtr(base64_encode(hash_hmac('sha256', 'cv-share|' . (int) $code, (string) config('app.key'), true)), '+/', '-_'), '=');
    }

    /** The CV behind a share link, or null: wrong signature, or not (or no longer) an active job seeker. */
    private function sharedCvRow(string $code, string $signature): ?array
    {
        if (!hash_equals($this->cvSignature($code), $signature)) {
            return null;
        }
        $owner = User::where('code', $code)->first();
        if (!$owner || $owner->status !== 'A' || !\App\Http\Middleware\EnsureCanCreateCv::allows($owner)) {
            return null;
        }

        $privacy = ProfilePrivacy::get($owner->code);
        if (!$privacy['cv_link'] || $privacy['profile'] !== 'public') {
            return null;
        }

        return $this->cvRows($owner->code)[0] ?? null;
    }

    private function sharedCvNotFound()
    {
        return response()->json(['success' => false, 'message' => 'This CV link is not valid or the CV is no longer available.'], 404);
    }

    // ------------------------------------------------------------------
    //  Profile privacy and the logged-out profile
    // ------------------------------------------------------------------

    /** PUT company-details : a recruiter edits the company shown on their own profile */
    public function updateCompany(Request $request)
    {
        $user = Auth::user();
        if ($user->role_code !== 'DEF-CLIENT') {
            return response()->json(['success' => false, 'message' => 'Only recruiter accounts have a company profile.'], 403);
        }
        $v = $request->validate([
            'company' => 'required|string|max:255', 'industry' => 'nullable|string|max:255', 'companywebsite' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255', 'about' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($user, $v) {
            DB::table('users')->where('code', $user->code)->update(['company' => $v['company'], 'updated_at' => now()]);
            DB::table('resources')->where('code', $user->code)->update(['company' => $v['company'], 'industry' => $v['industry'] ?? null,
                'companywebsite' => $v['companywebsite'] ?? null, 'current_location' => $v['location'] ?? null, 'updated_at' => now()]);
            $profile = ['summary' => $v['about'] ?? null, 'current_location' => $v['location'] ?? null, 'updated_at' => now()];
            if (!DB::table('userprofiles')->where('code', $user->code)->update($profile)) {
                DB::table('userprofiles')->insert($profile + ['code' => $user->code, 'transNo' => 1, 'created_at' => now()]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Company information saved.']);
    }

    /** GET privacy-settings (own) */
    public function privacySettings()
    {
        return response()->json(['success' => true, 'data' => ProfilePrivacy::get(Auth::user()->code)]);
    }

    /** PUT privacy-settings (own) */
    public function savePrivacySettings(Request $request)
    {
        $level = 'required|in:' . implode(',', ProfilePrivacy::LEVELS);
        $v = $request->validate(['profile' => $level, 'contact' => $level, 'experience' => $level, 'personal' => $level,
            'visitors' => 'required|boolean', 'cv_link' => 'required|boolean']);
        $v['visitors'] = (bool) $v['visitors'] && $v['profile'] === 'public';
        $v['cv_link'] = (bool) $v['cv_link'];
        DB::table('users')->where('code', Auth::user()->code)->update(['privacy_settings' => json_encode($v), 'updated_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Privacy settings saved.', 'data' => $v]);
    }

    /** GET public/profile/{code} : no sign in. Only for owners who opted in; never contact details or CV. */
    public function publicProfile(string $code)
    {
        $owner = User::where('code', $code)->first();
        if (!$owner || $owner->status !== 'A' || in_array($owner->role_code, \App\Helpers\PostVisibility::ADMIN_ROLES, true)
            || !ProfilePrivacy::openToVisitors($code)) {
            return response()->json(['success' => false, 'message' => 'This profile is not public.'], 404);
        }

        $r = DB::table('resources')->where('code', $code)->first(['fname', 'lname', 'profession', 'company', 'industry', 'companywebsite', 'current_location', 'coverphoto']);
        $p = DB::table('userprofiles')->where('code', $code)->first(['photo_pic', 'summary', 'current_location']);

        $posts = DB::table('posts')->where('code', $code)->where('status', \App\Helpers\PostVisibility::PUBLIC)
            ->orderByDesc('created_at')->limit(20)->get(['posts_uuid', 'caption', 'created_at']);
        $images = DB::table('attachmentposts')->whereIn('posts_uuid', $posts->pluck('posts_uuid'))->where('posts_type', 'image')
            ->get(['posts_uuid', 'path_url'])->groupBy('posts_uuid');

        return response()->json(['success' => true, 'data' => [
            'name' => $owner->fullname, 'role_code' => $owner->role_code,
            'photo_pic' => $p->photo_pic ?? null, 'coverphoto' => ($r->coverphoto ?? null) ?: ($owner->coverphoto ?: null),
            'profession' => $r->profession ?? null, 'company' => $r->company ?? null, 'industry' => $r->industry ?? null,
            'companywebsite' => $r->companywebsite ?? null, 'location' => ($p->current_location ?? null) ?: ($r->current_location ?? null),
            'about' => $p->summary ?? null,
            'connections_count' => count(ProfilePrivacy::connectionCodes($code)),
            'jobs' => DB::table('jobposting')->where('code', $code)->where('recordstatus', 'active')->orderByDesc('created_at')->limit(20)
                ->get(['transNo', 'job_name', 'company', 'location', 'work_type', 'created_at']),
            'posts' => $posts->map(fn ($x) => ['caption' => $x->caption, 'created_at' => $x->created_at,
                'images' => ($images[$x->posts_uuid] ?? collect())->map(fn ($i) => ['path_url' => $i->path_url])->values()])->values(),
        ]])->header('Cache-Control', 'no-store');
    }

    /** One account's CV, one entry per userprofiles row. */
    private function cvRows($currentUserCode): array
    {
        // ✅ Get all user profiles for this user
        $userProfiles = DB::table('userprofiles')->where('code', $currentUserCode)->get();

        // ✅ Get common resource info once
        $resource = DB::table('resources')
            ->select('fname', 'lname', 'email', 'profession', 'contact_no', 'date_birth', 'home_country', 'current_location')
            ->where('code', $currentUserCode)
            ->first();

        $result = [];

        // A user can have CV entries (education, skills, jobs...) before a userprofiles row exists
        // (the row is only created once a photo is saved). Still return their CV instead of nothing.
        $rows = $userProfiles->all();
        if (empty($rows) && $resource) {
            $rows = [(object) []];
        }

        foreach ($rows as $profile) {
            $result[] = [
                "code" => $currentUserCode,
                "email" => $resource->email ?? null,
                "fname" => $resource->fname ?? null,
                "lname" => $resource->lname ?? null,
                "photo_pic" => $profile->photo_pic ?? null, // null = account has no picture; the UI shows the default avatar
                "contact_no" => $profile->contact_no ?? $resource->contact_no ?? null,
                "contact_visibility" => $profile->contact_visibility ?? 0,
                "email_visibility" => $profile->email_visibility ?? 0,
                "summary" => $profile->summary ?? null,
                "date_birth" => $profile->date_birth ?? $resource->date_birth ?? null,
                "home_country" => $profile->home_country ?? $resource->home_country ?? null,
                "current_location" => $profile->current_location ?? $resource->current_location ?? null,
                "home_state" => $profile->home_state ?? null,
                "current_state" => $profile->current_state ?? null,
                "profession" => $resource->profession ?? null,
                "lines" => [
                    "education" => DB::table('usereducations')
                        ->select('highest_education', 'school_name', 'start_month', 'start_year', 'end_month', 'end_year', 'status')
                        ->where('code', $currentUserCode)
                        ->get(),
                    "language" => DB::table('usercapabilities')
                        ->select('language')
                        ->where('code', $currentUserCode)
                        ->get(),
                    "training" => DB::table('usertrainings')
                        ->select('training_title', 'training_provider', 'date_completed')
                        ->where('code', $currentUserCode)
                        ->get(),
                    "seminar" => DB::table('userseminars')
                        ->select('seminar_title', 'seminar_provider', 'date_completed')
                        ->where('code', $currentUserCode)
                        ->get(),
                    "skills" => DB::table('userskills')
                        ->select('skills')
                        ->where('code', $currentUserCode)
                        ->get(),
                    "employment" => DB::table('useremploymentrecords')
                        ->select('company_name', 'position', 'job_description', 'date_completed')
                        ->where('code', $currentUserCode)
                        ->get(),
                    "certificate" => DB::table('usercertificates')
                        ->where('code', $currentUserCode)
                        ->get()
                ]
            ];
        }

        return $result;
    }




}



//    *** STORE ***
// {
//     "photo_pic": null,
//     "contact_no": "1234567890",
//     "contact_visibility": 1,
//     "email_visibility": 1,
//     "summary": "This is a summary.",
//     "date_birth": "1990-01-01",
//     "home_country": "United States",
//     "current_location": "New York",
//     "home_state": "NY",
//     "current_state": "NY",
//     "lines": {
//       "education": [
//         {
//           "highest_education": "Bachelor's Degree",
//           "school_name": "University A",
//           "start_month": "January",
//           "start_year": "2010",
//           "end_month": "May",
//           "end_year": "2014",
//           "status": "Completed"
//         },
//         {
//           "highest_education": "Master's Degree",
//           "school_name": "University B",
//           "start_month": "August",
//           "start_year": "2015",
//           "end_month": "June",
//           "end_year": "2017",
//           "status": "Completed"
//         }
//       ],
//       "language": [
//         {
//           "language": "English"
//         },
//         {
//           "language": "French"
//         }
//       ],
//       "training": [
//         {
//           "training_title": "Leadership Training",
//           "training_provider": "Training Corp",
//           "trainingdate": "2019-12-01"
//         }
//       ],
//       "seminar": [
//         {
//           "seminar_title": "Tech Conference",
//           "seminar_provider": "TechWorld",
//           "seminardate": "2022-10-15"
//         }
//       ],
//     "skills": [
//         { "skills": "Programming" },
//         { "skills": "Web Design" }
//     ],
//       "employment": [
//         {
//           "company_name": "Company A",
//           "position": "Software Engineer",
//           "job_description": "Developed software solutions.",
//           "date_completed": "2021-08-01"
//         }
//       ],
//       "certificate": [
//         {
//           "certificate_title": "AWS Certified",
//           "certificate_provider": "Amazon",
//           "date_completed": "2020-05-01"
//         }
//       ]
//     }
// }