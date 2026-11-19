<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Connect / connected: send, cancel, accept, unfollow, status between two people. Rolled back. */
class ConnectApiTest extends TestCase
{
    use DatabaseTransactions;

    private User $me;
    private User $pal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->me = $this->makeUser('Mia');
        $this->pal = $this->makeUser('Pal');
    }

    private function makeUser(string $name): User
    {
        static $next = 870000;
        $code = $next += random_int(1, 40);

        return User::forceCreate([
            'fname' => $name, 'lname' => 'Tester', 'fullname' => "$name Tester",
            'email' => strtolower($name) . $code . '@example.test', 'password' => bcrypt('secret-pass'),
            'status' => 'A', 'code' => $code, 'coverphoto' => '',
        ]);
    }

    private function as(User $u): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs(User::find($u->id));
    }

    private function rows(): int
    {
        return DB::table('follows')->where(fn ($q) => $q
            ->where(['follower_code' => $this->me->code, 'following_code' => $this->pal->code])
            ->orWhere(['follower_code' => $this->pal->code, 'following_code' => $this->me->code]))->count();
    }

    public function test_connect_sends_a_pending_request_and_a_second_click_cancels_it(): void
    {
        $this->as($this->me);
        $this->putJson("/api/follow/{$this->pal->code}")->assertOk()->assertJsonPath('follow_status', 'pending');
        $this->getJson("/api/getPendingFollowStatus/{$this->pal->code}")->assertJsonPath('follow_status', 'pending');

        $this->putJson("/api/follow/{$this->pal->code}")->assertOk()->assertJsonPath('follow_status', 'none');
        $this->assertSame(0, $this->rows());
    }

    public function test_you_cannot_connect_with_yourself(): void
    {
        $this->as($this->me);
        $this->putJson("/api/follow/{$this->me->code}")->assertStatus(400);
        $this->assertSame(0, DB::table('follows')->where('follower_code', $this->me->code)->count());
    }

    public function test_accepting_makes_both_people_connected(): void
    {
        $this->as($this->me);
        $this->putJson("/api/follow/{$this->pal->code}")->assertOk();

        $this->as($this->pal);
        $this->putJson("/api/acceptFollowRequest/{$this->me->code}")->assertOk()->assertJsonPath('follow_status', 'accepted');

        $this->getJson("/api/getPendingFollowStatus/{$this->me->code}")->assertJsonPath('follow_status', 'accepted');
        $this->as($this->me);
        $this->getJson("/api/getPendingFollowStatus/{$this->pal->code}")->assertJsonPath('follow_status', 'accepted');
    }

    public function test_connecting_back_to_someone_who_already_asked_you_accepts_instead_of_duplicating(): void
    {
        $this->as($this->pal);
        $this->putJson("/api/follow/{$this->me->code}")->assertOk();

        $this->as($this->me);
        $this->putJson("/api/follow/{$this->pal->code}")->assertOk()->assertJsonPath('follow_status', 'accepted');
        $this->assertSame(1, $this->rows());
    }

    public function test_unfollow_removes_the_connection(): void
    {
        $this->as($this->me);
        $this->putJson("/api/follow/{$this->pal->code}")->assertOk();
        $this->as($this->pal);
        $this->putJson("/api/acceptFollowRequest/{$this->me->code}")->assertOk();

        $id = DB::table('follows')->where('follower_code', $this->me->code)->value('id');
        $this->deleteJson("/api/unfollow/$id")->assertOk()->assertJsonPath('follow_status', 'none');
        $this->assertSame(0, $this->rows());
    }

    public function test_a_stranger_cannot_remove_someone_elses_connection(): void
    {
        $this->as($this->me);
        $this->putJson("/api/follow/{$this->pal->code}")->assertOk();
        $id = DB::table('follows')->where('follower_code', $this->me->code)->value('id');

        $this->as($this->makeUser('Otto'));
        $this->deleteJson("/api/unfollow/$id")->assertNotFound();
        $this->assertSame(1, $this->rows());
    }

    public function test_accept_needs_a_real_pending_request(): void
    {
        $this->as($this->pal);
        $this->putJson("/api/acceptFollowRequest/{$this->me->code}")->assertNotFound();
    }

    public function test_guests_are_refused(): void
    {
        $this->putJson("/api/follow/{$this->pal->code}")->assertUnauthorized();
    }

    // ---- the people lists the Network page shows (Connect / Pending / Connected / Connect Back) ----

    private function seedPerson(User $u): void
    {
        DB::table('resources')->insert(['code' => $u->code, 'fullname' => $u->fullname, 'email' => $u->email, 'profession' => 'Tester', 'industry' => 'IT']);
        DB::table('user_activity')->insert(['viewer_code' => $this->me->code, 'viewed_code' => $u->code, 'activity_type' => 'profile_view']);
    }

    private function statusOf(User $u): array
    {
        $row = collect($this->getJson('/api/getPeopleRecentActivity')->assertOk()->json('data'))->firstWhere('code', $u->code);
        $this->assertNotNull($row, 'person missing from the list');

        return [$row['follow_status'], $row['id'] ?? null];
    }

    public function test_the_list_shows_each_state_and_the_row_id_needed_to_unfollow(): void
    {
        $this->seedPerson($this->pal);
        $this->as($this->me);
        $this->assertSame(['not_following', null], $this->statusOf($this->pal));

        $res = $this->putJson("/api/follow/{$this->pal->code}")->assertOk()->assertJsonPath('follow_status', 'pending');
        $this->assertNotNull($res->json('follow_id'));
        $this->assertSame(['pending', $res->json('follow_id')], $this->statusOf($this->pal));

        $this->as($this->pal);
        $this->putJson("/api/acceptFollowRequest/{$this->me->code}")->assertOk();
        $this->as($this->me);
        [$status, $id] = $this->statusOf($this->pal);
        $this->assertSame('accepted', $status);
        $this->deleteJson("/api/unfollow/$id")->assertOk();
        $this->assertSame('not_following', $this->statusOf($this->pal)[0]);
    }

    public function test_someone_who_asked_me_shows_as_connect_back_and_one_click_connects(): void
    {
        $this->seedPerson($this->pal);
        $this->as($this->pal);
        $this->putJson("/api/follow/{$this->me->code}")->assertOk();

        $this->as($this->me);
        $this->assertSame('connect_back', $this->statusOf($this->pal)[0]);
        $res = $this->putJson("/api/follow/{$this->pal->code}")->assertOk()->assertJsonPath('follow_status', 'accepted');
        $this->assertNotNull($res->json('follow_id'));
        $this->assertSame('accepted', $this->statusOf($this->pal)[0]);
    }

    public function test_a_connection_they_started_can_still_be_removed_by_me(): void
    {
        $this->seedPerson($this->pal);
        $this->as($this->pal);
        $this->putJson("/api/follow/{$this->me->code}")->assertOk();
        $this->as($this->me);
        $this->putJson("/api/acceptFollowRequest/{$this->pal->code}")->assertOk();

        [$status, $id] = $this->statusOf($this->pal);
        $this->assertSame('accepted', $status);
        $this->deleteJson("/api/unfollow/$id")->assertOk();
        $this->assertSame(0, $this->rows());
    }

    public function test_people_you_may_know_loads_and_reports_the_same_states(): void
    {
        DB::table('resources')->insert(['code' => $this->me->code, 'fullname' => 'Me', 'email' => $this->me->email, 'profession' => 'Tester', 'industry' => 'IT']);
        DB::table('resources')->insert(['code' => $this->pal->code, 'fullname' => 'Pal', 'email' => $this->pal->email, 'profession' => 'Tester', 'industry' => 'IT']);
        $this->as($this->pal);
        $this->putJson("/api/follow/{$this->me->code}")->assertOk();

        $this->as($this->me);
        $row = collect($this->getJson('/api/getPeopleyoumayknow')->assertOk()->assertJsonPath('success', true)->json('data'))->firstWhere('code', $this->pal->code);
        $this->assertSame('connect_back', $row['follow_status']);
    }
}
