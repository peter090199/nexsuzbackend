<?php

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Broadcast;
    use  App\Http\Controllers\Lookup\LookupController;
    use  App\Http\Controllers\Auth\LoginController;
    use  App\Http\Controllers\Auth\RegisterController;
    use  App\Http\Controllers\Auth\ForgetpasswordController;
    use  App\Http\Controllers\Auth\ProfileController;
    use  App\Http\Controllers\Auth\ProfilepictureController;
    use  App\Http\Controllers\Auth\PostController;
    use  App\Http\Controllers\Postcomments\CommentController;
    use App\Http\Controllers\Accessrolemenu\AccessrolemenuController;
    use App\Http\Controllers\System\Menus\MenuController;
    use App\Http\Controllers\System\Securityroles\SecurityroleController;
    use App\Http\Controllers\System\Securityroles\SecurityAdminController;
    use App\Http\Controllers\System\Roles\RoleController;
    use App\Http\Controllers\SearchAccount\UserController;
    use App\Http\Controllers\Select2\SelectController;
    use App\Http\Controllers\ChatController;
    use App\Http\Controllers\Follow\ClientsBAL;
    use App\Http\Controllers\SearchAccount\SearchHistoryBAL;
    use App\Http\Controllers\CV\UserLanguage;
    use App\Http\Controllers\CV\UserEducations;
    use App\Http\Controllers\CV\UserSkills;
    use App\Http\Controllers\CV\UserSeminars;
    use App\Http\Controllers\CV\UserTrainings;
    use App\Http\Controllers\CV\UserCertificates;
    use App\Http\Controllers\CV\UserWorkExperiences;
    use App\Http\Controllers\Follow\FollowController;
    use App\Http\Controllers\Jobs\JobPostingController;
    use App\Http\Controllers\Jobs\SavedJobController;
    use App\Http\Controllers\Jobs\JobListController;
    use App\Http\Controllers\Jobs\JobTemplateController;
    use App\Http\Controllers\Jobs\CandidateReviewController;
    use App\Http\Controllers\PhoneValidationController;
    use App\Http\Controllers\Jobs\QuestionController;
    use App\Http\Controllers\Jobs\AppliedJobController;
    use App\Http\Controllers\ReactionController;
    use App\Events\MessageSent;
    use App\Events\Message;
    use App\Events\NotificationCountUpdated;
    use App\Http\Controllers\System\Submenu\Submenus;
    use App\Http\Controllers\System\Users\AppUsersController;
    use App\Http\Controllers\AccountPlan\UserPlanController;
    use App\Http\Controllers\AccountPlan\UserPlanDetailsController;
    use App\Http\Controllers\AccountPlan\UserSubscriptionController;
    use App\Http\Controllers\SubscriptionAdmin\FeatureController as SubFeatureController;
    use App\Http\Controllers\SubscriptionAdmin\PlanFeatureAssignmentController;
    use App\Http\Controllers\SubscriptionAdmin\SubscriberController as SubSubscriberController;
    use App\Http\Controllers\SubscriptionAdmin\ReportController as SubReportController;
    use App\Http\Controllers\System\DatabaseAdminController;
    use App\Http\Controllers\System\EmailConfigController;
    use App\Services\PlanService;

    /*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider and all of them will
    | be assigned to the "api" middleware group. Make something great!
    |
    */

    // PUBLIC
    Route::post('login', [LoginController::class, 'login'])->name('login');
    Route::post('resetpassword', [ForgetpasswordController::class, 'resetpassword'])->name('resetpassword');
    Route::post('forgetpassword', [ForgetpasswordController::class, 'forgetpassword'])->name('forgetpassword');
    Route::post('register', [RegisterController::class, 'register'])->name('register');
    Route::post('resend-activation', [RegisterController::class, 'resendActivation'])->middleware('throttle:resend-activation');
    Route::post('accountactivation', [RegisterController::class, 'accountactivation'])->name('accountactivation');

    Route::get('getPlanpublic', [UserPlanController::class, 'getPlanpublic']);
    Route::get('getByPlanpublic/{planId}', [UserPlanDetailsController::class, 'getByPlanPublic']);

    Route::get('getActiveJobsByPublic', [JobListController::class, 'getActiveJobsByPublic']);
    Route::get('searchUsersBypublic', [UserController::class, 'searchUsersBypublic']);

    Route::post('send-message', function (Request $request) {
        $message = $request->input('message');
        event(new MessageSent($message)); // ✅ Corrected event class name
        return response()->json(['success' => true, 'message' => $message]);
    });

    // Route::get('testmail', function() {
    //     Mail::raw('Test email from Laravel', function($message) {
    //         $message->to('nexsuz.official13@gmail.com')
    //                 ->subject('Test Mail');
    //     });
    //     return 'Email sent';
    // });

    Route::middleware('auth:api')->post('/profile/broadcasting/auth', function () {
        return Broadcast::auth(request());
    });

    // PUBLIC website: Contact section data (only what the display switches allow) and the contact form
    Route::get('business-info/public', [\App\Http\Controllers\System\BusinessInfoController::class, 'publicShow']);
    Route::post('contact-message', [\App\Http\Controllers\System\BusinessInfoController::class, 'contact'])->middleware('throttle:contact-message');
    // PUBLIC website: About section totals (three counts only, cached 10 minutes)
    Route::get('public/stats', [\App\Http\Controllers\System\PublicStatsController::class, 'show'])->middleware('throttle:60,1');

    // PUBLIC language data: the picker and the texts are needed before anyone signs in
    Route::get('i18n/config', [\App\Http\Controllers\System\LocalizationController::class, 'config']);
    Route::get('i18n/countries', [\App\Http\Controllers\System\LocalizationController::class, 'countries']);
    Route::get('i18n/translations/{locale}', [\App\Http\Controllers\System\LocalizationController::class, 'translations']);

    // PDF options only (no personal data): the shared CV page below needs them without a sign-in
    Route::get('cv/pdf-settings', [\App\Http\Controllers\CV\CvPdfSettingsController::class, 'show'])->middleware('throttle:60,1');

    // PUBLIC shared CV: read only, and only with the signature from the link the owner shared (see ProfileController)
    Route::get('cv/shared/{code}/{signature}', [ProfileController::class, 'sharedCv'])->whereNumber('code');
    // PUBLIC profile: only for owners who opted in to logged-out visitors
    Route::get('public/profile/{code}', [ProfileController::class, 'publicProfile'])->whereNumber('code');
    Route::get('cv/shared/{code}/{signature}/photo', [ProfileController::class, 'sharedCvPhoto'])->whereNumber('code');

    Route::middleware(['auth:sanctum', 'checkstatus'])->group(function () {
        Route::get('user', function (Request $request) {
            return $request->user();
        });
        //accessmenu
        Route::Resource('accessmenu', AccessrolemenuController::class)->names('accessmenu');
        // changing what a role can open: DEF-MASTERADMIN only (these two used to accept any signed-in user)
        Route::post('saveAccessMenu', [SecurityroleController::class, 'saveAccessMenu'])->middleware('masteradmin');
        Route::delete('deleteMenu', [SecurityroleController::class, 'deleteMenu'])->middleware('masteradmin');
        // Route::get('getModule', [AccessrolemenuController::class, 'getModule']);
        Route::post('logout', [LoginController::class, 'logout'])->name('logout');
        // Settings > Account Information: administrators edit their own name, email, profession, company, website
        Route::put('account/details', [\App\Http\Controllers\Auth\AccountDetailsController::class, 'update'])->middleware('throttle:20,1');
        // pause my own account (job seekers and recruiters); signing in again reactivates it
        Route::post('account/deactivate', [\App\Http\Controllers\Auth\AccountDeactivationController::class, 'deactivate'])->middleware('throttle:5,1');
        // PROFILE resource
        Route::Resource('profile', ProfileController::class)->except(['store'])->names('profile');
        Route::post('profile', [ProfileController::class, 'store'])->name('profile.store')->middleware('planfeature:CREATE_PROFILE');
        Route::get('user/profile', [ProfileController::class, 'userAuth'])->name('user/profile');
        Route::resource('profile_pic', ProfilepictureController::class)->names('profile_pic');
        Route::post('uploadCoverPhoto', [ProfileController::class, 'uploadCoverPhoto']);
        Route::post('removeCoverPhoto', [ProfileController::class, 'removeCoverPhoto']);
        Route::post('uploadProfilePhoto', [ProfileController::class, 'uploadProfilePhoto']);
        Route::post('removeProfilePhoto', [ProfileController::class, 'removeProfilePhoto']);
        Route::get('media', [ProfileController::class, 'media']);
        // own language, and (master admin only) Language Setup
        Route::get('i18n/preference', [\App\Http\Controllers\System\LocalizationController::class, 'preference']);
        Route::put('i18n/preference', [\App\Http\Controllers\System\LocalizationController::class, 'savePreference']);
        Route::middleware('masteradmin')->controller(\App\Http\Controllers\System\LocalizationController::class)->prefix('i18n')->group(function () {
            Route::get('admin', 'admin');
            Route::get('users', 'users');
            Route::put('users/{id}', 'setUserLanguage')->whereNumber('id');
            Route::put('countries/{code}', 'setCountry');
            Route::post('languages', 'saveLanguage');
            Route::put('languages/{code}', 'setLanguage');
            Route::put('translations/{locale}', 'saveTranslations');
        });
        Route::put('company-details', [ProfileController::class, 'updateCompany']);
        Route::get('privacy-settings', [ProfileController::class, 'privacySettings']);
        Route::put('privacy-settings', [ProfileController::class, 'savePrivacySettings']);
        //users
        Route::get('getUsers', [AppUsersController::class, 'getUsers']);
        // admin dashboards (DEF-ADMIN and DEF-MASTERADMIN; the controller scopes the data by role)
        Route::get('admin/dashboard-stats', [\App\Http\Controllers\System\AdminDashboardController::class, 'stats']);
        // add a DEF-ADMIN account (master admin only; the role is fixed server-side)
        Route::post('users/admin', [AppUsersController::class, 'storeAdmin'])->middleware(['masteradmin', 'throttle:20,1']);
        // edit an account's contact details (master admin only; role / status / password cannot be changed here)
        Route::patch('users/{code}', [AppUsersController::class, 'update'])->middleware('masteradmin');
        Route::patch('users/{code}/status', [AppUsersController::class, 'setStatus'])->middleware('masteradmin');
        // delete one account and everything it created (master admin only; password + typed email + backup first)
        Route::get('users/{code}/delete-preview', [AppUsersController::class, 'deletePreview'])->middleware('masteradmin');
        Route::delete('users/{code}', [AppUsersController::class, 'destroy'])->middleware(['masteradmin', 'throttle:user-delete']);

        // is this job seeker's CV finished? (the Angular route guard keeps DEF-USERS on the CV page until it is)
        Route::get('cv/status', [\App\Http\Controllers\CV\CvStatusController::class, 'show']);
        // how job seekers provide their CV: fill up the builder, or upload a CV / resume only (master admin decides)
        Route::get('cv/mode', [\App\Http\Controllers\CV\CvModeController::class, 'show']);
        Route::put('cv/mode', [\App\Http\Controllers\CV\CvModeController::class, 'update'])->middleware('masteradmin');
        // CV Setup (master admin): PDF preview / print / download options and the server's URLs and storage health
        Route::put('cv/pdf-settings', [\App\Http\Controllers\CV\CvPdfSettingsController::class, 'update'])->middleware('masteradmin');
        Route::get('cv/pdf-environment', [\App\Http\Controllers\CV\CvPdfSettingsController::class, 'environment'])->middleware('masteradmin');

        // Menus
        Route::Resource('menu', MenuController::class)->names('menu');
        Route::post('saveMenu', [MenuController::class, 'saveMenu']);
        Route::get('getAllModules', [MenuController::class, 'getAllModules']);
        Route::delete('deleteMenu/{transNo}', [MenuController::class, 'deleteMenu']);
        Route::put('updateMenuById/{id}', [MenuController::class, 'updateMenuById']);


        //submenu
        Route::get('getSubmenuByTransNo/{transNo}', [Submenus::class, 'getSubmenuByMenuTransNo']);
        Route::delete('deleteSubmenu/{id}', [Submenus::class, 'deleteSubmenu']);
        Route::post('saveSubmenus', [Submenus::class, 'saveSubmenus']);
        Route::put('updateSubmenuById/{id}', [Submenus::class, 'updateSubmenuById']);
        // ROLE & PERMISSION MANAGEMENT (DEF-MASTERADMIN only; rules + audit in SecurityManagementService)
        Route::prefix('security-admin')->middleware('masteradmin')->group(function () {
            Route::get('/stats', [SecurityAdminController::class, 'stats']);
            Route::get('/roles', [SecurityAdminController::class, 'roles']);
            Route::post('/roles', [SecurityAdminController::class, 'storeRole']);
            Route::put('/roles/{id}', [SecurityAdminController::class, 'updateRole']);
            Route::delete('/roles/{id}', [SecurityAdminController::class, 'destroyRole']);
            Route::get('/roles/{id}/permissions', [SecurityAdminController::class, 'permissions']);
            Route::put('/roles/{id}/permissions', [SecurityAdminController::class, 'savePermissions']);
            Route::get('/users', [SecurityAdminController::class, 'users']);
            Route::post('/users/assign', [SecurityAdminController::class, 'assign']);
            Route::delete('/users/{code}/role', [SecurityAdminController::class, 'removeRole']);
            Route::get('/audit', [SecurityAdminController::class, 'audit']);
        });
        // Security roles
        Route::Resource('security', SecurityroleController::class)->names('security');
        //Role
        Route::Resource('role', RoleController::class)->names('role');
        // SELECT2 ALL REQUEST
        Route::post('rolecode', [SelectController::class, 'rolecode'])->name('rolecode');
        // lookup information
        Route::get('userlists', [LookupController::class, 'userlists'])->name('userlists');
        //search fullname
        Route::get('searchUsers', [UserController::class, 'searchUsers'])->middleware('planfeature:SEARCH_ACCESS');
        Route::get('company/people/{code}', [UserController::class, 'companyPeople']);
        //get Onlineusers
        Route::get('getIsOnline', [LoginController::class, 'getIsOnline']);

        //chat meesages realtime
        Route::post('send-message', [ChatController::class, 'sendMessage'])->middleware('planfeature:MESSAGING_ACCESS');
        Route::post('messages/read', [ChatController::class, 'markAsRead'])->middleware('planfeature:MESSAGING_ACCESS');
        Route::get('conversations', [ChatController::class, 'conversations'])->middleware('planfeature:MESSAGING_ACCESS');

        // notification preferences + social activity notifications (always the caller's own)
        Route::get('notification-settings', [\App\Http\Controllers\NotificationSettingsController::class, 'show']);
        Route::put('notification-settings', [\App\Http\Controllers\NotificationSettingsController::class, 'update']);
        Route::post('notification-settings/reset', [\App\Http\Controllers\NotificationSettingsController::class, 'reset']);
        Route::get('activity-notifications', [\App\Http\Controllers\ActivityNotificationController::class, 'index']);
        Route::post('activity-notifications/read', [\App\Http\Controllers\ActivityNotificationController::class, 'markRead']);
        Route::get('receivemessages/{receiverId}', [ChatController::class, 'fetchMessages'])->middleware('planfeature:MESSAGING_ACCESS');
        Route::get('getActiveUsers', [ChatController::class, 'getActiveUsers'])->middleware('planfeature:MESSAGING_ACCESS');
        Route::put('messagesIsread', [ChatController::class, 'markAsReadMessage'])->middleware('planfeature:MESSAGING_ACCESS');

        Route::post('savePost', [PostController::class, 'savePost'])->middleware('planfeature:NETWORK_FEED');
        Route::post('updatePostByTransNo/{transNo}', [PostController::class, 'updatePostByTransNo'])->middleware('planfeature:NETWORK_FEED');
        Route::get('update_count', [ChatController::class, 'updateNotificationCount']);
        Route::get('getDataPost', [PostController::class, 'getDataPost']);

        Route::get('getNotificationsIsUnRead', [ChatController::class, 'getNotificationsIsUnRead']);
        Route::get('getNotificationsIsRead', [ChatController::class, 'getNotificationsIsRead']);

        Route::post('messages/mark_allAsread', [ChatController::class, 'markAllAsRead'])->middleware('planfeature:MESSAGING_ACCESS');
        Route::get('messages_receive/{receiverId}', [ChatController::class, 'messages_receive'])->middleware('planfeature:MESSAGING_ACCESS');
        Route::get('getMessagesAll', [ChatController::class, 'getMessagesAll'])->middleware('planfeature:MESSAGING_ACCESS');

        //Post 
        Route::resource('post', PostController::class)->names('post');
        Route::post('deleteindidualpost/{id}', [PostController::class, 'deleteIndividualPost']);
        Route::post('reactToPostById', [FollowController::class, 'reactToPostById'])->middleware('planfeature:NETWORK_FEED');
        Route::post('removeReactionByPostId', [FollowController::class, 'removeReactionByPostId']);
        Route::get('getReactionByPostId/{id}', [FollowController::class, 'getReactionByPostId']);

        //Comment
        Route::resource('comment', CommentController::class)->except(['store'])->names('comment');
        Route::post('comment', [CommentController::class, 'store'])->name('comment.store')->middleware('planfeature:NETWORK_FEED');
        Route::post('commentreply', [CommentController::class, 'commentreply'])->middleware('planfeature:NETWORK_FEED');
        Route::post('reactToComment', [CommentController::class, 'reactToComment'])->middleware('planfeature:NETWORK_FEED');
        Route::post('removeCommentReaction', [CommentController::class, 'removeCommentReaction']);
        Route::get('commentReactors', [CommentController::class, 'commentReactors']);

        //Reactions  
        Route::resource('reaction', App\Http\Controllers\Postreaction\PostreactionController::class)->names('reaction');
        Route::get('getReactionPost/{post_id}', [ReactionController::class, 'getReactionPost']);

        //Follow  App\Http\Controllers\Follow
        Route::resource('follow', FollowController::class)->except(['update'])->names('follow');
        Route::put('follow/{follow}', [FollowController::class, 'update'])->name('follow.update')->middleware('planfeature:NETWORK_FEED');
        Route::get('getPost', [FollowController::class, 'getPost']);

        // GLOBAL ANNOUNCEMENTS: written by DEF-ADMIN / DEF-MASTERADMIN, read by everybody signed in (rules in AnnouncementService)
        Route::get('announcements', [\App\Http\Controllers\AnnouncementController::class, 'feed']);
        Route::get('announcements/manage', [\App\Http\Controllers\AnnouncementController::class, 'manage']);
        Route::get('announcements/{id}', [\App\Http\Controllers\AnnouncementController::class, 'show'])->whereNumber('id');
        Route::post('announcements', [\App\Http\Controllers\AnnouncementController::class, 'store'])->middleware('throttle:30,1');
        Route::post('announcements/{id}/update', [\App\Http\Controllers\AnnouncementController::class, 'update'])->whereNumber('id')->middleware('throttle:60,1');
        Route::post('announcements/{id}/status', [\App\Http\Controllers\AnnouncementController::class, 'setStatus'])->whereNumber('id');
        Route::delete('announcements/{id}', [\App\Http\Controllers\AnnouncementController::class, 'destroy'])->whereNumber('id');
        Route::post('announcements/{id}/react', [\App\Http\Controllers\AnnouncementController::class, 'react'])->whereNumber('id')->middleware('planfeature:NETWORK_FEED');   // same plan rule as reacting to a post
        Route::delete('announcements/{id}/react', [\App\Http\Controllers\AnnouncementController::class, 'unreact'])->whereNumber('id');
        Route::get('posts/followed', [FollowController::class, 'getFollowedPosts']);

        // Route::post('/post-attachment/{id}', [PostController::class, 'deleteIndividualPost']);
        //List clients base on rrofile
        Route::get('getListClients', [ClientsBAL::class, 'getListClients']);
        Route::get('getFollowStatus/{code}', [ClientsBAL::class, 'getFollowStatus']);
        Route::get('getPendingFollowStatus/{code}', [ClientsBAL::class, 'getPendingFollowStatus']);
        //List clients base on PENDING
        Route::get('getPendingFollowRequests', [ClientsBAL::class, 'getPendingFollowRequests']);
        Route::get('getfollowingPending', [ClientsBAL::class, 'getfollowingPending']);
        Route::put('acceptFollowRequest/{followerCode}', [ClientsBAL::class, 'acceptFollowRequest'])->middleware('planfeature:NETWORK_FEED');
        //unfollow
        Route::delete('unfollow/{id}', [ClientsBAL::class, 'unfollow']);
        // BLOCKING (all account types): every route acts on the signed-in account's own block list
        Route::get('blocks', [\App\Http\Controllers\BlockController::class, 'index']);
        Route::post('blocks', [\App\Http\Controllers\BlockController::class, 'store']);
        Route::delete('blocks/{code}', [\App\Http\Controllers\BlockController::class, 'destroy']);
        Route::get('blocks/status/{code}', [\App\Http\Controllers\BlockController::class, 'status']);
        // Suggested users based on profession or industry of followed people
        Route::get('getPeopleyoumayknow', [ClientsBAL::class, 'getPeopleyoumayknow']);
        Route::get('getPeopleRecentActivity', [ClientsBAL::class, 'getPeopleRecentActivity']);
        Route::delete('deleteSearchHistory', [SearchHistoryBAL::class, 'deleteSearchHistory'])->middleware('planfeature:SEARCH_ACCESS');
        Route::post('saveSearchHistory', [SearchHistoryBAL::class, 'saveSearchHistory'])->middleware('planfeature:SEARCH_ACCESS');
        Route::get('getSearchHistory', [SearchHistoryBAL::class, 'getSearchHistory'])->middleware('planfeature:SEARCH_ACCESS');
        //enhance cv  (saveProfile: role rule is enforced inside ProfileController@store; the section routes below use 'cvwriter')
        Route::post('saveProfile', [ProfileController::class, 'saveProfile'])->middleware('planfeature:CREATE_PROFILE');
        // Settings > Resume / CV: the job seeker's own PDF resumes, one marked as the default (DEF-USERS only)
        Route::middleware(['cvwriter', 'planfeature:CREATE_RESUME'])->prefix('user-resumes')->group(function () {
            Route::get('/', [\App\Http\Controllers\Auth\UserResumeController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Auth\UserResumeController::class, 'store'])->middleware('throttle:20,1');
            Route::patch('/{id}/default', [\App\Http\Controllers\Auth\UserResumeController::class, 'setDefault'])->whereNumber('id');
            Route::delete('/{id}', [\App\Http\Controllers\Auth\UserResumeController::class, 'destroy'])->whereNumber('id');
            Route::get('/{id}/file', [\App\Http\Controllers\Auth\UserResumeController::class, 'download'])->whereNumber('id');
            // read the CV and return its data for the import preview (saves nothing), then save what the person approved
            Route::post('/{id}/extract', [\App\Http\Controllers\Auth\UserResumeController::class, 'extract'])->whereNumber('id')->middleware('throttle:20,1');
            Route::post('/{id}/import', [\App\Http\Controllers\Auth\UserResumeController::class, 'import'])->whereNumber('id')->middleware('throttle:20,1');
        });
        Route::get('getProfileData', [ProfileController::class, 'getProfileData']);
        Route::get('getProfileByCode', [ProfileController::class, 'getProfileByCode']);
        //LANGAUGE
        Route::post('saveLanguage', [UserLanguage::class, 'saveLanguage'])->middleware(['cvwriter', 'planfeature:CREATE_RESUME']);
        Route::get('getLanguagesByCode', [UserLanguage::class, 'getLanguagesByCode']);
        Route::delete('deleteLanguageById/{id}', [UserLanguage::class, 'deleteLanguageById'])->middleware(['cvwriter', 'planfeature:CREATE_RESUME']);
        //education
        Route::post('saveEducation', [UserEducations::class, 'saveEducation'])->middleware(['cvwriter', 'planfeature:CREATE_RESUME']);
        Route::put('updateEducationById/{id}', [UserEducations::class, 'updateEducationById'])->middleware(['cvwriter', 'planfeature:CREATE_RESUME']);
        Route::get('getEducationsByCode', [UserEducations::class, 'getEducationsByCode']);
        Route::get('getEducationById/{id}', [UserEducations::class, 'getEducationById']);
        Route::delete('deleteEducation/{id}', [UserEducations::class, 'deleteEducation'])->middleware(['cvwriter', 'planfeature:CREATE_RESUME']);
        //Skills
        Route::post('saveSkills', [UserSkills::class, 'saveSkills'])->middleware(['cvwriter', 'planfeature:CREATE_RESUME']);
        Route::get('getSkills', [UserSkills::class, 'getSkills']);
        Route::put('skills/{id}', [UserSkills::class, 'updateSkill'])->middleware(['cvwriter', 'planfeature:CREATE_RESUME']);
        Route::delete('deleteSkills/{id}', [UserSkills::class, 'delete'])->middleware(['cvwriter', 'planfeature:CREATE_RESUME']);
        //seminar
        Route::post('saveSeminar', [UserSeminars::class, 'saveSeminar'])->middleware(['cvwriter', 'planfeature:CREATE_RESUME']);
        Route::put('updateSeminar/{id}', [UserSeminars::class, 'updateSeminar'])->middleware(['cvwriter', 'planfeature:CREATE_RESUME']);
        Route::get('getSeminarByCode', [UserSeminars::class, 'getSeminarByCode']);
        Route::delete('delete/{id}', [UserSeminars::class, 'delete'])->middleware(['cvwriter', 'planfeature:CREATE_RESUME']);
        //trainings
        Route::post('saveTrainings', [UserTrainings::class, 'saveTrainings'])->middleware(['cvwriter', 'planfeature:CREATE_RESUME']);
        Route::get('getTrainings', [UserTrainings::class, 'getTrainings']);
        Route::put('updateTrainings/{id}', [UserTrainings::class, 'updateTrainings'])->middleware(['cvwriter', 'planfeature:CREATE_RESUME']);
        Route::delete('deleteTraining/{id}', [UserTrainings::class, 'deleteTraining'])->middleware(['cvwriter', 'planfeature:CREATE_RESUME']);
        //certificate
        Route::post('saveCertificates', [UserCertificates::class, 'saveCertificates'])->middleware(['cvwriter', 'planfeature:CREATE_RESUME']);
        Route::get('getCertificates', [UserCertificates::class, 'getCertificates']);
        Route::delete('deleteCertificate/{id}', [UserCertificates::class, 'deleteCertificate'])->middleware(['cvwriter', 'planfeature:CREATE_RESUME']);
        Route::put('updateCertificates/{id}', [UserCertificates::class, 'updateCertificates'])->middleware(['cvwriter', 'planfeature:CREATE_RESUME']);
        //work experience
        Route::post('saveEmployment', [UserWorkExperiences::class, 'saveEmployment'])->middleware(['cvwriter', 'planfeature:CREATE_RESUME']);
        Route::get('getEmployment', [UserWorkExperiences::class, 'getEmployment']);
        Route::delete('deleteEmployment/{id}', [UserWorkExperiences::class, 'deleteEmployment'])->middleware(['cvwriter', 'planfeature:CREATE_RESUME']);
        Route::put('updateWorkExperience/{id}', [UserWorkExperiences::class, 'updateWorkExperience'])->middleware(['cvwriter', 'planfeature:CREATE_RESUME']);
        //GET CV DATA ALL
        Route::get('getProfileCV', [ProfileController::class, 'getProfileCV']);
        // the link a job seeker shares for their own CV
        Route::get('cv/share-link', [ProfileController::class, 'shareCvLink'])->middleware('cvwriter');
        //JobPosting
        Route::post('saveJobPosting', [JobPostingController::class, 'saveJobPosting'])->middleware('planfeature:JOB_POSTING');

        Route::post('saveJobs', [SavedJobController::class, 'saveJobs']);
        Route::get('getSaveJobs', [SavedJobController::class, 'getSaveJobs']);

        Route::post('job-posting', [JobPostingController::class, 'saveOrUpdateJobPosting'])->middleware('planfeature:JOB_POSTING');
        Route::put('job-posting/{transNo}', [JobPostingController::class, 'saveOrUpdateJobPosting'])->middleware('planfeature:JOB_POSTING');

        // job posting templates & themes: everyone signed in can read; writes are DEF-MASTERADMIN only (middleware + gate)
        Route::get('job-templates', [JobTemplateController::class, 'index']);
        Route::get('job-templates/{id}', [JobTemplateController::class, 'show'])->whereNumber('id');
        Route::prefix('admin/job-templates')->middleware('masteradmin')->group(function () {
            Route::get('/', [JobTemplateController::class, 'adminIndex']);
            Route::post('/', [JobTemplateController::class, 'store']);
            Route::put('/{id}', [JobTemplateController::class, 'update'])->whereNumber('id');
            Route::delete('/{id}', [JobTemplateController::class, 'destroy'])->whereNumber('id');
            Route::post('/{id}/duplicate', [JobTemplateController::class, 'duplicate'])->whereNumber('id');
            Route::post('/{id}/default', [JobTemplateController::class, 'setDefault'])->whereNumber('id');
            Route::post('/{id}/publish', [JobTemplateController::class, 'publish'])->whereNumber('id');
            Route::post('/{id}/unpublish', [JobTemplateController::class, 'unpublish'])->whereNumber('id');
        });

        // recruitment pipeline: candidate review, CV, AI evaluation, interviews, notes (job owner / master admin, checked in the controller)
        Route::prefix('recruitment')->middleware('planfeature:JOB_APPLICANTS')->group(function () {
            Route::get('reports', [\App\Http\Controllers\Jobs\RecruitmentReportController::class, 'index']);
            Route::get('candidates', [CandidateReviewController::class, 'allCandidates']);
            Route::get('jobs/{transNo}/candidates', [CandidateReviewController::class, 'candidates']);
            Route::get('applications/{id}', [CandidateReviewController::class, 'show'])->whereNumber('id');
            Route::get('applications/{id}/resume', [CandidateReviewController::class, 'resume'])->whereNumber('id');
            Route::delete('applications/{id}', [CandidateReviewController::class, 'destroy'])->whereNumber('id');
            Route::post('applications/{id}/status', [CandidateReviewController::class, 'changeStatus'])->whereNumber('id');
            Route::post('applications/{id}/notification-preview', [CandidateReviewController::class, 'notificationPreview'])->whereNumber('id');
            Route::post('applications/{id}/notes', [CandidateReviewController::class, 'addNote'])->whereNumber('id');
            Route::post('applications/{id}/interviews', [CandidateReviewController::class, 'scheduleInterview'])->whereNumber('id');
            Route::put('interviews/{id}', [CandidateReviewController::class, 'updateInterview'])->whereNumber('id');
            Route::post('applications/{id}/evaluate', [CandidateReviewController::class, 'evaluate'])->whereNumber('id');
            Route::get('applications/{id}/evaluations', [CandidateReviewController::class, 'evaluations'])->whereNumber('id');
            Route::post('evaluations/{id}/overrides', [CandidateReviewController::class, 'override'])->whereNumber('id');
            Route::get('evaluation-settings', [CandidateReviewController::class, 'settings']);
            Route::put('evaluation-settings', [CandidateReviewController::class, 'saveSettings']);
        });

        Route::put('updateJobPosting/{transNo}', [JobPostingController::class, 'updateJobPosting'])->middleware('planfeature:JOB_POSTING');
        Route::get('getJobPostingsByCode', [JobPostingController::class, 'getJobPostingsByCode']);
        Route::get('getJobPostingByTransNo/{transNo}', [JobPostingController::class, 'getJobPostingByTransNo']);
        Route::delete('deleteJobPosting/{transNo}', [JobPostingController::class, 'deleteJobPosting']);
        //JobList
        Route::get('getActiveJobs', [JobListController::class, 'getActiveJobs']);
        Route::get('getActiveJobsByCode/{code}', [JobListController::class, 'getActiveJobsByCode']);
        Route::get('getAppliedStatus/{transNo}', [AppliedJobController::class, 'getAppliedStatus']);
        //company profile
        Route::get('company/profile/{code}', [ProfileController::class, 'userAuthByCode']);
        Route::get('country_codes', function () {
            $phones = file_get_contents("http://country.io/phone.json");
            $names = file_get_contents("http://country.io/names.json");

            return response()->json([
                'phones' => json_decode($phones, true),
                'names'  => json_decode($names, true),
            ]);
        });
        Route::post('validate_phone', [PhoneValidationController::class, 'validate_phone']);
        //AppliedQuestions
        Route::post('addQuestions', [QuestionController::class, 'addQuestions']);
        Route::get('getQuestions/{jobId}', [QuestionController::class, 'getQuestionById']);
        Route::delete('deleteQuestionById/{question_id}', [QuestionController::class, 'deleteQuestionById']);
        // MY JOB APPLICATIONS (DEF-USERS only, own applications, read only)
        Route::middleware('cvwriter')->prefix('my-applications')->group(function () {
            Route::get('/', [\App\Http\Controllers\Jobs\MyApplicationsController::class, 'index']);
            Route::get('/{id}', [\App\Http\Controllers\Jobs\MyApplicationsController::class, 'show'])->whereNumber('id');
        });

        //AppliedJobs
        Route::post('saveAppliedJob', [AppliedJobController::class, 'saveAppliedJob'])->middleware('planfeature:APPLY_JOBS');
        Route::get('getAppliedJob', [AppliedJobController::class, 'getAppliedJob']);
        Route::get('getAppliedJobByTransNo/{transNo}', [AppliedJobController::class, 'getAppliedJobByTransNo']);
        Route::get('getInterviewAppliedJobs', [AppliedJobController::class, 'getInterviewAppliedJobs']);
        Route::get('getAllAppliedJobsByCode', [AppliedJobController::class, 'getAllAppliedJobsByCode']);
        Route::get('getAppliedJobByUsers/{transNo}', [AppliedJobController::class, 'getAppliedJobByUsers']);

        Route::post('updateAppliedStatus/{applied_id}', [AppliedJobController::class, 'updateAppliedStatus']);
        //Post Reactions
        // Route::post('saveReaction', [ReactionController::class, 'saveReaction']);
        Route::get('react/{postId}', [ReactionController::class, 'getReactions']);
        Route::post('saveReaction', [ReactionController::class, 'saveReaction']);


        //CLIENT DASHBOARD
        Route::get('getJobVacanciesCountByCode', [JobListController::class, 'getJobVacanciesCountByCode']);
        Route::get('getAppliedJobCount', [JobListController::class, 'getAppliedJobCount']);
        Route::get('getPendingReviews', [JobListController::class, 'getPendingReviews']);
        Route::get('getHired', [JobListController::class, 'getHired']);
        Route::get('getClientDashboard', [JobListController::class, 'getClientDashboard']);

        //scoring applied job
        Route::get('getApplicationScore', [JobPostingController::class, 'getApplicationScore']);

        // plans with features and limits for the signed-in account's own audience (Settings → Subscription Plan)
        Route::get('plans/overview', [\App\Http\Controllers\AccountPlan\PlanOverviewController::class, 'index']);

        //ACCOUNT PLAN
        Route::prefix('account-plan')->group(function () {
            Route::get('/list', [UserPlanController::class, 'index']);
            // Route::get('/getPlanpublic', [UserPlanController::class, 'getPlanpublic']);
            Route::get('/show/{planId}', [UserPlanController::class, 'show']);
            Route::post('/upgrade', [UserPlanController::class, 'upgrade']);
            Route::post('/free-plan', [PlanService::class, 'activateFreePlan']);

            // Plan management: DEF-ADMIN and DEF-MASTERADMIN (enforced server-side)
            Route::middleware('subscriptionadmin')->group(function () {
                Route::post('/save', [UserPlanController::class, 'save']);
                Route::put('/update/{id}', [UserPlanController::class, 'update']);
                Route::delete('/delete/{id}', [UserPlanController::class, 'destroy']);
                Route::patch('/deactivate/{id}', [UserPlanController::class, 'deactivate']);
                Route::patch('/activate/{id}', [UserPlanController::class, 'activate']);
                Route::patch('/popular/{id}', [UserPlanController::class, 'setPopular']);
                Route::get('/user-subscription/{code}', [UserPlanController::class, 'userSubscription']);
                Route::post('/assign', [UserPlanController::class, 'assign']);
            });
        });

        // SUBSCRIPTION MANAGEMENT (DEF-ADMIN and DEF-MASTERADMIN). Plans themselves use the account-plan routes above.
        Route::prefix('subscription-admin')->middleware('subscriptionadmin')->group(function () {
            Route::get('/features', [SubFeatureController::class, 'index']);
            Route::post('/features', [SubFeatureController::class, 'store']);
            Route::put('/features/{id}', [SubFeatureController::class, 'update']);
            Route::patch('/features/{id}/toggle', [SubFeatureController::class, 'toggle']);

            Route::get('/assignments', [PlanFeatureAssignmentController::class, 'overview']);
            Route::get('/assignments/{planId}', [PlanFeatureAssignmentController::class, 'show']);
            Route::put('/assignments/{planId}', [PlanFeatureAssignmentController::class, 'update']);

            Route::get('/subscribers', [SubSubscriberController::class, 'index']);
            Route::get('/subscribers/{code}', [SubSubscriberController::class, 'show']);
            Route::post('/subscribers/{code}/change-plan', [SubSubscriberController::class, 'changePlan']);
            Route::post('/subscribers/{code}/extend', [SubSubscriberController::class, 'extend']);
            Route::post('/subscribers/{code}/suspend', [SubSubscriberController::class, 'suspend']);
            Route::post('/subscribers/{code}/reactivate', [SubSubscriberController::class, 'reactivate']);

            Route::get('/reports', [SubReportController::class, 'index']);
        });

        // DATABASE ADMIN (DEF-MASTERADMIN only): backups, server info, storage, table maintenance. No restore / drop / raw SQL.
        Route::prefix('database-admin')->middleware('masteradmin')->group(function () {
            Route::get('/server', [DatabaseAdminController::class, 'server']);
            Route::get('/storage', [DatabaseAdminController::class, 'storage']);
            Route::get('/tables', [DatabaseAdminController::class, 'tables']);
            Route::post('/tables/{action}', [DatabaseAdminController::class, 'maintain'])->where('action', 'optimize|analyze')->middleware('throttle:db-maintain');
            Route::get('/backups', [DatabaseAdminController::class, 'backups']);
            Route::post('/backups', [DatabaseAdminController::class, 'createBackup'])->middleware('throttle:db-backup');
            Route::get('/backups/{name}/download', [DatabaseAdminController::class, 'downloadBackup']);
            Route::get('/reset/preview', [DatabaseAdminController::class, 'resetPreview']);
            Route::post('/reset', [DatabaseAdminController::class, 'resetAll'])->middleware('throttle:db-reset');
        });

        // "SEE TRANSLATION" (any signed-in user; works only while an administrator has the paid plan on)
        Route::get('translation/status', [\App\Http\Controllers\System\TranslationBillingController::class, 'status']);
        Route::get('translation/languages', [\App\Http\Controllers\System\TranslationBillingController::class, 'languages']);
        Route::post('translation/text', [\App\Http\Controllers\System\TranslationBillingController::class, 'text'])->middleware('throttle:30,1');

        // TRANSLATION PLANS AND PRICING (DEF-ADMIN, DEF-MASTERADMIN). The API key is never returned.
        Route::prefix('translation-billing')->middleware('subscriptionadmin')->controller(\App\Http\Controllers\System\TranslationBillingController::class)->group(function () {
            Route::get('/', 'show');
            Route::put('/settings', 'settings');
            Route::put('/key', 'saveKey');
            Route::delete('/key', 'deleteKey');
            Route::post('/test', 'test')->middleware('throttle:10,1');
            Route::put('/plan', 'plan');
            Route::post('/translate', 'translate')->middleware('throttle:20,1');
        });

        // EMAIL CONFIG (DEF-MASTERADMIN only): dynamic SMTP settings. The password is never returned.
        Route::prefix('email-config')->middleware(['masteradmin', 'throttle:email-config'])->group(function () {
            Route::get('/', [EmailConfigController::class, 'show']);
            Route::post('/', [EmailConfigController::class, 'store'])->name('email-config.store');
            Route::put('/', [EmailConfigController::class, 'update'])->name('email-config.update');
            Route::delete('/', [EmailConfigController::class, 'reset']);
            Route::post('/toggle', [EmailConfigController::class, 'toggle']);
            Route::post('/test-connection', [EmailConfigController::class, 'testConnection'])->middleware('throttle:email-test-connection');
            Route::post('/test-email', [EmailConfigController::class, 'testEmail'])->middleware('throttle:email-test-email');
        });

        // NEXSUZ INFORMATION (DEF-MASTERADMIN, or DEF-ADMIN when the "nexsuzinfo" submenu is assigned): one shared business record
        Route::prefix('business-info')->middleware(['businessinfo', 'throttle:business-info'])->group(function () {
            Route::get('/', [\App\Http\Controllers\System\BusinessInfoController::class, 'show']);
            Route::put('/', [\App\Http\Controllers\System\BusinessInfoController::class, 'update']);
            Route::post('/reset', [\App\Http\Controllers\System\BusinessInfoController::class, 'reset']);
            Route::post('/logo', [\App\Http\Controllers\System\BusinessInfoController::class, 'uploadLogo']);
            Route::delete('/logo', [\App\Http\Controllers\System\BusinessInfoController::class, 'removeLogo']);
            Route::post('/email-theme/preview', [\App\Http\Controllers\System\BusinessInfoController::class, 'previewEmailTheme'])->middleware('throttle:email-theme-preview');
            Route::post('/email-theme/test', [\App\Http\Controllers\System\BusinessInfoController::class, 'testEmailTheme'])->middleware('throttle:contact-mail-test');
            Route::get('/contact-mail', [\App\Http\Controllers\System\BusinessInfoController::class, 'contactMailStatus']);
            Route::post('/contact-mail/check', [\App\Http\Controllers\System\BusinessInfoController::class, 'contactMailCheck'])->middleware('throttle:contact-mail-check');
            Route::post('/contact-mail/test', [\App\Http\Controllers\System\BusinessInfoController::class, 'contactMailTest'])->middleware('throttle:contact-mail-test');
        });

        // CONTACT INQUIRIES (DEF-MASTERADMIN, or DEF-ADMIN when the menu is assigned): visitor messages, replies and history
        Route::prefix('contact-inquiries')->middleware(['menuaccess:contactinquiries', 'throttle:inquiries'])->group(function () {
            $c = \App\Http\Controllers\System\ContactInquiryController::class;
            Route::get('/', [$c, 'index']);
            Route::get('/unread-count', [$c, 'unreadCount']);
            Route::get('/{id}', [$c, 'show'])->whereNumber('id');
            Route::post('/{id}/read', [$c, 'setRead'])->whereNumber('id');
            Route::post('/{id}/status', [$c, 'setStatus'])->whereNumber('id');
            Route::post('/{id}/assign', [$c, 'assign'])->whereNumber('id');
            Route::post('/{id}/reply', [$c, 'reply'])->whereNumber('id')->middleware('throttle:inquiry-reply');
            Route::get('/{id}/messages/{messageId}/attachments/{index}', [$c, 'attachment'])->whereNumber(['id', 'messageId', 'index']);
            Route::post('/{id}/restore', [$c, 'restore'])->whereNumber('id');
            Route::delete('/{id}', [$c, 'destroy'])->whereNumber('id');
        });

        //ACCOUNT PLAN  FEATURES
        Route::prefix('account-plan-details')->group(function () {
            Route::get('/getByPlan/{planId}', [UserPlanDetailsController::class, 'getByPlan']);
            Route::get('/my-plan-features', [UserPlanController::class, 'myFeatures']);

            Route::middleware('subscriptionadmin')->group(function () {
                Route::post('/save', [UserPlanDetailsController::class, 'save']);
                Route::put('/update/{id}', [UserPlanDetailsController::class, 'update']);
                Route::delete('/delete/{id}', [UserPlanDetailsController::class, 'delete']);
            });
        });
    });


