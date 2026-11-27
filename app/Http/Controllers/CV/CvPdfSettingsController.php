<?php

namespace App\Http\Controllers\CV;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\SecurityManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * CV Setup: how CV and resume PDFs are previewed, printed and downloaded. Stored in app_settings (cv.pdf).
 *
 *   GET cv/pdf-settings     (public: a shared CV link is viewed without signing in) -> the options only
 *   PUT cv/pdf-settings     (master admin)
 *   GET cv/pdf-environment  (master admin) -> which server this is, its URLs and storage health
 *
 * The CV PDF itself is built in the browser, so "disable printing / downloading" removes those actions from the app;
 * it cannot stop someone from printing or saving what their browser is already showing.
 */
class CvPdfSettingsController extends Controller
{
    public const KEY = 'cv.pdf';

    /** how the app already behaved, so nothing changes until a master admin changes it */
    public const DEFAULTS = [
        'preview_enabled' => true, 'method' => 'preview', 'display' => 'embedded', 'filename' => 'CV_{name}',
        'page_size' => 'a4', 'orientation' => 'portrait', 'allow_print' => true, 'allow_download' => true,
    ];

    public static function current(): array
    {
        try {
            return array_merge(self::DEFAULTS, array_intersect_key(AppSetting::read(self::KEY) ?: [], self::DEFAULTS));
        } catch (\Throwable) {
            return self::DEFAULTS;      // a settings problem must never take the CV page down
        }
    }

    public function show()
    {
        return response()->json(['success' => true, 'data' => self::current()]);
    }

    public function update(Request $request, SecurityManagementService $security)
    {
        $data = $request->validate([
            'preview_enabled' => 'required|boolean',
            'method' => ['required', Rule::in(['preview', 'download'])],
            'display' => ['required', Rule::in(['tab', 'embedded'])],
            // a file name only: no folders, no characters Windows refuses; {name} becomes the person's name
            'filename' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9 _\-(){}]+$/'],
            'page_size' => ['required', Rule::in(['a4', 'letter', 'legal'])],
            'orientation' => ['required', Rule::in(['portrait', 'landscape'])],
            'allow_print' => 'required|boolean',
            'allow_download' => 'required|boolean',
        ], ['filename.regex' => 'Use letters, numbers, spaces, - _ ( ) and {name} only.']);

        foreach (['preview_enabled', 'allow_print', 'allow_download'] as $flag) {
            $data[$flag] = (bool) $data[$flag];
        }
        if ($data['method'] === 'download' && !$data['allow_download']) {
            return response()->json(['success' => false, 'message' => 'The viewing method is Download, so downloading cannot be disabled.'], 422);
        }

        $before = self::current();
        AppSetting::write(self::KEY, $data);
        $security->audit('cv.pdf_settings', Auth::user(), 'cv_setup', null, 'CV PDF settings changed', ['before' => $before, 'after' => $data]);

        return response()->json(['success' => true, 'message' => 'CV setup saved.', 'data' => self::current()]);
    }

    public function environment(Request $request)
    {
        $link = public_path('storage');
        $target = storage_path('app/public');
        $linked = is_dir($link) && is_dir($target) && realpath($link) === realpath($target);
        $appUrl = rtrim((string) config('app.url'), '/');
        $served = rtrim($request->getSchemeAndHttpHost(), '/');

        $warnings = [];
        if (!$linked) {
            $warnings[] = 'public/storage does not point to the uploaded files. Run: php artisan storage:link';
        }
        if (parse_url($appUrl, PHP_URL_HOST) !== parse_url($served, PHP_URL_HOST)) {
            $warnings[] = "APP_URL ({$appUrl}) is not the address this request used ({$served}). Photo and file links may be wrong.";
        }
        if (app()->environment('production') && config('app.debug')) {
            $warnings[] = 'APP_DEBUG is on in production. Turn it off.';
        }

        return response()->json(['success' => true, 'data' => [
            'environment' => app()->environment('production') ? 'production' : 'local',
            'app_env' => app()->environment(),
            'app_url' => $appUrl,
            'served_url' => $served,
            'storage_url' => rtrim((string) config('filesystems.disks.public.url'), '/'),
            'storage_linked' => $linked,
            'storage_writable' => is_writable(storage_path('app')),
            'warnings' => $warnings,
        ]]);
    }
}
