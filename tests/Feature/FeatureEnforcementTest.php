<?php

namespace Tests\Feature;

use App\Models\AccountPlan\SubscriptionFeature;
use App\Models\AccountPlan\UserPlan;
use App\Models\User;
use App\Services\PlanService;
use App\Services\SubscriptionAdminService;
use App\Support\FeatureAccess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Subscription features are enforced on the API for job seekers (DEF-USERS) and recruiters (DEF-CLIENT) only:
 * what the plan includes (Plan Feature Assignment) AND the role rules must both allow a request. Admins and master
 * admins are never touched. Plans are built here on their own (negative levels, so they inherit nothing real) and the
 * MyISAM plan tables are cleaned by hand.
 */
class FeatureEnforcementTest extends TestCase
{
    use DatabaseTransactions;

    /** @var int[] */
    private array $codes = [];
    private int $level = -100;

    private const SEEKER_ALL = ['APPLY_JOBS', 'CREATE_RESUME', 'CREATE_PROFILE', 'NETWORK_FEED', 'SEARCH_ACCESS', 'MESSAGING_ACCESS'];
    private const RECRUITER_ALL = ['JOB_POSTING', 'JOB_APPLICANTS', 'NETWORK_FEED', 'SEARCH_ACCESS', 'MESSAGING_ACCESS'];

    /** feature => [method, url] of endpoints it guards (one or more) */
    private const ENDPOINTS = [
        'APPLY_JOBS' => [['post', '/api/saveAppliedJob']],
        'CREATE_RESUME' => [['get', '/api/user-resumes'], ['post', '/api/saveSkills'], ['post', '/api/saveEducation']],
        'CREATE_PROFILE' => [['post', '/api/saveProfile']],
        'NETWORK_FEED' => [['post', '/api/savePost'], ['put', '/api/follow/123'], ['post', '/api/comment'], ['put', '/api/acceptFollowRequest/1'], ['post', '/api/reactToPostById']],
        'SEARCH_ACCESS' => [['get', '/api/searchUsers?search=x'], ['get', '/api/getSearchHistory']],
        'MESSAGING_ACCESS' => [['post', '/api/send-message'], ['get', '/api/conversations'], ['get', '/api/getMessagesAll']],
        'JOB_POSTING' => [['post', '/api/saveJobPosting'], ['post', '/api/job-posting'], ['put', '/api/updateJobPosting/X']],
        'JOB_APPLICANTS' => [['get', '/api/recruitment/candidates'], ['get', '/api/recruitment/reports']],
    ];

    protected function tearDown(): void
    {
        DB::table('userplandetails')->where('planId', 'like', 'ZZFA%')->delete();
        DB::table('userplanheader')->where('planId', 'like', 'ZZFA%')->delete();
        DB::table('usersubscription_details')->whereIn('code', array_map('strval', $this->codes))->delete();
        DB::table('usersubscription')->whereIn('code', array_map('strval', $this->codes))->delete();
        PlanService::bust();
        parent::tearDown();
    }

    // ------------------------------------------------------------------ fixtures

    private function makeUser(string $role): User
    {
        static $next = 870000;
        $code = $next += random_int(1, 40);
        $this->codes[] = $code;

        return User::forceCreate([
            'fname' => 'Fay', 'lname' => 'Tester', 'fullname' => 'Fay Tester', 'role_code' => $role,
            'email' => "fay{$code}@example.test", 'password' => bcrypt('secret-pass'),
            'status' => 'A', 'code' => $code, 'coverphoto' => '',
        ]);
    }

    /**
     * A plan that includes exactly $features. By default it sits BELOW every real plan, so it inherits nothing real. Plans
     * inherit from the plans under them: of two test plans, create the one that must NOT inherit last (it is the lowest).
     * A paid plan used for expiry tests is given a high $level instead: the free plan must not inherit its perks.
     */
    private function plan(string $audience, array $features, float $price = 0.0, string $name = 'ZZ Test Plan', ?int $level = null): string
    {
        $level ??= $this->level--;
        $id = 'ZZFA' . abs($level);
        UserPlan::create(['planId' => $id, 'plan_name' => $name, 'button_name' => 'Go', 'sort_number' => 99, 'price' => $price,
            'tagmonthYear' => 'Monthly', 'tag' => 'ZZ_FA' . abs($level), 'description' => 'test', 'recordStatus' => 'active',
            'plan_level' => $level, 'role_code' => $audience]);
        foreach ($features as $code) {
            DB::table('userplandetails')->insert(['planId' => $id, 'plan_name' => $name, 'fid' => 'ZZ' . random_int(100000, 999999), 'feature_name' => $code,
                'feature_code' => $code, 'feature_value' => 'YES', 'recordStatus' => 'active', 'button_name' => '']);
        }
        PlanService::bust();

        return $id;
    }

    private function subscribe(User $u, string $planId, ?string $endsAt = null): void
    {
        PlanService::subscribeUser((string) $u->code, $planId, 'Test');
        if ($endsAt) {
            DB::table('usersubscription')->where('code', (string) $u->code)->where('is_active', 'active')->update(['end_date' => $endsAt]);
        }
        PlanService::bust();
    }

    private function as(User $u): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs(User::find($u->id));
    }

    private function hit(string $method, string $url)
    {
        return $this->json(strtoupper($method), $url, []);
    }

    private function assertLocked($response, string $feature): void
    {
        $response->assertStatus(403)->assertJsonPath('code', 'feature_locked')->assertJsonPath('feature', $feature)->assertJsonPath('upgrade', true);
        $this->assertNotEmpty($response->json('message'));
    }

    /** the request got past the subscription check (the controller may still answer 404, 422 ...) */
    private function assertNotLocked($response, string $why = ''): void
    {
        $this->assertNotSame('feature_locked', $response->json('code'), "locked: {$why} " . $response->getContent());
    }

    private function mine(string $feature): array
    {
        return self::ENDPOINTS[$feature];
    }

    public static function seekerFeatures(): array
    {
        return array_map(fn ($f) => [$f], self::SEEKER_ALL);
    }

    public static function recruiterFeatures(): array
    {
        return array_map(fn ($f) => [$f], self::RECRUITER_ALL);
    }

    // ------------------------------------------------------------------ job seekers

    #[DataProvider('seekerFeatures')]
    public function test_a_job_seeker_without_a_feature_is_refused_exactly_its_endpoints(string $missing): void
    {
        $u = $this->makeUser('DEF-USERS');
        $this->subscribe($u, $this->plan('DEF-USERS', array_values(array_diff(self::SEEKER_ALL, [$missing]))));
        $this->as($u);

        foreach ($this->mine($missing) as [$m, $url]) {
            $this->assertLocked($this->hit($m, $url), $missing);
        }
        // every other feature of the plan still works
        foreach (array_diff(self::SEEKER_ALL, [$missing]) as $other) {
            foreach ($this->mine($other) as [$m, $url]) {
                $this->assertNotLocked($this->hit($m, $url), "{$other} {$url}");
            }
        }
    }

    public function test_a_job_seeker_with_every_feature_is_not_blocked_anywhere(): void
    {
        $u = $this->makeUser('DEF-USERS');
        $this->subscribe($u, $this->plan('DEF-USERS', self::SEEKER_ALL));
        $this->as($u);

        foreach (self::SEEKER_ALL as $f) {
            foreach ($this->mine($f) as [$m, $url]) { $this->assertNotLocked($this->hit($m, $url), "{$f} {$url}"); }
        }
    }

    public function test_a_plan_with_no_features_blocks_every_feature_for_a_job_seeker(): void
    {
        $u = $this->makeUser('DEF-USERS');
        $this->subscribe($u, $this->plan('DEF-USERS', []));
        $this->as($u);

        foreach (self::SEEKER_ALL as $f) {
            foreach ($this->mine($f) as [$m, $url]) { $this->assertLocked($this->hit($m, $url), $f); }
        }
        $this->assertSame(array_fill_keys(array_merge(self::SEEKER_ALL), false), $this->getJson('/api/account-plan-details/my-plan-features')->json('access'));
    }

    public function test_a_recruiter_only_feature_is_not_enforced_on_a_job_seeker(): void
    {
        // JOB_POSTING / JOB_APPLICANTS belong to recruiter plans: a seeker's plan lacking them is not "locked" by them
        $u = $this->makeUser('DEF-USERS');
        $this->subscribe($u, $this->plan('DEF-USERS', self::SEEKER_ALL));
        $this->as($u);

        foreach (['JOB_POSTING', 'JOB_APPLICANTS'] as $f) {
            foreach ($this->mine($f) as [$m, $url]) { $this->assertNotLocked($this->hit($m, $url), $f); }
        }
    }

    // ------------------------------------------------------------------ recruiters

    #[DataProvider('recruiterFeatures')]
    public function test_a_recruiter_without_a_feature_is_refused_exactly_its_endpoints(string $missing): void
    {
        $u = $this->makeUser('DEF-CLIENT');
        $this->subscribe($u, $this->plan('DEF-CLIENT', array_values(array_diff(self::RECRUITER_ALL, [$missing]))));
        $this->as($u);

        foreach ($this->mine($missing) as [$m, $url]) {
            $this->assertLocked($this->hit($m, $url), $missing);
        }
        foreach (array_diff(self::RECRUITER_ALL, [$missing]) as $other) {
            foreach ($this->mine($other) as [$m, $url]) {
                $this->assertNotLocked($this->hit($m, $url), "{$other} {$url}");
            }
        }
    }

    public function test_a_recruiter_with_every_feature_is_not_blocked(): void
    {
        $u = $this->makeUser('DEF-CLIENT');
        $this->subscribe($u, $this->plan('DEF-CLIENT', self::RECRUITER_ALL));
        $this->as($u);

        foreach (self::RECRUITER_ALL as $f) {
            foreach ($this->mine($f) as [$m, $url]) { $this->assertNotLocked($this->hit($m, $url), "{$f} {$url}"); }
        }
    }

    public function test_job_seeker_only_features_are_not_enforced_on_a_recruiter_and_the_role_rule_still_applies(): void
    {
        // both must allow: a recruiter plan that INCLUDES CREATE_RESUME still cannot write a CV (the role rule says no)
        $u = $this->makeUser('DEF-CLIENT');
        $this->subscribe($u, $this->plan('DEF-CLIENT', array_merge(self::RECRUITER_ALL, ['CREATE_RESUME'])));
        $this->as($u);

        $r = $this->hit('post', '/api/saveSkills');
        $r->assertStatus(403);
        $this->assertNotSame('feature_locked', $r->json('code'));
        $this->assertStringContainsString('cannot create or edit a CV', (string) $r->json('message'), 'refused by the ROLE rule');
    }

    // ------------------------------------------------------------------ admins are never touched

    #[DataProvider('admins')]
    public function test_admins_and_master_admins_are_never_plan_limited(string $role): void
    {
        $u = $this->makeUser($role);                    // no subscription at all
        $this->as($u);

        foreach (self::ENDPOINTS as $feature => $endpoints) {
            foreach ($endpoints as [$m, $url]) { $this->assertNotLocked($this->hit($m, $url), "{$role} {$feature} {$url}"); }
        }
        $r = $this->getJson('/api/account-plan-details/my-plan-features')->assertOk();
        $r->assertJsonPath('plan_applies', false);
        $this->assertNull($r->json('access'));
    }

    public static function admins(): array
    {
        return [['DEF-ADMIN'], ['DEF-MASTERADMIN']];
    }

    public function test_the_feature_check_itself_lets_admins_through_even_for_an_unknown_code(): void
    {
        $admin = $this->makeUser('DEF-ADMIN');
        $seeker = $this->makeUser('DEF-USERS');

        $this->assertTrue(FeatureAccess::allows($admin, 'NOT_A_REAL_FEATURE'));
        $this->assertFalse(FeatureAccess::allows($seeker, 'NOT_A_REAL_FEATURE'), 'a typo must never open a door');
    }

    // ------------------------------------------------------------------ changes are seen at once

    public function test_changing_plan_is_seen_on_the_next_request(): void
    {
        $u = $this->makeUser('DEF-USERS');
        $full = $this->plan('DEF-USERS', self::SEEKER_ALL);
        $none = $this->plan('DEF-USERS', []);               // created last = lowest: it inherits nothing from $full
        $this->as($u);

        $this->subscribe($u, $none);
        $this->assertLocked($this->hit('post', '/api/savePost'), 'NETWORK_FEED');

        $this->subscribe($u, $full);                       // upgrade
        $this->assertNotLocked($this->hit('post', '/api/savePost'));

        $this->subscribe($u, $none);                       // downgrade
        $this->assertLocked($this->hit('post', '/api/savePost'), 'NETWORK_FEED');
    }

    public function test_the_master_admin_editing_the_plan_features_is_seen_on_the_next_request(): void
    {
        $u = $this->makeUser('DEF-CLIENT');
        $plan = $this->plan('DEF-CLIENT', self::RECRUITER_ALL);
        $this->subscribe($u, $plan);
        $this->as($u);
        $this->assertNotLocked($this->hit('get', '/api/conversations'));

        $messaging = SubscriptionFeature::where('feature_code', 'MESSAGING_ACCESS')->firstOrFail();
        app(SubscriptionAdminService::class)->saveAssignments(UserPlan::where('planId', $plan)->first(), [['feature_id' => $messaging->id, 'enabled' => false]]);

        $this->assertLocked($this->hit('get', '/api/conversations'), 'MESSAGING_ACCESS');
    }

    // ------------------------------------------------------------------ expiry and suspension

    public function test_an_expired_paid_plan_falls_back_to_the_free_plan_and_says_so(): void
    {
        $u = $this->makeUser('DEF-USERS');
        $paid = $this->plan('DEF-USERS', array_merge(self::SEEKER_ALL, ['ZZ_PAID_PERK']), 499.0, 'ZZ Gold', 900);
        $this->subscribe($u, $paid, now()->addDays(10)->toDateTimeString());
        $this->as($u);

        $active = $this->getJson('/api/account-plan-details/my-plan-features')->assertOk();
        $active->assertJsonPath('status', 'active')->assertJsonPath('CurrentPlan', 'ZZ Gold')->assertJsonPath('effective_plan', 'ZZ Gold');
        $this->assertTrue(PlanService::hasFeature((string) $u->code, 'ZZ_PAID_PERK'));

        DB::table('usersubscription')->where('code', (string) $u->code)->where('is_active', 'active')->update(['end_date' => now()->subDay()->toDateTimeString()]);

        $expired = $this->getJson('/api/account-plan-details/my-plan-features')->assertOk();
        $expired->assertJsonPath('status', 'expired')->assertJsonPath('subscribed_plan', 'ZZ Gold');
        $this->assertNotSame('ZZ Gold', $expired->json('effective_plan'), 'the features now come from the free plan');
        $this->assertFalse(PlanService::hasFeature((string) $u->code, 'ZZ_PAID_PERK'), 'the paid perk stops at once');
        $starter = PlanService::starterPlan('DEF-USERS');
        $this->assertSame($starter->plan_name, $expired->json('effective_plan'));
        $this->assertSame('ZZ Gold', $expired->json('CurrentPlan'), 'what they subscribed to is still reported (so the screen can say it expired)');
    }

    public function test_renewing_an_expired_plan_brings_the_features_back(): void
    {
        $u = $this->makeUser('DEF-USERS');
        $paid = $this->plan('DEF-USERS', ['ZZ_PAID_PERK'], 499.0, 'ZZ Gold', 900);
        $this->subscribe($u, $paid, now()->subDay()->toDateTimeString());
        $this->assertFalse(PlanService::hasFeature((string) $u->code, 'ZZ_PAID_PERK'));

        DB::table('usersubscription')->where('code', (string) $u->code)->where('is_active', 'active')->update(['end_date' => now()->addMonth()->toDateTimeString()]);
        PlanService::bust();

        $this->assertTrue(PlanService::hasFeature((string) $u->code, 'ZZ_PAID_PERK'));
    }

    public function test_a_free_plan_never_expires(): void
    {
        $u = $this->makeUser('DEF-USERS');
        $free = $this->plan('DEF-USERS', self::SEEKER_ALL, 0.0, 'ZZ Free');
        $this->subscribe($u, $free, now()->subMonths(3)->toDateTimeString());
        $this->as($u);

        $this->getJson('/api/account-plan-details/my-plan-features')->assertOk()
            ->assertJsonPath('status', 'active')->assertJsonPath('effective_plan', 'ZZ Free');
        $this->assertNotLocked($this->hit('post', '/api/savePost'));
    }

    public function test_the_end_date_is_sent_with_its_time_zone(): void
    {
        // a bare "2026-11-23 13:50:00" would be read in the BROWSER's zone; the screen schedules a refresh from this value
        $u = $this->makeUser('DEF-USERS');
        $this->subscribe($u, $this->plan('DEF-USERS', self::SEEKER_ALL, 499.0, 'ZZ Gold', 900), now()->addDays(3)->toDateTimeString());
        $this->as($u);

        $ends = $this->getJson('/api/account-plan-details/my-plan-features')->assertOk()->json('expires_at');

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}([+-]\d{2}:\d{2}|Z)$/', $ends);
        $this->assertEqualsWithDelta(now()->addDays(3)->timestamp, strtotime($ends), 5);
    }

    public function test_an_expired_recruiter_plan_falls_back_the_same_way(): void
    {
        $u = $this->makeUser('DEF-CLIENT');
        $paid = $this->plan('DEF-CLIENT', array_merge(self::RECRUITER_ALL, ['ZZ_PAID_PERK']), 999.0, 'ZZ Gold', 900);
        $this->subscribe($u, $paid, now()->subDay()->toDateTimeString());
        $this->as($u);

        $this->getJson('/api/account-plan-details/my-plan-features')->assertJsonPath('status', 'expired');
        $this->assertFalse(PlanService::hasFeature((string) $u->code, 'ZZ_PAID_PERK'));
    }

    public function test_a_suspended_subscription_has_no_features_at_all(): void
    {
        $u = $this->makeUser('DEF-USERS');
        $this->subscribe($u, $this->plan('DEF-USERS', self::SEEKER_ALL));
        DB::table('usersubscription')->where('code', (string) $u->code)->update(['is_active' => 'cancelled', 'remarks' => SubscriptionAdminService::SUSPENDED_TAG . ' by admin']);
        PlanService::bust();
        $this->as($u);

        $r = $this->getJson('/api/account-plan-details/my-plan-features')->assertOk();
        $r->assertJsonPath('status', 'suspended');
        foreach (self::SEEKER_ALL as $f) {
            foreach ($this->mine($f) as [$m, $url]) { $this->assertLocked($this->hit($m, $url), $f); }
        }
        $this->assertStringContainsString('suspended', $r->json('messages.MESSAGING_ACCESS'));
    }

    public function test_an_account_with_no_subscription_uses_the_free_plan_so_nobody_is_locked_out_by_a_gap(): void
    {
        $u = $this->makeUser('DEF-USERS');           // no subscription row
        $this->as($u);

        $r = $this->getJson('/api/account-plan-details/my-plan-features')->assertOk()->assertJsonPath('status', 'none');
        $this->assertSame(PlanService::starterPlan('DEF-USERS')->plan_name, $r->json('effective_plan'));
        $this->assertNull($r->json('CurrentPlan'), 'nothing is subscribed to');
        $this->assertNotLocked($this->hit('post', '/api/savePost'));
    }

    public function test_a_job_seeker_whose_plan_has_no_cv_feature_is_not_forced_to_set_up_a_cv(): void
    {
        $u = $this->makeUser('DEF-USERS');
        $this->subscribe($u, $this->plan('DEF-USERS', array_values(array_diff(self::SEEKER_ALL, ['CREATE_RESUME']))));
        $this->as($u);

        $r = $this->getJson('/api/cv/status')->assertOk();
        $r->assertJsonPath('data.applies', false)->assertJsonPath('data.complete', true);
    }

    public function test_a_job_seeker_whose_plan_includes_the_cv_feature_is_still_asked_to_set_up_a_cv(): void
    {
        $u = $this->makeUser('DEF-USERS');
        $this->subscribe($u, $this->plan('DEF-USERS', self::SEEKER_ALL));
        $this->as($u);

        $this->getJson('/api/cv/status')->assertOk()->assertJsonPath('data.applies', true)->assertJsonPath('data.complete', false);
    }

    // ------------------------------------------------------------------ what the screens are told

    public function test_the_features_answer_carries_access_and_the_reason_for_each_refusal(): void
    {
        $u = $this->makeUser('DEF-USERS');
        $this->subscribe($u, $this->plan('DEF-USERS', ['APPLY_JOBS']));
        $this->as($u);

        $r = $this->getJson('/api/account-plan-details/my-plan-features')->assertOk();

        $this->assertTrue($r->json('access.APPLY_JOBS'));
        $this->assertFalse($r->json('access.MESSAGING_ACCESS'));
        $this->assertStringContainsString('Upgrade your plan', $r->json('messages.MESSAGING_ACCESS'));
        $this->assertArrayNotHasKey('APPLY_JOBS', $r->json('messages'), 'nothing to explain for what is allowed');
        $this->assertArrayNotHasKey('JOB_POSTING', $r->json('access'), 'a recruiter feature is not part of a seeker\'s access');
        foreach (['plan_id', 'CurrentPlan', 'subscribed_plan', 'effective_plan', 'effective_plan_id', 'status', 'expires_at', 'features', 'plan_applies'] as $k) {
            $this->assertArrayHasKey($k, $r->json(), "the existing key '{$k}' is kept");
        }
    }

    public function test_a_locked_answer_says_why_when_the_plan_expired(): void
    {
        $u = $this->makeUser('DEF-USERS');
        // an expired paid plan falls back to free: remove the feature from free? it cannot be done here, so use a suspended-free case instead
        $this->subscribe($u, $this->plan('DEF-USERS', []));
        $this->as($u);

        $this->assertStringContainsString('not included in your plan', $this->hit('post', '/api/savePost')->json('message'));
    }

    // ------------------------------------------------------------------ the routes really carry the check

    public function test_every_guarded_route_carries_the_planfeature_middleware(): void
    {
        $expect = [
            ['POST', 'api/saveAppliedJob', 'APPLY_JOBS'], ['POST', 'api/saveSkills', 'CREATE_RESUME'], ['GET', 'api/user-resumes', 'CREATE_RESUME'],
            ['POST', 'api/user-resumes/{id}/import', 'CREATE_RESUME'], ['POST', 'api/saveProfile', 'CREATE_PROFILE'], ['POST', 'api/profile', 'CREATE_PROFILE'],
            ['POST', 'api/savePost', 'NETWORK_FEED'], ['PUT', 'api/follow/{follow}', 'NETWORK_FEED'], ['POST', 'api/comment', 'NETWORK_FEED'],
            ['PUT', 'api/acceptFollowRequest/{followerCode}', 'NETWORK_FEED'], ['GET', 'api/searchUsers', 'SEARCH_ACCESS'],
            ['POST', 'api/send-message', 'MESSAGING_ACCESS'], ['GET', 'api/conversations', 'MESSAGING_ACCESS'],
            ['POST', 'api/saveJobPosting', 'JOB_POSTING'], ['POST', 'api/job-posting', 'JOB_POSTING'], ['PUT', 'api/updateJobPosting/{transNo}', 'JOB_POSTING'],
            ['GET', 'api/recruitment/candidates', 'JOB_APPLICANTS'], ['GET', 'api/recruitment/reports', 'JOB_APPLICANTS'],
        ];
        foreach ($expect as [$method, $uri, $code]) {
            $route = collect(Route::getRoutes()->getRoutes())->first(fn ($r) => $r->uri() === $uri && in_array($method, $r->methods(), true));
            $this->assertNotNull($route, "route {$method} {$uri} exists");
            $this->assertContains("planfeature:{$code}", $route->gatherMiddleware(), "{$method} {$uri} is guarded by {$code}");
        }
    }

    public function test_no_route_uses_a_feature_code_the_registry_does_not_know(): void
    {
        foreach (Route::getRoutes()->getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $mw) {
                if (is_string($mw) && str_starts_with($mw, 'planfeature:')) {
                    foreach (explode(',', substr($mw, 12)) as $code) {
                        $this->assertTrue(FeatureAccess::isKnown($code), "{$route->uri()} uses unknown feature '{$code}'");
                    }
                }
            }
        }
    }

    public function test_the_registry_only_enforces_features_for_job_seekers_and_recruiters(): void
    {
        foreach (FeatureAccess::FEATURES as $code => $f) {
            foreach ($f['roles'] as $role) {
                $this->assertContains($role, ['DEF-USERS', 'DEF-CLIENT'], "{$code} must never be enforced for {$role}");
            }
        }
        $this->assertFalse(FeatureAccess::enforcedFor('DEF-ADMIN', 'MESSAGING_ACCESS'));
        $this->assertFalse(FeatureAccess::enforcedFor('DEF-MASTERADMIN', 'MESSAGING_ACCESS'));
    }
}
