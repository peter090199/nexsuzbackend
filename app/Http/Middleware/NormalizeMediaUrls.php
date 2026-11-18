<?php

namespace App\Http\Middleware;

use App\Helpers\MediaUrl;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Makes every profile photo, cover photo, post image and comment image in an API response
 * point at THIS server's storage, whatever host was saved in the database.
 *
 * Why: rows created on other hosts (api.nexsuz.com, exploredition.com) or by older code
 * (/storage/app/public/...) hold URLs that do not resolve here. They are rewritten on the way
 * out, centrally, instead of in the dozens of queries that return a photo.
 *
 * Only values that match one of the known media paths (see MediaUrl) are touched, object/array
 * shapes are preserved, and the cover column's "no cover" markers become null.
 */
class NormalizeMediaUrls
{
    private const MAX_BYTES = 4000000;

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!$response instanceof JsonResponse) {
            return $response;
        }

        $raw = $response->getContent();
        if ($raw === false || $raw === null || strlen($raw) > self::MAX_BYTES
            || (strpos($raw, 'uploads') === false && strpos($raw, 'default.jpg') === false)) {
            return $response;      // nothing to rewrite: skip the decode/encode
        }

        $response->setData($this->walk($response->getData(false)));

        return $response;
    }

    private function walk($value, ?string $key = null)
    {
        if (is_array($value)) {
            foreach ($value as $k => $item) {
                $value[$k] = $this->walk($item, is_string($k) ? $k : null);
            }

            return $value;
        }

        if (is_object($value)) {
            foreach (get_object_vars($value) as $k => $item) {
                $value->$k = $this->walk($item, (string) $k);
            }

            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        // the cover column defaults to the bare name "default.jpg": that means "no cover photo"
        if ($key === 'coverphoto' && ($value === 'default.jpg' || $value === '')) {
            return null;
        }

        return strpos($value, 'uploads/') === false ? $value : MediaUrl::normalize($value);
    }
}
