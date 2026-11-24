<?php

namespace App\Helpers;

/**
 * Single place that knows how uploaded media is addressed.
 *
 * Everything lives on the "public" disk and is served through the public/storage symlink:
 *
 *   uploads/posts/{userCode}/{postUuid}/{file}        post images / videos
 *   uploads/announcements/{userCode}/{uuid}/{file}    announcement images / attachments
 *   uploads/comments/{userCode}/{file}                comment images
 *   uploads/{userCode}/cvphoto/{uuid}/{file}          profile photo
 *   uploads/{userCode}/coverphoto/{uuid}/{file}       cover photo
 *   uploads/DEFAULTPROFILE/DEFAULTPROFILE.png         default avatar
 *
 * Older rows stored absolute URLs on other hosts (api.nexsuz.com, exploredition.com) and shapes
 * such as /storage/app/public/uploads/...  normalize() maps every historical shape to the correct
 * URL for the CURRENT host, so no stored data has to be rewritten.
 */
class MediaUrl
{
    private const PATTERNS = [
        '#uploads/posts/.+$#',
        '#uploads/announcements/.+$#',
        '#uploads/comments/.+$#',
        '#uploads/\d+/(?:cvphoto|coverphoto)/.+$#',
        '#uploads/DEFAULTPROFILE/.+$#',
    ];

    /** Path relative to the public disk (uploads/...), or null if the value is not one of our media files. */
    public static function relativePath(?string $stored): ?string
    {
        if (!$stored) {
            return null;
        }
        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $stored, $m)) {
                return $m[0];
            }
        }

        return null;
    }

    /** Browser-ready absolute URL for any stored value (non-media values are returned unchanged). */
    public static function normalize(?string $stored): ?string
    {
        $relative = self::relativePath($stored);

        return $relative ? url('storage/' . $relative) : $stored;
    }

    /** Map the path_url of every item in a list of rows/models. */
    public static function normalizeAll($rows)
    {
        foreach ($rows as $row) {
            $row->path_url = self::normalize($row->path_url ?? null);
        }

        return $rows;
    }
}
