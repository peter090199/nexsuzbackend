<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Subscription Plans > Add plan: the badge (tag) must be unique within an audience, not across all plans,
 * so a recruiter plan can use a badge a job seeker plan already has (e.g. STARTER, PREMIUM).
 */
class PlanBadgePerAudienceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $code = random_int(980000, 989000);
        $master = User::forceCreate([
            'fname' => 'Master', 'lname' => 'Tester', 'fullname' => 'Master Tester', 'role_code' => 'DEF-MASTERADMIN',
            'email' => "master{$code}@example.test", 'password' => bcrypt('secret-pass'),
            'status' => 'A', 'code' => $code, 'coverphoto' => '',
        ]);
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($master);
    }

    /** the plan tables are MyISAM here (no rollback), so remove what the tests wrote */
    protected function tearDown(): void
    {
        DB::table('userplanheader')->where('tag', 'like', 'ZZ\_%')->delete();
        \App\Services\PlanService::bust();
        parent::tearDown();
    }

    private function save(string $tag, string $audience, array $extra = [])
    {
        return $this->postJson('/api/account-plan/save', $extra + [
            'plan_name' => "ZZ $tag $audience", 'button_name' => 'Go', 'price' => 5, 'sort_number' => 95, 'tag' => $tag, 'audience' => $audience,
        ]);
    }

    public function test_a_recruiter_plan_can_reuse_a_job_seeker_badge(): void
    {
        $this->save('ZZ_SAME', 'DEF-USERS')->assertOk();

        $r = $this->save('ZZ_SAME', 'DEF-CLIENT')->assertOk();

        $this->assertSame('DEF-CLIENT', $r->json('data.audience'));
    }

    public function test_the_same_badge_twice_in_one_audience_is_still_refused(): void
    {
        $this->save('ZZ_DUP', 'DEF-CLIENT')->assertOk();

        $this->save('ZZ_DUP', 'DEF-CLIENT')->assertStatus(409)->assertJsonPath('exists', true);
    }

    public function test_a_job_seeker_plan_cannot_reuse_a_job_seeker_badge(): void
    {
        $this->save('ZZ_DUP2', 'DEF-USERS')->assertOk();

        $this->save('ZZ_DUP2', 'DEF-USERS')->assertStatus(409);
    }

    public function test_editing_a_recruiter_plan_to_a_badge_used_only_by_job_seekers_is_allowed(): void
    {
        $this->save('ZZ_SEEK', 'DEF-USERS')->assertOk();
        $id = $this->save('ZZ_REC', 'DEF-CLIENT')->assertOk()->json('data.planId');

        $this->putJson("/api/account-plan/update/{$id}", [
            'plan_name' => 'ZZ rec', 'button_name' => 'Go', 'price' => 5, 'tag' => 'ZZ_SEEK', 'audience' => 'DEF-CLIENT',
        ])->assertOk();
    }

    public function test_editing_a_plan_to_a_badge_of_another_plan_in_its_audience_is_refused(): void
    {
        $this->save('ZZ_A', 'DEF-CLIENT')->assertOk();
        $id = $this->save('ZZ_B', 'DEF-CLIENT')->assertOk()->json('data.planId');

        $this->putJson("/api/account-plan/update/{$id}", [
            'plan_name' => 'ZZ b', 'button_name' => 'Go', 'price' => 5, 'tag' => 'ZZ_A', 'audience' => 'DEF-CLIENT',
        ])->assertStatus(409);
    }

    public function test_moving_a_plan_to_recruiters_is_refused_when_that_badge_exists_there(): void
    {
        $this->save('ZZ_MOVE', 'DEF-CLIENT')->assertOk();
        $id = $this->save('ZZ_MOVE', 'DEF-USERS')->assertOk()->json('data.planId');

        $this->putJson("/api/account-plan/update/{$id}", [
            'plan_name' => 'ZZ m', 'button_name' => 'Go', 'price' => 5, 'tag' => 'ZZ_MOVE', 'audience' => 'DEF-CLIENT',
        ])->assertStatus(409);
    }
}
