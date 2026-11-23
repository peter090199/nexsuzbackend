<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveEmailConfigRequest;
use App\Services\MailConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Master-admin SMTP configuration. The password is accepted but never returned or logged.
 * Saving or enabling always proves the credentials against the real SMTP server first.
 */
class EmailConfigController extends Controller
{
    public function __construct(private MailConfigService $mail) { }

    private function audit(string $action, array $context = []): void
    {
        Log::info("email-config: {$action}", ['by' => Auth::user()->code ?? null] + $context);   // never the password
    }

    private function view(string $message = null, int $status = 200, array $extra = []): JsonResponse
    {
        return response()->json(['success' => $status < 400, 'message' => $message, 'data' => $this->mail->publicView()] + $extra, $status);
    }

    private function fail(string $message, int $status = 422, array $extra = []): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message] + $extra, $status);
    }

    private function ready(): ?JsonResponse
    {
        return $this->mail->tableReady() ? null
            : $this->fail('The settings table is missing. Run the app_settings migration first.', 503);
    }

    /** GET /email-config */
    public function show(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->mail->publicView()]);
    }

    /** POST /email-config : first-time save. Proves the credentials, then stores them enabled. */
    public function store(SaveEmailConfigRequest $request): JsonResponse
    {
        if ($r = $this->ready()) { return $r; }
        if ($this->mail->stored()) { return $this->fail('A configuration is already saved. Use Update instead.', 409); }

        return $this->saveValidated($request, 'saved');
    }

    /** PUT /email-config : change the saved settings. A blank password keeps the stored one. */
    public function update(SaveEmailConfigRequest $request): JsonResponse
    {
        if ($r = $this->ready()) { return $r; }
        if (!$this->mail->stored()) { return $this->fail('Nothing is saved yet. Use Save Configuration first.', 404); }

        return $this->saveValidated($request, 'updated');
    }

    private function saveValidated(SaveEmailConfigRequest $request, string $verb): JsonResponse
    {
        $input = $request->validated();
        if (!$this->mail->hostAllowed($input['host'])) {
            return $this->fail('Private or local network addresses are not allowed as the SMTP host.');
        }

        $settings = $this->mail->resolve($input);
        if (($settings['password'] ?? '') === '') { return $this->fail('Enter the SMTP password.'); }

        // never replace working settings with ones that do not authenticate
        $test = $this->mail->testConnection($settings);
        $this->mail->saveStatus(['connection' => $test['ok'] ? 'connected' : 'failed', 'last_test_at' => now()->toDateTimeString(),
            'last_error' => $test['ok'] ? null : $test['message']]);
        if (!$test['ok']) {
            $this->audit("{$verb} rejected (connection test failed)", ['host' => $settings['host']]);
            return $this->fail('Not saved. The SMTP server refused the connection: ' . $test['message'], 422, ['test' => $test]);
        }

        $enabled = (bool) ($this->mail->stored()['enabled'] ?? true);
        $this->mail->persist($settings, $enabled);
        $this->mail->reapply();
        $this->audit($verb, ['host' => $settings['host'], 'port' => $settings['port'], 'username' => $settings['username'], 'enabled' => $enabled]);

        return $this->view('SMTP configuration ' . $verb . '. The connection was verified.', 200, ['test' => $test]);
    }

    /** POST /email-config/test-connection : optional unsaved form values; nothing is sent. */
    public function testConnection(Request $request): JsonResponse
    {
        // with form values: test those (unsaved); with an empty body: test what is saved / in .env
        $override = $request->filled('host') ? $request->validate(SaveEmailConfigRequest::fields(false)) : [];
        $settings = $this->mail->resolve($override);
        if (!$this->mail->hostAllowed($settings['host'])) {
            return $this->fail('Private or local network addresses are not allowed as the SMTP host.');
        }
        $test = $this->mail->testConnection($settings);

        if ($this->mail->tableReady()) {
            $this->mail->saveStatus(['connection' => $test['ok'] ? 'connected' : 'failed', 'last_test_at' => now()->toDateTimeString(),
                'last_error' => $test['ok'] ? null : $test['message']]);
        }
        $this->audit('connection test', ['host' => $settings['host'], 'ok' => $test['ok']]);

        return response()->json(['success' => $test['ok'], 'message' => $test['message'], 'data' => $this->mail->publicView()], $test['ok'] ? 200 : 422);
    }

    /** POST /email-config/test-email : really sends one message through the given (or saved) settings. */
    public function testEmail(Request $request): JsonResponse
    {
        $data = $request->validate(['to' => ['required', 'email:rfc', 'max:190']]);
        $override = $request->filled('settings')
            ? Validator::make((array) $request->input('settings'), SaveEmailConfigRequest::fields(false))->validate()
            : [];

        $settings = $this->mail->resolve($override);
        if (!$this->mail->hostAllowed($settings['host'])) {
            return $this->fail('Private or local network addresses are not allowed as the SMTP host.');
        }

        $result = $this->mail->sendTest($settings, $data['to'], config('app.name', 'Nexsuz'));
        if ($this->mail->tableReady()) {
            $this->mail->saveStatus($result['ok']
                ? ['last_success_at' => now()->toDateTimeString(), 'last_error' => null]
                : ['last_error' => $result['message']]);
        }
        $this->audit('test email', ['to_domain' => substr(strrchr($data['to'], '@') ?: '', 1), 'ok' => $result['ok']]);

        return response()->json(['success' => $result['ok'], 'message' => $result['message'], 'data' => $this->mail->publicView()], $result['ok'] ? 200 : 422);
    }

    /** POST /email-config/toggle : enabling re-checks the credentials first. */
    public function toggle(Request $request): JsonResponse
    {
        if ($r = $this->ready()) { return $r; }
        $enable = (bool) $request->validate(['enabled' => ['required', 'boolean']])['enabled'];

        $stored = $this->mail->stored();
        $settings = $this->mail->effective();   // saved settings, or the .env ones when nothing is saved yet

        if ($enable) {
            $test = $this->mail->testConnection($settings);
            $this->mail->saveStatus(['connection' => $test['ok'] ? 'connected' : 'failed', 'last_test_at' => now()->toDateTimeString(),
                'last_error' => $test['ok'] ? null : $test['message']]);
            if (!$test['ok']) {
                return $this->fail('Email was not enabled. The SMTP server refused the connection: ' . $test['message'], 422, ['test' => $test]);
            }
        }

        // with nothing saved, "disable" snapshots the current .env settings so they can be switched back on later
        $this->mail->persist($settings, $enable);
        $this->mail->reapply();
        $this->audit($enable ? 'enabled' : 'disabled', ['was_saved' => (bool) $stored]);

        return $this->view($enable ? 'Email service enabled.' : 'Email service disabled. No emails will be sent until it is enabled again.');
    }

    /** DELETE /email-config : back to the .env defaults. */
    public function reset(): JsonResponse
    {
        if ($r = $this->ready()) { return $r; }
        $this->mail->forget();
        $this->mail->reapply();
        $this->audit('reset to .env defaults');

        return $this->view('Reset. The server\'s .env mail settings are in use again.');
    }
}
