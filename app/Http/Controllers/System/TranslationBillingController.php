<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\SecurityManagementService;
use App\Services\TranslationBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/** Translation plans and pricing for DEF-ADMIN and DEF-MASTERADMIN (routes use 'subscriptionadmin'). */
class TranslationBillingController extends Controller
{
    public function __construct(private TranslationBillingService $billing, private SecurityManagementService $security) { }

    private function audit(string $action, string $summary, array $details = []): void
    {
        $this->security->audit("translation.{$action}", Auth::user(), 'translation_plan', null, $summary, $details);   // never the key
    }

    private function view(string $message = null, array $extra = []): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $this->billing->view()] + $extra);
    }

    private function attempt(callable $work): JsonResponse
    {
        try {
            return $work();
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /** GET translation-billing */
    public function show(): JsonResponse
    {
        return $this->view();
    }

    /** PUT translation-billing/settings */
    public function settings(Request $request): JsonResponse
    {
        $v = $request->validate([
            'currency' => 'required|in:PHP,USD',
            'pricing_url' => 'required|url|starts_with:https://|max:300',
            'price_per_million' => 'present|nullable|numeric|min:0|max:1000000',
            'free_allowance' => 'required|integer|min:0|max:1000000000',
            'monthly_budget' => 'present|nullable|numeric|min:0|max:100000000',
            'monthly_char_limit' => 'present|nullable|integer|min:0|max:10000000000',
            'expected_characters' => 'required|integer|min:0|max:10000000000',
            'thresholds' => 'required|array|min:1|max:5',
            'thresholds.*' => 'integer|min:1|max:100',
        ]);

        return $this->attempt(function () use ($v) {
            $before = array_intersect_key($this->billing->config(), $v);
            $this->billing->saveSettings($v);
            $this->audit('settings', 'Translation pricing and budget changed', ['before' => $before, 'after' => $v]);

            return $this->view('Settings saved.');
        });
    }

    /** PUT translation-billing/key {api_key} */
    public function saveKey(Request $request): JsonResponse
    {
        $key = $request->validate(['api_key' => ['required', 'string', 'regex:/^[A-Za-z0-9_\-]{20,200}$/']],
            ['api_key.regex' => 'That does not look like a Google API key.'])['api_key'];
        $this->billing->saveKey($key);
        $this->audit('key', 'Google API key saved (plan returned to Free)');

        return $this->view('API key saved. Test the connection, then approve the paid plan.');
    }

    /** DELETE translation-billing/key */
    public function deleteKey(): JsonResponse
    {
        $this->billing->saveKey(null);
        $this->audit('key', 'Google API key removed (plan returned to Free)');

        return $this->view('API key removed.');
    }

    /** POST translation-billing/test */
    public function test(): JsonResponse
    {
        return $this->attempt(function () {
            $result = $this->billing->testConnection();
            $this->audit('test', 'Google connection tested: ' . ($result['ok'] ? 'ok' : 'failed'));

            return $this->view($result['message'], ['test' => $result]);
        });
    }

    /** PUT translation-billing/plan {plan, confirm} */
    public function plan(Request $request): JsonResponse
    {
        $v = $request->validate(['plan' => 'required|in:free,paid', 'confirm' => 'boolean']);

        return $this->attempt(function () use ($v) {
            $this->billing->setPlan($v['plan'], (bool) ($v['confirm'] ?? false), Auth::user());
            $this->audit('plan', "Translation plan set to {$v['plan']}");

            return $this->view($v['plan'] === 'paid' ? 'Paid translation enabled.' : 'Free plan active.');
        });
    }

    /** GET translation/status : any signed-in user; only says whether "See translation" can be offered */
    public function status(): JsonResponse
    {
        return response()->json(['success' => true, 'enabled' => $this->billing->available()]);
    }

    /** GET translation/languages : what a conversation can be translated into ([] while translation is off) */
    public function languages(): JsonResponse
    {
        return $this->attempt(fn () => response()->json(['success' => true, 'data' => $this->billing->supportedLanguages()]));
    }

    /** POST translation/text {text, target} : any signed-in user, one text, when they click "See translation" */
    public function text(Request $request): JsonResponse
    {
        $v = $request->validate([
            'text' => 'required|string|max:5000',
            'target' => ['required', 'string', 'max:20', 'regex:/^[a-z]{2,3}(-[A-Za-z0-9]{2,8})*$/'],
        ]);

        return $this->attempt(function () use ($v) {
            $result = $this->billing->translateText($v['text'], $v['target'], (int) Auth::id());
            if ($result['alert']) { $this->audit('alert', "Translation usage passed {$result['alert']}% of the monthly limit"); }

            return response()->json(['success' => true, 'translated' => $result['translated'], 'detected' => $result['detected']]);
        });
    }

    /** POST translation-billing/translate {locale, texts:{key: English}} : fills missing system texts */
    public function translate(Request $request): JsonResponse
    {
        $v = $request->validate([
            'locale' => ['required', 'string', 'max:20', 'regex:/^[a-z]{2,3}(-[A-Za-z0-9]{2,8})*$/', 'not_regex:/^en(-|$)/'],
            'texts' => 'required|array|max:500',
            'texts.*' => 'required|string|max:2000',
        ]);
        foreach (array_keys($v['texts']) as $key) {
            // only interface and system keys; there is no way to send a post, a CV or a message by its key
            if (!preg_match('/^(web|nav|ls)_[A-Za-z0-9_]{1,110}$/', (string) $key)) {
                return response()->json(['success' => false, 'message' => 'Only system texts can be translated.'], 422);
            }
        }

        return $this->attempt(function () use ($v) {
            $result = $this->billing->translate($v['locale'], $v['texts']);
            $this->audit('translate', "Google translation to {$v['locale']}: {$result['translated']} texts, {$result['characters']} characters", $result);
            if ($result['alert']) { $this->audit('alert', "Translation usage passed {$result['alert']}% of the monthly limit"); }

            return $this->view(null, ['result' => $result]);
        });
    }
}
