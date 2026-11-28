<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * GET public/stats   (no sign-in: the website's About section shows these)
 *
 * Three totals only, counted from the real tables the same way the admin dashboard counts them.
 * No names, codes or anything else about any account leaves the server.
 *
 *   professionals  active job-seeker accounts       (users, role DEF-USERS, status A)
 *   jobs           every job ever posted            (jobposting)
 *   companies      active recruiter/company accounts (users, role DEF-CLIENT, status A)
 *
 * Cached for 10 minutes so the public page cannot be used to hammer the database.
 */
class PublicStatsController extends Controller
{
    public const CACHE_KEY = 'public-stats';
    private const CACHE_SECONDS = 600;

    public function show()
    {
        $data = Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, fn () => [
            'professionals' => DB::table('users')->where('role_code', 'DEF-USERS')->where('status', 'A')->count(),
            'jobs'          => DB::table('jobposting')->count(),
            'companies'     => DB::table('users')->where('role_code', 'DEF-CLIENT')->where('status', 'A')->count(),
        ]);

        return response()->json(['success' => true, 'data' => $data]);
    }
}
