<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Run once on a server after deploying (local or production):
 *   php artisan cv:check-environment
 *
 * Checks what CV Setup reports, and goes further: it saves a small test file to the public disk, fetches it through
 * the storage URL like a browser would, and removes it again. Exit code 0 = everything works.
 */
class CheckCvEnvironment extends Command
{
    protected $signature = 'cv:check-environment {--skip-http : do not fetch the test file over the network}';

    protected $description = 'Check APP_URL, the storage link and that stored files are reachable over the web';

    public function handle(): int
    {
        $failed = 0;
        $check = function (bool $ok, string $label, string $fix = '') use (&$failed) {
            $this->line(($ok ? '<info>  OK  </info> ' : '<error> FAIL </error> ') . $label . ($ok || !$fix ? '' : "\n        -> {$fix}"));
            $failed += $ok ? 0 : 1;
        };

        $production = app()->environment('production');
        $appUrl = rtrim((string) config('app.url'), '/');
        $storageUrl = rtrim((string) config('filesystems.disks.public.url'), '/');
        $this->info('Environment: ' . ($production ? 'production' : 'local') . ' (APP_ENV=' . app()->environment() . ')');
        $this->line("APP_URL:     {$appUrl}");
        $this->line("Storage URL: {$storageUrl}");

        $check(filter_var($appUrl, FILTER_VALIDATE_URL) !== false, 'APP_URL is a full address', 'Set APP_URL in .env, e.g. https://api.example.com');
        $check(!$production || str_starts_with($appUrl, 'https://'), 'APP_URL uses https in production', 'Use https:// in APP_URL');
        $check(!$production || !config('app.debug'), 'APP_DEBUG is off in production', 'Set APP_DEBUG=false');
        $check(!$production || !preg_match('/localhost|127\.0\.0\.1|192\.168\.|10\.\d+\./', $appUrl), 'APP_URL is not a local address in production', 'Set the public address in APP_URL');

        $link = public_path('storage');
        $target = storage_path('app/public');
        $check(is_dir($link) && realpath($link) === realpath($target), 'public/storage points to storage/app/public', 'Run: php artisan storage:link');
        $check(is_writable(storage_path('app')) && is_writable($target), 'storage is writable', 'Give the web server user write access to storage/');

        if ($this->option('skip-http')) {
            $this->warn('Skipped fetching the test file over the network.');
        } else {
            $name = 'cv-check-' . Str::random(12) . '.txt';
            $marker = 'nexsuz ' . Str::random(16);
            try {
                Storage::disk('public')->put($name, $marker);
                $url = Storage::disk('public')->url($name);
                $response = Http::timeout(15)->get($url);
                $check($response->successful() && trim($response->body()) === $marker, "a stored file opens at {$url}",
                    $response->successful() ? 'The address answered, but with other content: check the web server serves public/storage'
                        : "HTTP {$response->status()}: check APP_URL, storage:link and the web server's document root");
            } catch (\Throwable $e) {
                $check(false, 'a stored file can be fetched over the web', $e->getMessage());
            } finally {
                Storage::disk('public')->delete($name);
            }
        }

        $this->newLine();
        $failed ? $this->error("{$failed} check(s) failed.") : $this->info('All checks passed.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
