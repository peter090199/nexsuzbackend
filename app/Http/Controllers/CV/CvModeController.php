<?php

namespace App\Http\Controllers\CV;

use App\Http\Controllers\Controller;
use App\Services\CvModeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * How job seekers provide their CV.
 *
 *   GET cv/mode  (any signed-in account)  -> { upload_only, mode: 'upload' | 'builder' }
 *   PUT cv/mode  (master admin only)      -> { upload_only: true | false }
 */
class CvModeController extends Controller
{
    public function show()
    {
        return $this->respond();
    }

    public function update(Request $request)
    {
        $data = $request->validate(['upload_only' => 'required|boolean']);

        if (!Schema::hasTable('app_settings')) {
            return response()->json(['success' => false, 'message' => 'The settings table is missing. Run the app_settings migration first.'], 503);
        }

        CvModeService::set((bool) $data['upload_only']);

        return $this->respond('CV setting saved.');
    }

    private function respond(string $message = 'OK')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => ['upload_only' => CvModeService::uploadOnly(), 'mode' => CvModeService::mode()],
        ]);
    }
}
