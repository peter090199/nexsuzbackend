<?php

namespace Tests\Feature;

use App\Models\AccountPlan\SubscriptionFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Master-admin Subscription Management API. Every test is rolled back. */
class SubscriptionAdminApiTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $member;
    private string $planA = 'PLN000001';
    private string $planB = 'PLN000002';

    protected function setUp(): void
    {
        parent::setUp();
        static $next = 880000;
        $mk = function (string $name, string $role) use (&$next) {
            $code = $next += random_int(1, 40);
            return User::forceCreate([
                'fname' => $name, 'lname' => 'Tester', 'fullname' => "$name Tester", 'role_code' => $role,
                'email' => strtolower($name) . $code . '@example.test', 'password' => bcrypt('secret-pass'),
                'status' => 'A', 'code' => $code, 'coverphoto' => '',
            ]);
        };
        $this->admin = $mk('Boss', 'DEF-MASTERADMIN');
        $this->member = $mk('Member', 'DEF-USERS');
    }

    /** userplandetails / usersubscription* are MyISAM here (no rollback), so remove what the tests wrote by hand. */
    protected function tearDown(): void
    {
        $code = (string) $this->member->code;
        DB::table("usersubscription_details")->where("code", $code)->delete();
        DB::table("usersubscription")->where("code", $code)->delete();
        DB::table("userplandetails")->where("feature_code", "like", "ZZ\_%")->delete();
        DB::table("userplandetails")->whereIn("planId", DB::table("userplanheader")->where("tag", "like", "ZZ\_%")->select("planId"))->delete();
        DB::table("userplanheader")->where("tag", "like", "ZZ\_%")->delete();
        \App\Services\PlanService::bust();
        parent::tearDown();
    }

    private function as(User $u): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($u);
    }

    public function test_only_master_admin_can_use_it(): void
    {
        $this->as($this->member);
        $this->getJson('/api/subscription-admin/features')->assertStatus(403);
        $this->getJson('/api/subscription-admin/reports')->assertStatus(403);
    }

    public function test_feature_catalog_create_validates_and_key_is_immutable(): void
    {
        $this->as($this->admin);
        $this->postJson('/api/subscription-admin/features', ['name' => 'x'])->assertStatus(422);
        $this->postJson('/api/subscription-admin/features', [
            'feature_code' => 'bad key', 'name' => 'x', 'category' => 'Jobs', 'value_type' => 'toggle',
        ])->assertStatus(422)->assertJsonValidationErrors('feature_code');

        $id = $this->postJson('/api/subscription-admin/features', [
            'feature_code' => 'zz_test_feature', 'name' => 'Test feature', 'category' => 'Jobs', 'value_type' => 'limit',
        ])->assertStatus(201)->assertJsonPath('data.key', 'ZZ_TEST_FEATURE')->json('data.id');

        // duplicate key is rejected
        $this->postJson('/api/subscription-admin/features', [
            'feature_code' => 'ZZ_TEST_FEATURE', 'name' => 'Dup', 'category' => 'Jobs', 'value_type' => 'toggle',
        ])->assertStatus(422);

        // key cannot be changed on update
        $this->putJson("/api/subscription-admin/features/$id", [
            'feature_code' => 'CHANGED', 'name' => 'Renamed', 'category' => 'Jobs', 'value_type' => 'limit', 'is_active' => true,
        ])->assertOk()->assertJsonPath('data.key', 'ZZ_TEST_FEATURE')->assertJsonPath('data.name', 'Renamed');

        $this->patchJson("/api/subscription-admin/features/$id/toggle")->assertOk()->assertJsonPath('data.active', false);
    }

    public function test_assignment_matrix_round_trip_writes_values_the_app_enforces(): void
    {
        $this->as($this->admin);
        $id = SubscriptionFeature::create([
            'feature_code' => 'ZZ_LIMITED', 'name' => 'Limited thing', 'category' => 'Jobs', 'value_type' => 'limit',
        ])->id;
        $toggle = SubscriptionFeature::create([
            'feature_code' => 'ZZ_TOGGLE', 'name' => 'Toggle thing', 'category' => 'Jobs', 'value_type' => 'toggle',
        ])->id;

        $this->putJson("/api/subscription-admin/assignments/{$this->planA}", ['items' => [
            ['feature_id' => $id, 'enabled' => true, 'limit' => 25, 'unlimited' => false],
            ['feature_id' => $toggle, 'enabled' => true, 'limit' => 99, 'unlimited' => true], // ignored for toggles
        ]])->assertOk();

        $this->assertSame('25', DB::table('userplandetails')->where('planId', $this->planA)->where('feature_code', 'ZZ_LIMITED')->value('feature_value'));
        $this->assertSame('YES', DB::table('userplandetails')->where('planId', $this->planA)->where('feature_code', 'ZZ_TOGGLE')->value('feature_value'));

        $this->putJson("/api/subscription-admin/assignments/{$this->planA}", ['items' => [
            ['feature_id' => $id, 'enabled' => true, 'limit' => null, 'unlimited' => true],
            ['feature_id' => $toggle, 'enabled' => false],
        ]])->assertOk();

        $this->assertSame('UNLIMITED', DB::table('userplandetails')->where('planId', $this->planA)->where('feature_code', 'ZZ_LIMITED')->value('feature_value'));
        $row = DB::table('userplandetails')->where('planId', $this->planA)->where('feature_code', 'ZZ_TOGGLE')->first();
        $this->assertSame('inactive', $row->recordStatus);

        $m = collect($this->getJson("/api/subscription-admin/assignments/{$this->planA}")->assertOk()->json('data'))->keyBy('feature_code');
        $this->assertTrue($m['ZZ_LIMITED']['unlimited']);
        $this->assertFalse($m['ZZ_TOGGLE']['enabled']);

        $this->putJson("/api/subscription-admin/assignments/{$this->planA}", ['items' => [['feature_id' => 99999999, 'enabled' => true]]])
            ->assertStatus(422);
        $this->getJson('/api/subscription-admin/assignments/NOPE')->assertStatus(404);
    }

    public function test_subscriber_lifecycle_change_extend_suspend_reactivate(): void
    {
        $this->as($this->admin);
        $code = $this->member->code;

        // use a plan of our own so the test does not depend on which real plans are enabled right now
        $this->planB = $this->postJson('/api/account-plan/save', [
            'plan_name' => 'ZZ Lifecycle', 'button_name' => 'Go', 'price' => 10, 'sort_number' => 91, 'tag' => 'ZZ_LIFECYCLE',
        ])->assertOk()->json('data.planId');

        $this->postJson("/api/subscription-admin/subscribers/$code/change-plan", ['plan_id' => $this->planB])->assertOk();
        $row = collect($this->getJson('/api/subscription-admin/subscribers?search=Member')->assertOk()->json('data'))->firstWhere('code', (string) $code);
        $this->assertSame('active', $row['status']);
        $this->assertSame($this->planB, $row['plan_id']);

        // same plan again is a conflict
        $this->postJson("/api/subscription-admin/subscribers/$code/change-plan", ['plan_id' => $this->planB])->assertStatus(409);

        $before = $this->getJson("/api/subscription-admin/subscribers/$code")->json('data.end');
        $this->postJson("/api/subscription-admin/subscribers/$code/extend", ['days' => 30])->assertOk();
        $after = $this->getJson("/api/subscription-admin/subscribers/$code")->json('data.end');
        $this->assertGreaterThan(strtotime($before), strtotime($after));
        $this->postJson("/api/subscription-admin/subscribers/$code/extend", ['days' => 0])->assertStatus(422);

        $this->postJson("/api/subscription-admin/subscribers/$code/suspend", ['reason' => 'abuse'])->assertOk();
        $this->assertSame('suspended', $this->getJson("/api/subscription-admin/subscribers/$code")->json('data.status'));
        $this->postJson("/api/subscription-admin/subscribers/$code/suspend")->assertStatus(409);
        $this->postJson("/api/subscription-admin/subscribers/$code/extend", ['days' => 5])->assertStatus(409);

        $this->postJson("/api/subscription-admin/subscribers/$code/reactivate")->assertOk();
        $detail = $this->getJson("/api/subscription-admin/subscribers/$code")->json('data');
        $this->assertSame('active', $detail['status']);
        $this->assertNotEmpty($detail['history']);
        $this->postJson("/api/subscription-admin/subscribers/$code/reactivate")->assertStatus(409);
    }

    public function test_plans_carry_an_audience_and_public_list_can_filter_by_it(): void
    {
        $this->as($this->admin);
        $body = ["plan_name" => "ZZ Recruiter", "button_name" => "Hire", "price" => 1500, "sort_number" => 90,
            "tag" => "ZZ_RECRUITER", "tagmonthYear" => "Monthly"];

        $this->postJson("/api/account-plan/save", $body + ["audience" => "NOPE"])->assertStatus(422);
        $id = $this->postJson("/api/account-plan/save", $body + ["audience" => "DEF-CLIENT"])->assertOk()
            ->assertJsonPath("data.audience", "DEF-CLIENT")->json("data.planId");

        $names = fn (string $a) => collect($this->getJson("/api/getPlanpublic?audience=$a")->assertOk()->json("data"))->pluck("plan_name");
        $this->assertTrue($names("DEF-CLIENT")->contains("ZZ Recruiter"));
        $this->assertFalse($names("DEF-USERS")->contains("ZZ Recruiter"));
        // legacy rows (blank / junk role_code) are job seeker plans
        $this->assertTrue($names("DEF-USERS")->contains("Free"));
        $this->assertTrue(collect($this->getJson("/api/getPlanpublic")->json("data"))->pluck("plan_name")->contains("ZZ Recruiter"));

        // update keeps the audience unless a new one is sent
        $this->putJson("/api/account-plan/update/$id", array_merge($body, ["plan_name" => "ZZ Recruiter 2"]))->assertOk()
            ->assertJsonPath("data.audience", "DEF-CLIENT");
        $this->putJson("/api/account-plan/update/$id", array_merge($body, ["audience" => "DEF-USERS"]))->assertOk()
            ->assertJsonPath("data.audience", "DEF-USERS");
    }

    public function test_subscribers_list_filters_and_reports_shape(): void
    {
        $this->as($this->admin);
        $this->getJson('/api/subscription-admin/subscribers?status=nonsense')->assertStatus(422);
        $this->getJson('/api/subscription-admin/subscribers?per_page=5&status=none')->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['total', 'per_page'], 'counts' => ['all', 'active', 'expired', 'suspended', 'expiring_soon']]);

        $this->getJson('/api/subscription-admin/reports')->assertOk()->assertJsonStructure(['data' => [
            'totals' => ['subscribers', 'paid', 'free', 'monthly_revenue'], 'status', 'distribution', 'revenue', 'growth', 'expiring',
        ]]);
    }
}
