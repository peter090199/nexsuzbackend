<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** applied_jobs, application_status_history, interviews and users are InnoDB, so everything rolls back. */
class MyApplicationsTest extends TestCase
{
    use DatabaseTransactions;

    private function user(string $role): User
    {
        static $next = 867500;
        $code = $next += random_int(2, 20);

        return User::forceCreate(['fname' => 'M', 'lname' => 'A', 'fullname' => 'M A', 'role_code' => $role,
            'email' => "myapp{$code}@example.test", 'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']);
    }

    private function as(?User $u): void
    {
        $this->app['auth']->forgetGuards();
        if ($u) { Sanctum::actingAs($u); }
    }

    /** an application to a job that does not exist (no jobPosting row needed: the list still shows it) */
    private function apply(User $u, string $status, string $job = 'Tester'): int
    {
        $id = DB::table('applied_jobs')->insertGetId(['transNo' => 'TR-TEST-' . $u->code, 'job_name' => $job, 'email' => $u->email,
            'country_code' => '63', 'phone_number' => '1', 'code' => $u->code, 'role_code' => 'DEF-USERS', 'fullname' => $u->fullname,
            'applied_status' => $status, 'created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3)]);
        DB::table('application_status_history')->insert(['applied_id' => $id, 'transNo' => 'TR-TEST-' . $u->code, 'from_status' => null,
            'to_status' => 'applied_active', 'reason' => 'Application received', 'source' => 'applicant', 'created_at' => now()->subDays(3)]);

        return $id;
    }

    public function test_only_a_signed_in_job_seeker_gets_in(): void
    {
        $this->as(null);
        $this->getJson('/api/my-applications')->assertStatus(401);
        foreach (['DEF-CLIENT', 'DEF-ADMIN', 'DEF-MASTERADMIN'] as $role) {
            $this->as($this->user($role));
            $this->getJson('/api/my-applications')->assertStatus(403);
        }
    }

    public function test_the_list_shows_only_my_applications_with_counts(): void
    {
        $me = $this->user('DEF-USERS');
        $other = $this->user('DEF-USERS');
        $this->apply($me, 'review', 'Designer');
        $this->apply($me, 'reject');
        $this->apply($me, 'odd');
        $this->apply($other, 'approved');

        $this->as($me);
        $r = $this->getJson('/api/my-applications')->assertOk()->assertJsonPath('total', 3)
            ->assertJsonPath('counts.review', 1)->assertJsonPath('counts.reject', 1)->assertJsonPath('counts.applied_active', 1)
            ->assertJsonPath('counts.approved', 0);
        $first = collect($r->json('data'))->firstWhere('job_title', 'Designer');
        $this->assertMatchesRegularExpression('/^APP-\d{6}$/', $first['reference']);
        $this->assertFalse($first['job_available']);
    }

    public function test_details_hide_what_recruiters_keep_internal_and_others_get_404(): void
    {
        $me = $this->user('DEF-USERS');
        $id = $this->apply($me, 'reject');
        DB::table('application_status_history')->insert(['applied_id' => $id, 'transNo' => 'x', 'from_status' => 'applied_active', 'to_status' => 'reject',
            'reason' => 'SECRET internal reason', 'source' => 'recruiter', 'changed_by' => '1', 'changed_by_name' => 'Recruiter Name',
            'ai_snapshot' => '{"score":12}', 'created_at' => now()]);
        DB::table('interviews')->insert(['applied_id' => $id, 'transNo' => 'x', 'scheduled_at' => now()->addDay(), 'duration_min' => 30,
            'type' => 'online', 'interviewer_name' => 'Ana', 'status' => 'scheduled', 'feedback' => 'SECRET feedback', 'score' => 40,
            'notes' => 'SECRET notes', 'created_by' => '1', 'created_at' => now(), 'updated_at' => now()]);

        $this->as($me);
        $r = $this->getJson("/api/my-applications/{$id}")->assertOk()->assertJsonPath('data.status', 'reject')
            ->assertJsonPath('data.timeline.0.status', 'applied_active')->assertJsonPath('data.timeline.1.status', 'reject')
            ->assertJsonPath('data.interviews.0.interviewer_name', 'Ana');
        foreach (['SECRET', 'Recruiter Name', 'score', 'ai_snapshot', 'feedback', 'notes'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $r->getContent());
        }

        $this->as($this->user('DEF-USERS'));
        $this->getJson("/api/my-applications/{$id}")->assertStatus(404);
    }

    public function test_latest_updates_are_mine_newest_first_without_internal_details(): void
    {
        $me = $this->user('DEF-USERS');
        $id = $this->apply($me, 'interview', 'Designer');
        DB::table('application_status_history')->insert(['applied_id' => $id, 'transNo' => 'x', 'from_status' => 'applied_active', 'to_status' => 'interview',
            'reason' => 'SECRET reason', 'source' => 'recruiter', 'changed_by_name' => 'Recruiter Name', 'created_at' => now()]);
        $this->apply($this->user('DEF-USERS'), 'approved', 'Not mine');

        $this->as($me);
        $r = $this->getJson('/api/my-applications')->assertOk()->assertJsonCount(2, 'updates')
            ->assertJsonPath('updates.0.status', 'interview')->assertJsonPath('updates.0.job_title', 'Designer')
            ->assertJsonPath('updates.1.status', 'applied_active');
        foreach (['SECRET', 'Recruiter Name', 'Not mine'] as $hidden) { $this->assertStringNotContainsString($hidden, $r->getContent()); }
    }

    public function test_an_applicant_still_cannot_change_a_status(): void
    {
        $me = $this->user('DEF-USERS');
        $id = $this->apply($me, 'review');
        $this->as($me);
        $this->postJson("/api/updateAppliedStatus/{$id}", ['status' => 'approved']);
        $this->assertSame('review', DB::table('applied_jobs')->where('applied_id', $id)->value('applied_status'));
    }
}
