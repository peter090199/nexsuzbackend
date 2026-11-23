<?php

namespace App\Http\Controllers\CV;

use App\Http\Controllers\Controller;
use App\Services\CvSetupService;
use Illuminate\Support\Facades\Auth;

class CvStatusController extends Controller
{
    /**
     * GET cv/status : has the signed-in account finished its CV?
     * `applies` is false for roles that have no CV (recruiters and admins), and they always count as complete.
     */
    public function show(CvSetupService $cv)
    {
        return response()->json(['success' => true, 'data' => $cv->status(Auth::user())]);
    }
}
