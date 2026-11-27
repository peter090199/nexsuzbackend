<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Writes only app_settings, users and security_audit_logs, which are InnoDB and roll back. Google is faked. */
class TranslationBillingTest extends TestCase
{
    use DatabaseTransactions;

    private const KEY = 'AIzaTEST_key_1234567890abcdWXYZ';

    protected function setUp(): void
    {
        parent::setUp();
        AppSetting::where('setting_key', 'like', 'i18n.google.%')->orWhere('setting_key', 'i18n.text.da')->delete();
        // any request that is not faked below fails the test instead of reaching Google
        Http::preventStrayRequests();
    }

    private function as(?string $role): void
    {
        $this->app['auth']->forgetGuards();
        if (!$role) { return; }
        static $next = 887500;
        $code = $next += random_int(2, 20);
        Sanctum::actingAs(User::forceCreate(['fname' => 'T', 'lname' => 'B', 'fullname' => 'T B', 'role_code' => $role,
            'email' => "bill{$code}@example.test", 'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']));
    }

    private function fakeGoogle(int $status = 200): void
    {
        Http::fake([
            'translation.googleapis.com/language/translate/v2/languages*' => Http::response($status === 200 ? ['data' => ['languages' => []]] : ['error' => ['message' => 'API key not valid.']], $status),
            'translation.googleapis.com/language/translate/v2' => fn ($request) => Http::response(['data' => ['translations' => array_map(fn ($q) => ['translatedText' => 'da: ' . str_replace('{9}', '', $q)], $request['q'])]]),
        ]);
    }

    private function settings(array $over = []): array
    {
        return $over + ['currency' => 'USD', 'pricing_url' => 'https://cloud.google.com/translate/pricing', 'price_per_million' => 20,
            'free_allowance' => 0, 'monthly_budget' => 10, 'monthly_char_limit' => null, 'expected_characters' => 0, 'thresholds' => [50, 80, 100]];
    }

    private function enablePaid(array $over = []): void
    {
        $this->fakeGoogle();
        $this->putJson('/api/translation-billing/settings', $this->settings($over))->assertOk();
        $this->putJson('/api/translation-billing/key', ['api_key' => self::KEY])->assertOk();
        $this->postJson('/api/translation-billing/test')->assertOk()->assertJsonPath('test.ok', true);
        $this->putJson('/api/translation-billing/plan', ['plan' => 'paid', 'confirm' => true])->assertOk();
    }

    private function translate(array $texts)
    {
        return $this->postJson('/api/translation-billing/translate', ['locale' => 'da', 'texts' => $texts]);
    }

    public function test_only_administrators_may_read_or_change_anything(): void
    {
        foreach ([[null, 401], ['DEF-USERS', 403], ['DEF-CLIENT', 403]] as [$role, $status]) {
            $this->as($role);
            $this->getJson('/api/translation-billing')->assertStatus($status);
            $this->putJson('/api/translation-billing/plan', ['plan' => 'free'])->assertStatus($status);
            $this->putJson('/api/translation-billing/key', ['api_key' => self::KEY])->assertStatus($status);
            $this->translate(['web_x' => 'Hello'])->assertStatus($status);
        }
    }

    public function test_see_translation_is_for_signed_in_users_only_while_paid_and_inside_the_limits(): void
    {
        $send = fn (string $text) => $this->postJson('/api/translation/text', ['text' => $text, 'target' => 'ja']);
        $this->as(null);
        $send('Hello')->assertStatus(401);
        $this->as('DEF-USERS');
        $this->getJson('/api/translation/status')->assertOk()->assertJsonPath('enabled', false);
        $send('Hello')->assertStatus(422);

        $this->as('DEF-ADMIN');
        $this->enablePaid(['price_per_million' => 1000000, 'monthly_budget' => 20]);
        $this->as('DEF-USERS');
        $this->getJson('/api/translation/status')->assertJsonPath('enabled', true);
        \Illuminate\Support\Facades\Cache::forget('tr-languages');
        $this->getJson('/api/translation/languages')->assertOk()->assertJsonPath('data', []);   // the faked list is empty, so nothing is cached
        $words = 'hi ' . uniqid();                                   // unique, so no cached copy from an earlier run
        $send($words)->assertOk()->assertJsonPath('translated', "da: {$words}");
        $send($words)->assertOk();                                   // second time comes from the cache, not billed
        $this->assertSame(mb_strlen($words), array_values(AppSetting::read('i18n.google.usage'))[0]['characters']);
        $this->assertStringNotContainsString($words, DB::table('security_audit_logs')->get()->toJson());

        $send(str_repeat('x', 100) . uniqid())->assertStatus(422)->assertJsonPath('message', 'Translation is paused for this month.');
    }

    public function test_free_is_the_default_and_never_calls_google(): void
    {
        $this->as('DEF-ADMIN');
        $this->getJson('/api/translation-billing')->assertOk()->assertJsonPath('data.config.plan', 'free')->assertJsonPath('data.key.saved', false)
            ->assertJsonPath('data.current.estimated_cost', 0)->assertJsonPath('data.config.price_per_million', null);
        $this->translate(['web_x' => 'Hello'])->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_paid_needs_a_tested_key_a_price_a_budget_and_approval(): void
    {
        $this->as('DEF-MASTERADMIN');
        $paid = fn ($confirm = true) => $this->putJson('/api/translation-billing/plan', ['plan' => 'paid', 'confirm' => $confirm]);
        $this->fakeGoogle();

        $paid()->assertStatus(422);                                                                    // no key
        $this->putJson('/api/translation-billing/key', ['api_key' => self::KEY])->assertOk();
        $paid()->assertStatus(422);                                                                    // not tested
        $this->postJson('/api/translation-billing/test')->assertOk();
        $paid()->assertStatus(422);                                                                    // no price or budget
        $this->putJson('/api/translation-billing/settings', $this->settings())->assertOk();
        $paid(false)->assertStatus(422);                                                               // not approved
        $paid()->assertOk()->assertJsonPath('data.config.plan', 'paid');
        $this->putJson('/api/translation-billing/settings', $this->settings(['monthly_budget' => null]))->assertStatus(422);

        $this->putJson('/api/translation-billing/plan', ['plan' => 'free'])->assertOk()->assertJsonPath('data.config.plan', 'free');
        $this->translate(['web_x' => 'Hello'])->assertStatus(422);
        $paid()->assertOk();
        $this->putJson('/api/translation-billing/key', ['api_key' => self::KEY . 'new'])->assertOk()->assertJsonPath('data.config.plan', 'free');
    }

    public function test_a_failing_key_cannot_enable_paid(): void
    {
        $this->as('DEF-ADMIN');
        $this->fakeGoogle(400);
        $this->putJson('/api/translation-billing/settings', $this->settings())->assertOk();
        $this->putJson('/api/translation-billing/key', ['api_key' => self::KEY])->assertOk();
        $this->postJson('/api/translation-billing/test')->assertOk()->assertJsonPath('test.ok', false)->assertJsonPath('message', 'API key not valid.');
        $this->putJson('/api/translation-billing/plan', ['plan' => 'paid', 'confirm' => true])->assertStatus(422);
    }

    public function test_the_key_is_encrypted_masked_and_never_logged_or_returned(): void
    {
        $this->as('DEF-ADMIN');
        $response = $this->putJson('/api/translation-billing/key', ['api_key' => self::KEY])->assertOk()->assertJsonPath('data.key.masked', '••••••••WXYZ');
        $this->assertStringNotContainsString(self::KEY, $response->getContent());
        $this->assertStringNotContainsString(self::KEY, AppSetting::where('setting_key', 'i18n.google.key')->value('value'));
        $this->assertStringNotContainsString(self::KEY, DB::table('security_audit_logs')->where('action', 'like', 'translation.%')->get()->toJson());
        $this->assertTrue(DB::table('security_audit_logs')->where('action', 'translation.key')->exists());
        $this->as(null);
        $this->assertStringNotContainsString(self::KEY, $this->getJson('/api/i18n/translations/da')->getContent() . $this->getJson('/api/i18n/config')->getContent());
    }

    public function test_usage_cost_and_alerts_are_tracked_and_each_text_is_paid_once(): void
    {
        $this->as('DEF-ADMIN');
        // 1 per character above a 10 character allowance
        $this->enablePaid(['price_per_million' => 1000000, 'free_allowance' => 10, 'monthly_budget' => 20]);

        $this->translate(['web_a' => str_repeat('a', 25)])->assertOk()->assertJsonPath('result.translated', 1)->assertJsonPath('result.alert', 50)
            ->assertJsonPath('data.current.characters', 25)->assertJsonPath('data.current.allowance_used', 10)
            ->assertJsonPath('data.current.paid_characters', 15)->assertJsonPath('data.current.estimated_cost', 15)
            ->assertJsonPath('data.current.budget_percent', 75);
        $this->assertSame('da: ' . str_repeat('a', 25), AppSetting::read('i18n.text.da')['web_a']);

        $this->translate(['web_a' => str_repeat('a', 25)])->assertOk()->assertJsonPath('result.translated', 0)->assertJsonPath('data.current.characters', 25);
        // the other administrator sees the same figures: one source
        $this->as('DEF-MASTERADMIN');
        $this->getJson('/api/translation-billing')->assertJsonPath('data.current.characters', 25)->assertJsonPath('data.history.0.estimated_cost', 15);
    }

    public function test_nothing_is_sent_past_the_budget_or_the_character_limit(): void
    {
        $this->as('DEF-ADMIN');
        $this->enablePaid(['price_per_million' => 1000000, 'monthly_budget' => 10]);
        $this->translate(['web_a' => str_repeat('a', 11)])->assertOk()->assertJsonPath('result.blocked', true)->assertJsonPath('data.current.characters', 0);
        $this->translate(['web_b' => str_repeat('b', 10)])->assertOk()->assertJsonPath('result.blocked', false)->assertJsonPath('result.alert', 100)
            ->assertJsonPath('data.current.blocked', true);
        $this->translate(['web_c' => 'c'])->assertJsonPath('result.blocked', true);

        $this->putJson('/api/translation-billing/settings', $this->settings(['monthly_char_limit' => 10]))->assertOk();
        $this->translate(['web_d' => 'd'])->assertJsonPath('result.blocked', true);
        Http::assertSentCount(2);        // the connection test and the one translation inside the budget
    }

    public function test_only_system_keys_and_complete_placeholders_are_accepted(): void
    {
        $this->as('DEF-ADMIN');
        $this->enablePaid();
        $this->translate(['post_1' => 'A private post'])->assertStatus(422);
        $this->postJson('/api/translation-billing/translate', ['locale' => 'en-US', 'texts' => ['web_a' => 'x']])->assertStatus(422);
        // Google dropped the {9}: that translation is not saved
        $this->translate(['web_p' => 'Pay {9}', 'web_q' => 'Keep'])->assertOk()->assertJsonPath('result.translated', 1)->assertJsonPath('result.skipped', 1);
        $this->assertSame(['web_q'], array_keys(AppSetting::read('i18n.text.da')));
    }
}
