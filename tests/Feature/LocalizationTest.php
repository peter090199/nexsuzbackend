<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Country and language setup. Live settings are cleared inside the rolled-back transaction, never changed for good. */
class LocalizationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        AppSetting::where('setting_key', 'like', 'i18n.%')->delete();
    }

    private function as(?string $role): ?User
    {
        $this->app['auth']->forgetGuards();
        if (!$role) { return null; }
        static $next = 897500;
        $code = $next += random_int(2, 20);
        $u = User::forceCreate(['fname' => 'L', 'lname' => 'T', 'fullname' => 'L T', 'role_code' => $role, 'email' => "loc{$code}@example.test",
            'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']);
        Sanctum::actingAs($u);

        return $u;
    }

    public function test_reference_data_is_complete_and_keeps_countries_and_languages_separate(): void
    {
        $this->as('DEF-MASTERADMIN');
        $r = $this->getJson('/api/i18n/admin')->assertOk();
        $countries = collect($r->json('countries'))->keyBy('code');
        $languages = collect($r->json('languages'))->keyBy('code');

        $this->assertGreaterThanOrEqual(249, $countries->count());
        $this->assertSame(['PHL', '+63'], [$countries['PH']['code3'], $countries['PH']['dial']]);
        foreach (['en-US', 'en-GB', 'fil-PH', 'es-ES', 'es-MX', 'fr-FR', 'fr-CA', 'pt-BR', 'pt-PT', 'zh-CN', 'zh-TW'] as $code) {
            $this->assertArrayHasKey($code, $languages->all());
        }
        $this->assertTrue($languages['ar']['rtl']);
        $this->assertContains('PH', $languages['en']['countries']);          // one country, several languages
        $this->assertContains('US', $languages['en']['countries']);          // one language, several countries
    }

    public function test_only_the_master_admin_manages_the_setup(): void
    {
        foreach ([null => 401, 'DEF-CLIENT' => 403, 'DEF-ADMIN' => 403] as $role => $status) {
            $this->as($role ?: null);
            $this->getJson('/api/i18n/admin')->assertStatus($status);
            $this->putJson('/api/i18n/countries/PH', ['active' => false])->assertStatus($status);
            $this->putJson('/api/i18n/translations/fil-PH', ['values' => ['nav_home' => 'x']])->assertStatus($status);
        }
    }

    public function test_activating_defaulting_and_adding_languages(): void
    {
        $this->as('DEF-MASTERADMIN');
        $this->putJson('/api/i18n/languages/ar', ['is_default' => true])->assertStatus(422);            // not active yet
        $this->putJson('/api/i18n/languages/ar', ['active' => true])->assertOk();
        $this->putJson('/api/i18n/languages/ar', ['is_default' => true])->assertOk();
        $this->putJson('/api/i18n/languages/ar', ['active' => false])->assertStatus(422);              // it is the default
        $this->putJson('/api/i18n/languages/en-US', ['active' => false])->assertStatus(422);           // the fallback

        $this->postJson('/api/i18n/languages', ['code' => 'ceb-PH', 'name' => 'Cebuano (Philippines)', 'native' => 'Cebuano', 'rtl' => false, 'countries' => ['PH']])->assertOk();
        $this->putJson('/api/i18n/languages/ceb-PH', ['active' => true])->assertOk();
        $this->putJson('/api/i18n/countries/PH', ['active' => false])->assertOk();

        $this->as(null);
        $config = $this->getJson('/api/i18n/config')->assertOk()->assertJsonPath('default', 'ar');
        $this->assertContains('ceb-PH', array_column($config->json('languages'), 'code'));
    }

    public function test_translations_are_stored_and_removed(): void
    {
        $this->as('DEF-MASTERADMIN');
        $this->putJson('/api/i18n/translations/fil-PH', ['values' => ['nav_home' => 'Simula']])->assertOk();
        $this->putJson('/api/i18n/translations/fil-PH', ['values' => ['bad.key' => 'x']])->assertStatus(422);
        $this->as(null);
        $this->getJson('/api/i18n/translations/fil-PH')->assertOk()->assertJsonPath('data.nav_home', 'Simula');

        $this->as('DEF-MASTERADMIN');
        $this->putJson('/api/i18n/translations/fil-PH', ['values' => ['nav_home' => null]])->assertOk();
        $this->assertSame([], AppSetting::read('i18n.text.fil-PH'));
    }

    public function test_the_master_admin_manages_one_users_language_without_touching_others(): void
    {
        $a = $this->as('DEF-CLIENT');
        $b = $this->as('DEF-USERS');
        $this->as('DEF-CLIENT');
        $this->getJson('/api/i18n/users')->assertStatus(403);

        $this->as('DEF-MASTERADMIN');
        $this->putJson("/api/i18n/users/{$a->id}", ['language' => 'zh-CN'])->assertOk();
        $this->putJson("/api/i18n/users/{$b->id}", ['language' => 'fil-PH'])->assertOk();
        $this->putJson("/api/i18n/users/{$b->id}", ['language' => 'de'])->assertStatus(422);        // not activated
        $list = $this->getJson('/api/i18n/users?search=' . urlencode($a->email))->assertOk()->json('data');
        $this->assertSame([[$a->id, 'zh-CN']], array_map(fn ($u) => [$u['id'], $u['language']], $list));

        $mine = function (User $u) {
            $this->app['auth']->forgetGuards();
            Sanctum::actingAs($u);

            return $this->getJson('/api/i18n/preference')->assertOk()->json('language');
        };
        $this->assertSame(['zh-CN', 'fil-PH'], [$mine($a), $mine($b)]);

        $this->as('DEF-MASTERADMIN');
        $this->putJson("/api/i18n/users/{$a->id}", ['language' => null])->assertOk();
        $this->assertSame([null, 'fil-PH'], [$mine($a), $mine($b)]);
    }

    public function test_country_is_a_separate_choice_from_language(): void
    {
        $this->as('DEF-MASTERADMIN');
        $this->putJson('/api/i18n/countries/AQ', ['active' => false])->assertOk();
        $this->as(null);
        $codes = array_column($this->getJson('/api/i18n/countries')->assertOk()->json('data'), 'code');
        $this->assertContains('PH', $codes);
        $this->assertNotContains('AQ', $codes);

        $this->as('DEF-USERS');
        $this->putJson('/api/i18n/preference', [])->assertStatus(422);
        $this->putJson('/api/i18n/preference', ['country' => 'ZZ'])->assertStatus(422);
        $this->putJson('/api/i18n/preference', ['language' => 'fil-PH'])->assertOk();
        $this->putJson('/api/i18n/preference', ['country' => 'JP'])->assertOk();
        $this->getJson('/api/i18n/preference')->assertOk()->assertJsonPath('language', 'fil-PH')->assertJsonPath('country', 'JP');
    }

    public function test_each_user_keeps_their_own_language(): void
    {
        $a = $this->as('DEF-CLIENT');
        $this->putJson('/api/i18n/preference', ['language' => 'de'])->assertStatus(422);               // not activated
        $this->putJson('/api/i18n/preference', ['language' => 'fil-PH'])->assertOk();
        $this->as('DEF-USERS');
        $this->getJson('/api/i18n/preference')->assertOk()->assertJsonPath('language', null);
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($a);
        $this->getJson('/api/i18n/preference')->assertOk()->assertJsonPath('language', 'fil-PH');
    }
}
