<?php

namespace Tests\Feature;

use App\Models\AccountPlan\SubscriptionFeature;
use App\Models\AccountPlan\UserPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * GET /api/plans/overview: the plans a job seeker / recruiter sees under Settings → Subscription Plan, built from what the
 * master admin manages. userplanheader / userplandetails / usersubscription are MyISAM here (no rollback), so every row
 * these tests write is tagged ZZ and removed by hand.
 */
class PlanOverviewTest extends TestCase
{
    use DatabaseTransactions;

    private User $seeker;
    private User $recruiter;
    private User $admin;
    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        static $next = 893000;
        foreach (['seeker' => 'DEF-USERS', 'recruiter' => 'DEF-CLIENT', 'admin' => 'DEF-ADMIN'] as $k => $role) {
            $code = $next += random_int(1, 40);
            $this->$k = User::forceCreate(['fname' => $k, 'lname' => 'T', 'fullname' => "$k T", 'role_code' => $role, 'email' => "$k$code@example.test",
                'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']);
        }

        SubscriptionFeature::create(['feature_code' => 'ZZ_BASIC', 'name' => 'ZZ Basic thing', 'category' => 'Jobs', 'value_type' => 'toggle']);
        SubscriptionFeature::create(['feature_code' => 'ZZ_LIMIT', 'name' => 'ZZ Limited thing', 'category' => 'Jobs', 'value_type' => 'limit']);
        SubscriptionFeature::create(['feature_code' => 'ZZ_LATER', 'name' => 'ZZ Later thing', 'category' => 'Jobs', 'value_type' => 'toggle']);
        SubscriptionFeature::create(['feature_code' => 'ZZ_SEATS', 'name' => 'ZZ 5 Seats', 'category' => 'Jobs', 'value_type' => 'limit']);
        SubscriptionFeature::create(['feature_code' => 'ZZ_OFF', 'name' => 'ZZ Switched off', 'category' => 'Jobs', 'value_type' => 'toggle', 'is_active' => false]);

        $this->ids['p1'] = $this->plan('ZZ_P1', 'ZZ One', 901, 'DEF-USERS', 0);
        $this->ids['p2'] = $this->plan('ZZ_P2', 'ZZ Two', 902, 'DEF-USERS', 299);
        $this->ids['p3'] = $this->plan('ZZ_P3', 'ZZ Three', 903, 'DEF-USERS', 599, 'MOST POPULAR');
        $this->ids['off'] = $this->plan('ZZ_OFF', 'ZZ Hidden', 904, 'DEF-USERS', 1, 'X', 'inactive');
        $this->ids['r1'] = $this->plan('ZZ_R1', 'ZZ Recruiter', 905, 'DEF-CLIENT', 1500);

        $this->row('p1', 'ZZ_BASIC', 'ZZ Basic thing', 'YES');
        $this->row('p1', 'ZZ_LIMIT', 'ZZ Limited thing', '5');
        $this->row('p1', 'ZZ_LATER', 'ZZ Later thing', 'NO');
        $this->row('p1', 'ZZ_SEATS', 'ZZ 5 Seats', '5');
        $this->row('p1', null, 'ZZ Legacy perk', null);                      // older, name-only row
        $this->row('p2', 'ZZ_LIMIT', 'ZZ Limited thing', 'UNLIMITED');
        $this->row('p2', 'ZZ_LATER', 'ZZ Later thing', 'YES');
        $this->row('p2', 'EVERYTHING_ZZ', 'Everything in ZZ One', 'YES');   // only says "inherits"
        $this->row('p3', 'ZZ_BASIC', 'ZZ Basic thing', 'NO');               // a higher plan can switch a feature off
        $this->row('p3', 'ZZ_OFF', 'ZZ Switched off', 'YES');               // catalog says off: nobody sees it
        $this->row('off', 'ZZ_BASIC', 'ZZ Basic thing', 'YES');
        $this->row('r1', 'ZZ_BASIC', 'ZZ Basic thing', 'YES');
        $this->row('r1', 'ZZ_LIMIT', 'ZZ Limited thing', '25');
    }

    protected function tearDown(): void
    {
        // keyed on the plan id, not the tag: one test plan deliberately carries the real "MOST POPULAR" tag
        DB::table('userplandetails')->where('planId', 'like', 'ZZPLN%')->delete();
        DB::table('userplanheader')->where('planId', 'like', 'ZZPLN%')->delete();
        DB::table('usersubscription')->whereIn('code', [(string) $this->seeker->code, (string) $this->recruiter->code])->delete();
        \App\Services\PlanService::bust();
        parent::tearDown();
    }

    private function plan(string $tag, string $name, int $level, string $audience, float $price, string $badge = null, string $status = 'active'): string
    {
        $p = UserPlan::create(['planId' => 'ZZPLN' . $level, 'plan_name' => $name, 'button_name' => "Go $name", 'sort_number' => $level, 'price' => $price,
            'tagmonthYear' => 'Monthly', 'tag' => $badge ? "ZZ_$badge" : $tag, 'description' => "$name plan", 'recordStatus' => $status,
            'plan_level' => $level, 'role_code' => $audience]);
        if ($badge === 'MOST POPULAR') { $p->update(['tag' => 'MOST POPULAR']); DB::table('userplanheader')->where('planId', $p->planId)->update(['tag' => 'MOST POPULAR']); }
        return $p->planId;
    }

    private function row(string $plan, ?string $code, string $name, ?string $value): void
    {
        DB::table('userplandetails')->insert(['planId' => $this->ids[$plan], 'plan_name' => 'x', 'fid' => 'ZZ' . random_int(100000, 999999), 'feature_name' => $name,
            'feature_code' => $code, 'feature_value' => $value, 'recordStatus' => 'active', 'button_name' => '']);
    }

    private function overview(User $as, string $query = ''): array
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($as);
        return $this->getJson('/api/plans/overview' . $query)->assertOk()->assertJsonPath('success', true)->json('data');
    }

    private function zzPlans(array $data): array
    {
        return collect($data['plans'])->filter(fn ($p) => str_starts_with($p['planId'], 'ZZPLN'))->keyBy('planId')->all();
    }

    private function feature(array $plan, string $nameOrKey): ?array
    {
        return collect($plan['features'])->first(fn ($f) => $f['key'] === $nameOrKey || $f['name'] === $nameOrKey);
    }

    // ----------------------------------------------------------------------------------------------------------------

    public function test_it_needs_a_signed_in_user(): void
    {
        $this->getJson('/api/plans/overview')->assertStatus(401);
    }

    public function test_a_job_seeker_gets_only_active_job_seeker_plans_and_a_recruiter_only_recruiter_plans(): void
    {
        $seeker = $this->overview($this->seeker);
        $this->assertSame('DEF-USERS', $seeker['audience']);
        $names = array_column($this->zzPlans($seeker), 'plan_name');
        $this->assertSame(['ZZ One', 'ZZ Two', 'ZZ Three'], array_values($names));   // not the recruiter plan, not the inactive one

        $rec = $this->overview($this->recruiter);
        $this->assertSame('DEF-CLIENT', $rec['audience']);
        $this->assertSame(['ZZ Recruiter'], array_column($rec['plans'], 'plan_name'));

        // asking for the other audience does not work for the people who have a plan
        $this->assertSame('DEF-USERS', $this->overview($this->seeker, '?audience=DEF-CLIENT')['audience']);
        $this->assertSame('DEF-CLIENT', $this->overview($this->recruiter, '?audience=DEF-USERS')['audience']);
    }

    public function test_admins_can_ask_for_either_audience(): void
    {
        $this->assertSame('DEF-USERS', $this->overview($this->admin)['audience']);
        $this->assertSame(['ZZ Recruiter'], array_column($this->overview($this->admin, '?audience=DEF-CLIENT')['plans'], 'plan_name'));
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($this->admin);
        $this->getJson('/api/plans/overview?audience=nonsense')->assertStatus(422);
    }

    public function test_plan_details_come_through(): void
    {
        $p = $this->zzPlans($this->overview($this->seeker));
        $this->assertEquals(299, $p['ZZPLN902']['price']);
        $this->assertSame('monthly', $p['ZZPLN902']['billing']);
        $this->assertSame('ZZ Two plan', $p['ZZPLN902']['description']);
        $this->assertFalse($p['ZZPLN902']['is_popular']);
        $this->assertTrue($p['ZZPLN903']['is_popular']);
    }

    public function test_features_follow_the_catalog_limits_and_inheritance(): void
    {
        $p = $this->zzPlans($this->overview($this->seeker));

        // plan one
        $this->assertTrue($this->feature($p['ZZPLN901'], 'ZZ_BASIC')['included']);
        $lim = $this->feature($p['ZZPLN901'], 'ZZ_LIMIT');
        $this->assertTrue($lim['included']);
        $this->assertSame(5, (int) $lim['limit']);
        $this->assertSame('5', $lim['detail']);
        $this->assertFalse($this->feature($p['ZZPLN901'], 'ZZ_LATER')['included'], 'an explicit NO is not included');
        $this->assertSame('', $this->feature($p['ZZPLN901'], 'ZZ_SEATS')['detail'], 'the name already says 5');
        $this->assertTrue($this->feature($p['ZZPLN901'], 'ZZ Legacy perk')['included'], 'older name-only rows still show');

        // plan two inherits plan one and raises a limit
        $this->assertTrue($this->feature($p['ZZPLN902'], 'ZZ_BASIC')['included'], 'inherited from the plan below');
        $this->assertTrue($this->feature($p['ZZPLN902'], 'ZZ Legacy perk')['included']);
        $l2 = $this->feature($p['ZZPLN902'], 'ZZ_LIMIT');
        $this->assertTrue($l2['unlimited']);
        $this->assertSame('Unlimited', $l2['detail']);
        $this->assertTrue($this->feature($p['ZZPLN902'], 'ZZ_LATER')['included']);

        // plan three switches ZZ_BASIC off again, but keeps the rest
        $this->assertFalse($this->feature($p['ZZPLN903'], 'ZZ_BASIC')['included']);
        $this->assertTrue($this->feature($p['ZZPLN903'], 'ZZ_LATER')['included']);

        // nothing shows the "Everything in ..." marker rows, and a feature switched off in the catalog is gone everywhere
        foreach ($p as $plan) {
            $this->assertNull(collect($plan['features'])->first(fn ($f) => str_contains(strtolower($f['name']), 'everything in zz')));
            $this->assertNull($this->feature($plan, 'ZZ_OFF'));
        }
        // a lower plan lists what it lacks instead of hiding it
        $this->assertNotNull($this->feature($p['ZZPLN901'], 'ZZ_LATER'));
        $this->assertSame($plan_included = count(array_filter($p['ZZPLN901']['features'], fn ($f) => $f['included'])), $p['ZZPLN901']['included_count']);
    }

    public function test_recruiter_plan_features_stay_in_the_recruiter_audience(): void
    {
        $r = $this->overview($this->recruiter)['plans'][0];
        $this->assertSame(25, (int) $this->feature($r, 'ZZ_LIMIT')['limit']);
        $this->assertTrue($this->feature($r, 'ZZ_BASIC')['included']);
        $this->assertNull($this->feature($r, 'ZZ Legacy perk'), 'job seeker rows never leak into a recruiter plan');
        $this->assertNull($this->feature($r, 'ZZ_LATER'));
    }

    public function test_it_marks_the_current_plan_and_locks_downgrades(): void
    {
        $none = $this->zzPlans($this->overview($this->seeker));
        // first visit gives the free starter plan automatically
        $starter = \App\Services\PlanService::starterPlan('DEF-USERS')->planId;
        $this->assertTrue(collect($none)->merge(collect($this->overview($this->seeker)['plans'])->keyBy('planId'))->get($starter)['is_current'] ?? false);
        $this->assertFalse($none['ZZPLN902']['is_current']);
        DB::table('usersubscription')->where('code', (string) $this->seeker->code)->delete();
        \App\Services\PlanService::bust();

        DB::table('usersubscription')->insert(['transNo' => 'ZZ' . $this->seeker->code, 'planId' => 'ZZPLN902', 'plan_name' => 'ZZ Two', 'amount' => 0,
            'code' => (string) $this->seeker->code, 'is_active' => 'active', 'start_date' => now(), 'end_date' => now()->addMonth()]);
        $p = $this->zzPlans($this->overview($this->seeker));
        $this->assertTrue($p['ZZPLN902']['is_current']);
        $this->assertNotNull($p['ZZPLN902']['current_end']);
        $this->assertFalse($p['ZZPLN902']['disabled']);
        $this->assertTrue($p['ZZPLN901']['disabled'], 'a lower plan cannot be picked from here');
        $this->assertFalse($p['ZZPLN903']['disabled'], 'upgrading stays open');
        $this->assertFalse($p['ZZPLN901']['is_current']);
    }

    public function test_the_free_starter_plan_is_activated_automatically(): void
    {
        $this->assertFalse(DB::table('usersubscription')->where('code', (string) $this->seeker->code)->exists());
        $data = $this->overview($this->seeker);                       // first visit to Subscription Plan
        $mine = DB::table('usersubscription')->where('code', (string) $this->seeker->code)->where('is_active', 'active')->get();
        $this->assertCount(1, $mine);
        $starter = \App\Services\PlanService::starterPlan('DEF-USERS')->planId;
        $this->assertSame($starter, $mine[0]->planId, 'the STARTER job seeker plan, never a recruiter or inactive one');
        $this->assertTrue(collect($data['plans'])->firstWhere('planId', $starter)['is_current']);

        $this->overview($this->seeker);                               // second visit does not add another
        $this->assertSame(1, DB::table('usersubscription')->where('code', (string) $this->seeker->code)->count());

        // a recruiter gets a free recruiter plan once one is published, an admin gets nothing
        $this->overview($this->recruiter);
        $this->assertNull(DB::table('usersubscription')->where('code', (string) $this->recruiter->code)->value('planId'), 'a paid recruiter plan is never handed out as the free one');
        DB::table('userplanheader')->where('planId', 'ZZPLN905')->update(['price' => 0]);
        $this->overview($this->recruiter);
        $this->assertSame('ZZPLN905', DB::table('usersubscription')->where('code', (string) $this->recruiter->code)->value('planId'));
        $this->overview($this->admin);
        $this->assertFalse(DB::table('usersubscription')->where('code', (string) $this->admin->code)->exists());
    }

    public function test_an_existing_paid_plan_is_never_replaced_by_the_starter(): void
    {
        DB::table('usersubscription')->insert(['transNo' => 'ZZ' . $this->seeker->code, 'planId' => 'ZZPLN902', 'plan_name' => 'ZZ Two', 'amount' => 0,
            'code' => (string) $this->seeker->code, 'is_active' => 'active', 'start_date' => now(), 'end_date' => now()->addMonth()]);
        $this->overview($this->seeker);
        $this->assertSame(['ZZPLN902'], DB::table('usersubscription')->where('code', (string) $this->seeker->code)->pluck('planId')->all());
    }
}
