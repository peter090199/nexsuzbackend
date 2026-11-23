<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Dynamic SMTP configuration.
 *
 * Saved settings live in app_settings (key mail.smtp, password encrypted with Laravel's Crypt). When nothing is saved,
 * the .env values keep working exactly as before. Saved settings are applied to Laravel's mail config at runtime the
 * moment the mail manager is first used in a request, so a change needs no restart and no .env edit.
 */
class MailConfigService
{
    public const KEY = 'mail.smtp';
    public const STATUS_KEY = 'mail.status';

    /** Standard defaults per provider. Served to the UI so no host is hard-coded in Angular. */
    public const PRESETS = [
        'hostinger' => ['label' => 'Hostinger', 'host' => 'smtp.hostinger.com', 'port' => 465, 'encryption' => 'ssl',
            'note' => 'Use your full mailbox address as the username.'],
        'gmail' => ['label' => 'Gmail', 'host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls',
            'note' => 'Gmail needs an App Password (2-step verification must be on). Your normal password will not work.'],
        'outlook' => ['label' => 'Outlook / Microsoft 365', 'host' => 'smtp.office365.com', 'port' => 587, 'encryption' => 'tls',
            'note' => 'SMTP AUTH must be enabled for the mailbox in Microsoft 365.'],
        'custom' => ['label' => 'Custom SMTP', 'host' => '', 'port' => 587, 'encryption' => 'tls', 'note' => 'Enter the values from your mail provider.'],
    ];

    /** The .env / config values as they were before any saved setting was applied. */
    private static ?array $envSnapshot = null;

    public static function snapshotEnv(): void
    {
        if (self::$envSnapshot === null) {
            self::$envSnapshot = [
                'default' => config('mail.default'),
                'smtp' => config('mail.mailers.smtp'),
                'from' => config('mail.from'),
            ];
        }
    }

    // --------------------------------------------------------------- storage

    public function tableReady(): bool
    {
        try { return Schema::hasTable('app_settings'); } catch (\Throwable) { return false; }
    }

    /** Saved settings (password still encrypted), or null. */
    public function stored(): ?array
    {
        return $this->tableReady() ? AppSetting::read(self::KEY) : null;
    }

    public function status(): array
    {
        $s = $this->tableReady() ? AppSetting::read(self::STATUS_KEY) : null;

        return ($s ?? []) + ['connection' => 'not_tested', 'last_test_at' => null, 'last_success_at' => null, 'last_error' => null];
    }

    public function saveStatus(array $patch): void
    {
        if ($this->tableReady()) {
            AppSetting::write(self::STATUS_KEY, array_merge($this->status(), $patch));
        }
    }

    public function envSettings(): array
    {
        self::snapshotEnv();
        $smtp = self::$envSnapshot['smtp'] ?? [];
        $from = self::$envSnapshot['from'] ?? [];
        $enc = $smtp['encryption'] ?? null;

        return [
            'provider' => $this->guessProvider((string) ($smtp['host'] ?? '')),
            'host' => (string) ($smtp['host'] ?? ''), 'port' => (int) ($smtp['port'] ?? 587),
            'encryption' => $enc ?: ((int) ($smtp['port'] ?? 0) === 465 ? 'ssl' : 'none'),
            'username' => (string) ($smtp['username'] ?? ''), 'password' => (string) ($smtp['password'] ?? ''),
            'from_address' => (string) ($from['address'] ?? ''), 'from_name' => (string) ($from['name'] ?? ''),
        ];
    }

    private function guessProvider(string $host): string
    {
        foreach (self::PRESETS as $id => $p) {
            if ($p['host'] !== '' && strcasecmp($p['host'], $host) === 0) { return $id; }
        }
        return 'custom';
    }

    /** Decrypted settings in use: saved ones, else the .env fallback. */
    public function effective(): array
    {
        $s = $this->stored();
        if (!$s) { return $this->envSettings() + ['enabled' => true, 'source' => 'env']; }

        // array_merge, not "+": the decrypted password must replace the stored ciphertext
        return array_merge($s, ['password' => $this->decrypt($s['password'] ?? null), 'source' => 'database']);
    }

    public function encrypt(string $plain): string { return Crypt::encryptString($plain); }

    private function decrypt(?string $cipher): string
    {
        if (!$cipher) { return ''; }
        try { return Crypt::decryptString($cipher); } catch (\Throwable) { return ''; }   // e.g. APP_KEY changed
    }

    /** What the UI may see. The password is never included, only whether one is stored. */
    public function publicView(): array
    {
        $stored = $this->stored();
        $e = $this->effective();
        $env = $this->envSettings();

        return [
            'mailer' => 'smtp',
            'providers' => self::PRESETS,
            'saved' => (bool) $stored,
            'source' => $stored ? 'database' : 'env',
            'settings' => [
                'provider' => $e['provider'], 'host' => $e['host'], 'port' => (int) $e['port'], 'encryption' => $e['encryption'],
                'username' => $e['username'], 'from_address' => $e['from_address'], 'from_name' => $e['from_name'],
                'has_password' => $e['password'] !== '',
            ],
            'env_fallback' => ['host' => $env['host'], 'port' => $env['port'], 'encryption' => $env['encryption'], 'username' => $env['username'],
                'from_address' => $env['from_address'], 'from_name' => $env['from_name']],
            'status' => ['enabled' => (bool) ($stored['enabled'] ?? true)] + $this->status(),
        ];
    }

    // ------------------------------------------------------------ mailer config

    /** Laravel mailer array for a settings array (password already decrypted). */
    public static function mailerConfig(array $s): array
    {
        $enc = $s['encryption'] ?? 'none';

        return [
            'transport' => 'smtp', 'host' => $s['host'], 'port' => (int) $s['port'],
            'username' => $s['username'] ?: null, 'password' => ($s['password'] ?? '') !== '' ? $s['password'] : null,
            'encryption' => $enc === 'none' ? null : $enc,
            // explicit scheme: implicit TLS (smtps) for SSL, otherwise plain SMTP that upgrades with STARTTLS when offered
            'scheme' => $enc === 'ssl' ? 'smtps' : 'smtp',
            'auto_tls' => $enc !== 'none',
            'timeout' => 15,
        ];
    }

    /** Push the saved settings into Laravel's mail config for this process. No-op when nothing is saved. */
    public function apply(): void
    {
        self::snapshotEnv();
        $s = $this->stored();
        if (!$s) { return; }

        if (empty($s['enabled'])) {
            config(['mail.default' => 'array']);          // service switched off: nothing is sent
            return;
        }
        $e = $this->effective();
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => self::mailerConfig($e),
            'mail.from' => ['address' => $e['from_address'], 'name' => $e['from_name']],
        ]);
    }

    /** Re-apply right now (after a save) and drop any mailer already built in this process. */
    public function reapply(): void
    {
        $this->restoreEnv();
        $this->apply();
        foreach (['smtp', 'array', 'log'] as $name) { Mail::purge($name); }
    }

    private function restoreEnv(): void
    {
        self::snapshotEnv();
        config(['mail.default' => self::$envSnapshot['default'], 'mail.mailers.smtp' => self::$envSnapshot['smtp'], 'mail.from' => self::$envSnapshot['from']]);
    }

    // ------------------------------------------------------------------ actions

    /** Merge a request over the saved settings; a blank password keeps the saved (or .env) one. */
    public function resolve(array $override = []): array
    {
        $base = $this->effective();
        $out = array_merge($base, array_filter($override, fn ($v) => $v !== null && $v !== ''));
        if (($override['password'] ?? '') === '') { $out['password'] = $base['password']; }

        return $out;
    }

    /**
     * Connect and authenticate against the SMTP server without sending anything.
     * @return array{ok:bool,message:string}
     */
    public function testConnection(array $s): array
    {
        try {
            $transport = Mail::createSymfonyTransport(self::mailerConfig($s));
            $transport->start();      // connects, negotiates TLS and authenticates; nothing is sent
            $transport->stop();

            return ['ok' => true, 'message' => 'Connected and signed in to ' . $s['host'] . ':' . $s['port'] . '.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $this->safeError($e, $s['password'] ?? '')];
        }
    }

    /** Really send a message through the given settings. @return array{ok:bool,message:string} */
    public function sendTest(array $s, string $to, string $appName): array
    {
        $name = 'ondemand_' . bin2hex(random_bytes(4));
        try {
            config(["mail.mailers.{$name}" => self::mailerConfig($s)]);
            Mail::mailer($name)->raw(
                "This is a test email from {$appName}.\n\nIf you can read this, your SMTP settings work.\nSent: " . now()->toDateTimeString(),
                fn ($m) => $m->to($to)->subject("{$appName}: SMTP test email")->from($s['from_address'], $s['from_name'])
            );

            return ['ok' => true, 'message' => 'Test email sent to ' . $to . '.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $this->safeError($e, $s['password'] ?? '')];
        } finally {
            Mail::purge($name);
            config(["mail.mailers.{$name}" => null]);
        }
    }

    /** Error text safe to show and store: no password, no stack, bounded length. */
    public function safeError(\Throwable $e, string $password = ''): string
    {
        $msg = $e->getMessage();
        if ($password !== '') { $msg = str_replace($password, '***', $msg); }
        $msg = trim(preg_replace('/\s+/', ' ', $msg));

        return mb_substr($msg !== '' ? $msg : 'The SMTP server could not be reached.', 0, 300);
    }

    /** Refuses hosts that point at private / loopback / link-local addresses outside local development. */
    public function hostAllowed(string $host): bool
    {
        if (app()->environment('local', 'testing')) { return true; }
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) { return true; }   // unresolved: the connection test will fail on its own

        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    public function persist(array $s, bool $enabled): void
    {
        AppSetting::write(self::KEY, [
            'provider' => $s['provider'], 'host' => $s['host'], 'port' => (int) $s['port'], 'encryption' => $s['encryption'],
            'username' => $s['username'], 'password' => $this->encrypt((string) ($s['password'] ?? '')),
            'from_address' => $s['from_address'], 'from_name' => $s['from_name'], 'enabled' => $enabled,
        ]);
    }

    public function forget(): void
    {
        AppSetting::forget(self::KEY);
        AppSetting::forget(self::STATUS_KEY);
    }
}
