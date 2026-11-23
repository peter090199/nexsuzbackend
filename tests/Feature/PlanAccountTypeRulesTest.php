<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Who may hold a subscription plan (and a CV):
 *  - DEF-USERS (job seekers) take job seeker plans and may create a CV
 *  - DEF-CLIENT (recruiters) take recruiter plans, no CV
 *  - DEF-ADMIN and DEF-MASTERADMIN can take no plan and have no CV
 */
class PlanAccountTypeRulesTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<string, User> */
    private array $u = [];
    private string $seekerPlan;
    private string $recruiterPlan;

    protected function setUp(): void
    {
        parent::setUp();
        static $next = 890000;
        foreach (['seeker' => 'DEF-USERS', 'recruiter' => 'DEF-CLIENT', 'admin' => 'DEF-ADMIN', 'master' => 'DEF-MASTERADMIN'] as $k => $role) {
            $code = $next += random_int(1, 40);
            $this->u[$k] = User::forceCreate([
                'fname' => $k, 'lname' => 'Tester', 'fullname' => "$k Tester", 'role_code' => $role,
                'email' => "$k$code@example.test", 'password' => bcrypt('secret-pass'),
                'status' => 'A', 'code' => $code, 'coverphoto' => '',
            ]);
        }

        // two plans of our own (one per audience) so the test does not depend on which real plans are enabled
        $this->as($this->u['master']);
        $mk = fn (string $tag, string $aud) => $this->postJson('/api/account-plan/save', [
            'plan_name' => "ZZ $tag", 'button_name' => 'Go', 'price' => 5, 'sort_number' => 95, 'tag' => "ZZ_$tag", 'audience' => $aud,
        ])->assertOk()->json('data.planId');
        $this->seekerPlan = $mk('SEEKER', 'DEF-USERS');
        $this->recruiterPlan = $mk('RECRUITER', 'DEF-CLIENT');
    }

    /** subscription tables are MyISAM here (no rollback), so remove what the tests wrote by hand. */
    protected function tearDown(): void
    {
        $codes = collect($this->u)->pluck('code')->map(fn ($c) => (string) $c);
        DB::table('usersubscription_details')->whereIn('code', $codes)->delete();
        DB::table('usersubscription')->whereIn('code', $codes)->delete();
        DB::table('userplandetails')->whereIn('planId', DB::table('userplanheader')->where('tag', 'like', 'ZZ\_%')->select('planId'))->delete();
        DB::table('userplanheader')->where('tag', 'like', 'ZZ\_%')->delete();
        \App\Services\PlanService::bust();
        parent::tearDown();
    }

    private function as(User $u): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($u);
    }

    private function upgrade(string $who, string $planId)
    {
        $this->as($this->u[$who]);
        return $this->postJson('/api/account-plan/upgrade', ['planId' => $planId, 'payment_method' => 'gcash']);
    }

    public function test_job_seekers_and_recruiters_take_only_their_own_plans(): void
    {
        $this->upgrade('seeker', $this->seekerPlan)->assertOk();
        $this->upgrade('recruiter', $this->recruiterPlan)->assertOk();

        $this->upgrade('seeker', $this->recruiterPlan)->assertStatus(403)->assertJsonPath('message', 'This plan is for recruiter accounts only.');
        $this->upgrade('recruiter', $this->seekerPlan)->assertStatus(403)->assertJsonPath('message', 'This plan is for job seeker accounts only.');
    }

    public function test_admins_and_master_admins_cannot_take_any_plan(): void
    {
        foreach (['admin', 'master'] as $who) {
            foreach ([$this->seekerPlan, $this->recruiterPlan] as $plan) {
                $this->upgrade($who, $plan)->assertStatus(403)->assertJsonPath('message', 'Admin accounts do not use subscription plans.');
            }
            $this->assertSame(0, DB::table('usersubscription')->where('code', (string) $this->u[$who]->code)->count());
        }
    }

    public function test_free_plan_endpoint_refuses_admin_accounts(): void
    {
        foreach (['admin', 'master'] as $who) {
            $this->as($this->u[$who]);
            $this->postJson('/api/account-plan/free-plan')->assertStatus(403);
        }
    }

    public function test_master_admin_cannot_assign_a_plan_to_an_admin_or_the_wrong_audience(): void
    {
        $this->as($this->u['master']);
        $assign = fn (string $who, string $plan) => $this->postJson('/api/account-plan/assign', ['code' => $this->u[$who]->code, 'planId' => $plan]);

        $assign('admin', $this->seekerPlan)->assertStatus(409);
        $assign('master', $this->recruiterPlan)->assertStatus(409);
        $assign('seeker', $this->recruiterPlan)->assertStatus(409);
        $assign('recruiter', $this->seekerPlan)->assertStatus(409);
        $assign('seeker', $this->seekerPlan)->assertOk();
        $assign('recruiter', $this->recruiterPlan)->assertOk();

        // same rules through the Subscription Monitoring "change plan" action
        $this->postJson("/api/subscription-admin/subscribers/{$this->u['admin']->code}/change-plan", ['plan_id' => $this->seekerPlan])->assertStatus(409);
        $this->postJson("/api/subscription-admin/subscribers/{$this->u['seeker']->code}/change-plan", ['plan_id' => $this->recruiterPlan])->assertStatus(409);
    }

    public function test_admins_never_appear_in_subscription_monitoring(): void
    {
        $this->as($this->u['master']);
        $codes = collect($this->getJson('/api/subscription-admin/subscribers?per_page=100&search=Tester')->assertOk()->json('data'))->pluck('code');
        $this->assertTrue($codes->contains((string) $this->u['seeker']->code));
        $this->assertTrue($codes->contains((string) $this->u['recruiter']->code));
        $this->assertFalse($codes->contains((string) $this->u['admin']->code));
        $this->assertFalse($codes->contains((string) $this->u['master']->code));
    }

    public function test_my_features_applies_to_seekers_and_recruiters_but_not_admins(): void
    {
        $this->as($this->u['admin']);
        $this->getJson('/api/account-plan-details/my-plan-features')->assertOk()->assertJsonPath('plan_applies', false);
        $this->as($this->u['master']);
        $this->getJson('/api/account-plan-details/my-plan-features')->assertOk()->assertJsonPath('plan_applies', false);

        // recruiter with no subscription yet: not an error. Nothing subscribed (CurrentPlan null), and the free plan applies
        $this->as($this->u['recruiter']);
        $this->getJson('/api/account-plan-details/my-plan-features')->assertOk()
            ->assertJsonPath('plan_applies', true)->assertJsonPath('CurrentPlan', null)->assertJsonPath('status', 'none');

        $this->upgrade('recruiter', $this->recruiterPlan)->assertOk();
        $this->as($this->u['recruiter']);
        $this->getJson('/api/account-plan-details/my-plan-features')->assertOk()->assertJsonPath('CurrentPlan', 'ZZ RECRUITER');

        // a job seeker without a subscription is no longer told "subscribe first" (404): the free plan applies, so plan
        // enforcement never locks an older account out. Nothing is subscribed to (CurrentPlan stays null).
        $this->as($this->u['seeker']);
        $this->getJson('/api/account-plan-details/my-plan-features')->assertOk()
            ->assertJsonPath('CurrentPlan', null)->assertJsonPath('status', 'none');
    }

    public function test_only_job_seekers_can_write_a_cv(): void
    {
        foreach (['admin', 'master', 'recruiter'] as $who) {
            $this->as($this->u[$who]);
            $this->postJson('/api/saveLanguage', [])->assertStatus(403);
            $this->postJson('/api/saveEducation', [])->assertStatus(403);
        }
        // a job seeker gets past the role gate (the empty body then fails validation, not authorization)
        $this->as($this->u['seeker']);
        $this->assertNotSame(403, $this->postJson('/api/saveLanguage', [])->getStatusCode());
    }
}
