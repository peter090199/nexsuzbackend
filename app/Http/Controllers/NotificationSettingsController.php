<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationPrefs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * The signed-in user's notification preferences. There is no user id in any route: every call reads and writes
 * the caller's own row, so one user can never see or change another user's settings.
 *
 *   GET  notification-settings          -> every setting (defaults + what the user saved)
 *   PUT  notification-settings          -> partial update, e.g. { "sound": true, "sound_volume": 40 }
 *   POST notification-settings/reset    -> back to the defaults
 */
class NotificationSettingsController extends Controller
{
    public function show()
    {
        return $this->respond(NotificationPrefs::for(Auth::id()));
    }

    public function update(Request $request)
    {
        $unknown = array_diff(array_keys($request->all()), array_keys(NotificationPrefs::DEFAULTS));
        if ($unknown) {
            return response()->json([
                'success' => false,
                'message' => 'Unknown setting: ' . implode(', ', $unknown) . '.',
                'errors'  => ['settings' => ['Unknown setting: ' . implode(', ', $unknown) . '.']],
            ], 422);
        }

        $validator = Validator::make($request->all(), NotificationPrefs::rules(), [
            'sound_volume.between' => 'The volume must be between 0 and 100.',
            '*.boolean'            => 'This setting must be on or off.',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        return $this->respond(NotificationPrefs::update(Auth::id(), $validator->validated()));
    }

    public function reset()
    {
        return $this->respond(NotificationPrefs::reset(Auth::id()), 'Notification settings restored to the defaults.');
    }

    private function respond(array $settings, string $message = 'OK')
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $settings]);
    }
}
