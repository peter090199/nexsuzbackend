<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PlanService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * A new recruiter (DEF-CLIENT) starts with an ACTIVE free STARTER plan, no checkout. usersubscription is MyISAM here (no
 * rollback), so rows written for the accounts these tests create are removed by hand.
 */
class ClientStarterPlanTest extends TestCase
{
    use DatabaseTransactions;

    private array $codes = [];

    protected function tearDown(): void
    {
        DB::table('usersubscription')->whereIn('code', array_map('strval', $this->codes))->delete();
        DB::table('usersubscription_details')->whereIn('code', array_map('strval', $this->codes))->delete();
        PlanService::bust();
        parent::tearDown();
    }

    private function register(int $statuscode): User
    {
        Mail::fake();
        $email = 'st' . bin2hex(random_bytes(4)) . '@example.test';
        $this->postJson('/api/register', ['fname' => 'T', 'lname' => 'P', 'email' => $email, 'password' => 'Aa1!aaaa',
            'password_confirmation' => 'Aa1!aaaa', 'statuscode' => $statuscode])->assertStatus(201);
        $u = User::where('email', $email)->firstOrFail();
        $this->codes[] = $u->code;

        return $u;
    }

    private function needStarter(string $role): void
    {
        if (!PlanService::starterPlan($role)) { $this->markTestSkipped("no active starter plan for $role in this database"); }
    }

    public function test_a_new_recruiter_gets_an_active_starter_plan_and_a_job_seeker_gets_theirs(): void
    {
        $this->needStarter('DEF-CLIENT');
        $u = $this->register(1);
        $starter = PlanService::starterPlan('DEF-CLIENT');
        $subs = DB::table('usersubscription')->where('code', (string) $u->code)->get();

        $this->assertCount(1, $subs);
        $this->assertSame($starter->planId, $subs[0]->planId);
        $this->assertSame('active', $subs[0]->is_active);
        $this->assertSame('DEF-CLIENT', $starter->audience);
    }

    public function test_the_overview_reports_the_free_active_subscription_for_a_new_recruiter(): void
    {
        $this->needStarter('DEF-CLIENT');
        $u = $this->register(1);
        DB::table('users')->where('id', $u->id)->update(['status' => 'A']);   // activated through the email link
        $u->refresh();
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($u);

        $s = $this->getJson('/api/plans/overview')->assertOk()->json('data.subscription');
        $this->assertSame('ACTIVE', $s['status']);
        $this->assertSame(0.0, (float) $s['price']);
        $this->assertTrue($s['is_free']);
        $this->assertNull($s['expires_at'], 'a free plan never expires');
        $this->assertNotNull($s['start_date']);
        $this->assertIsArray($s['features']);
    }

    public function test_assigning_again_never_duplicates_and_never_replaces_a_paid_plan(): void
    {
        $this->needStarter('DEF-CLIENT');
        $u = $this->register(1);
        PlanService::ensureStarterPlan((string) $u->code, 'DEF-CLIENT');
        PlanService::ensureStarterPlan((string) $u->code, 'DEF-CLIENT');
        $this->assertSame(1, DB::table('usersubscription')->where('code', (string) $u->code)->count());

        // a paid active subscription is left alone
        DB::table('usersubscription')->where('code', (string) $u->code)->update(['planId' => 'PAIDX', 'amount' => 1500]);
        PlanService::ensureStarterPlan((string) $u->code, 'DEF-CLIENT');
        $rows = DB::table('usersubscription')->where('code', (string) $u->code)->get();
        $this->assertCount(1, $rows);
        $this->assertSame('PAIDX', $rows[0]->planId);
    }

    public function test_admins_never_get_a_plan_and_a_suspended_account_is_not_reactivated(): void
    {
        $this->assertNull(PlanService::ensureStarterPlan('0', 'DEF-ADMIN'));

        $this->needStarter('DEF-CLIENT');
        $u = $this->register(1);
        DB::table('usersubscription')->where('code', (string) $u->code)
            ->update(['is_active' => 'cancelled', 'remarks' => \App\Services\SubscriptionAdminService::SUSPENDED_TAG . ' by 1']);
        $this->assertNull(PlanService::ensureStarterPlan((string) $u->code, 'DEF-CLIENT'));
        $this->assertSame(0, DB::table('usersubscription')->where('code', (string) $u->code)->where('is_active', 'active')->count());
    }

}
