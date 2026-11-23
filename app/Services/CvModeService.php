<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;

/**
 * How job seekers provide their CV (set by the master admin in Settings).
 *
 *  - builder (the default, how the app always worked): they fill up the CV form step by step
 *  - upload only: the CV page shows only "upload your CV / resume"
 *
 * Stored in app_settings. If that table is missing (migration not run) or unreadable the answer is "builder", so a
 * problem here can never lock job seekers out of the CV page.
 */
class CvModeService
{
    public const KEY = 'cv.mode';

    public static function uploadOnly(): bool
    {
        try {
            if (!Schema::hasTable('app_settings')) {
                return false;
            }

            return (bool) (AppSetting::read(self::KEY)['upload_only'] ?? false);
        } catch (\Throwable) {
            return false;
        }
    }

    public static function set(bool $uploadOnly): void
    {
        AppSetting::write(self::KEY, ['upload_only' => $uploadOnly]);
    }

    public static function mode(): string
    {
        return self::uploadOnly() ? 'upload' : 'builder';
    }
}
