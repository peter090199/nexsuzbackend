<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Profile and post privacy, enforced by the API. Synthetic accounts; rows roll back. */
class ProfilePrivacyTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;
    private User $friend;
    private User $stranger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = $this->account('DEF-CLIENT');
        $this->friend = $this->account();
        $this->stranger = $this->account();
        DB::table('follows')->insert(['follower_code' => $this->friend->code, 'following_code' => $this->owner->code, 'follow_status' => 'accepted']);
        DB::table('userprofiles')->insert(['code' => $this->owner->code, 'transNo' => 1, 'email' => $this->owner->email,
            'contact_no' => '+639170000001', 'summary' => 'We hire', 'contact_visibility' => 0, 'email_visibility' => 0]);
        DB::table('userskills')->insert(['code' => $this->owner->code, 'transNo' => 1, 'skills' => 'Hiring']);
        foreach ([0 => 'only me', 1 => 'for all', 2 => 'friends'] as $status => $caption) {
            DB::table('posts')->insert(['code' => $this->owner->code, 'posts_uuid' => "zz-{$this->owner->code}-{$status}", 'transNo' => 1,
                'caption' => $caption, 'status' => $status, 'created_at' => now()->subMinutes(10 - $status), 'updated_at' => now()]);
        }
    }

    private function account(string $role = 'DEF-USERS'): User
    {
        static $next = 899000;
        $code = $next += random_int(2, 30);
        $u = User::forceCreate(['fname' => 'Pv', 'lname' => 'Tester', 'fullname' => "Pv Tester {$code}", 'role_code' => $role,
            'email' => "pv{$code}@example.test", 'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']);
        DB::table('resources')->insert(['code' => $code, 'fname' => 'Pv', 'lname' => 'Tester', 'fullname' => $u->fullname,
            'email' => $u->email, 'role_code' => $role, 'company' => 'ZZ Co', 'age' => 30, 'date_birth' => '1990-01-01']);

        return $u;
    }

    private function as(?User $u): self
    {
        $this->app['auth']->forgetGuards();
        if ($u) { Sanctum::actingAs(User::find($u->id)); }

        return $this;
    }

    private function privacy(array $change): void
    {
        $this->as($this->owner)->putJson('/api/privacy-settings', $change + ['profile' => 'public', 'contact' => 'public',
            'experience' => 'public', 'personal' => 'public', 'visitors' => false, 'cv_link' => true])->assertOk();
    }

    private function profile(User $viewer): array
    {
        return $this->as($viewer)->getJson('/api/profile/' . $this->owner->code)->assertOk()->json('message');
    }

    private function captions(User $viewer): array
    {
        return array_column($this->as($viewer)->getJson('/api/post?code=' . $this->owner->code)->assertOk()->json('data'), 'caption');
    }

    public function test_posts_follow_their_visibility_and_are_newest_first(): void
    {
        $this->assertSame(['friends', 'for all', 'only me'], $this->captions($this->owner));
        $this->assertSame(['friends', 'for all'], $this->captions($this->friend));
        $this->assertSame(['for all'], $this->captions($this->stranger));
        $this->as($this->owner)->getJson('/api/post?code=' . $this->owner->code . '&page=2')->assertOk()
            ->assertJsonPath('data', [])->assertJsonPath('has_more', false);
    }

    public function test_the_hide_switches_hide_contact_details_from_others_only(): void
    {
        DB::table('userprofiles')->where('code', $this->owner->code)->update(['contact_visibility' => 1, 'email_visibility' => 1]);

        $seen = $this->profile($this->friend);
        $this->assertNull($seen['email']);
        $this->assertNull($seen['contact_no']);
        $own = $this->profile($this->owner);
        $this->assertSame($this->owner->email, $own['email']);

        $card = $this->as($this->stranger)->getJson('/api/company/profile/' . $this->owner->code)->assertOk();
        $card->assertJsonPath('message.email', null)->assertJsonPath('message.contact_no', null)->assertJsonPath('message.company', 'ZZ Co');
        foreach (['age', 'date_birth', 'resumepdf'] as $field) {
            $this->assertArrayNotHasKey($field, $card->json('message'));
        }
    }

    public function test_a_connections_only_profile_is_closed_to_strangers(): void
    {
        $this->privacy(['profile' => 'connections']);

        $stranger = $this->profile($this->stranger);
        $this->assertTrue($stranger['restricted']);
        $this->assertNull($stranger['summary']);
        $this->assertNull($stranger['email']);
        $this->assertSame([], $stranger['lines']['skills']);
        $this->assertSame([], $this->captions($this->stranger));
        $this->assertSame(0, $this->as($this->stranger)->getJson('/api/company/people/' . $this->owner->code)->assertOk()->json('total') ?? 0);

        $this->assertSame('We hire', $this->profile($this->friend)['summary']);
        $this->assertSame(['friends', 'for all'], $this->captions($this->friend));
    }

    public function test_sections_can_be_limited_separately(): void
    {
        $this->privacy(['contact' => 'private', 'experience' => 'connections']);

        $friend = $this->profile($this->friend);
        $this->assertNull($friend['email']);
        $this->assertCount(1, $friend['lines']['skills']);
        $stranger = $this->profile($this->stranger);
        $this->assertSame([], $stranger['lines']['skills']);
        $this->assertSame('We hire', $stranger['summary']);
    }

    public function test_logged_out_visitors_see_a_profile_only_after_opting_in_and_never_contact_details(): void
    {
        $url = '/api/public/profile/' . $this->owner->code;
        $this->as(null)->getJson($url)->assertStatus(404);

        $this->privacy(['visitors' => true]);
        $res = $this->as(null)->getJson($url)->assertOk();
        $this->assertSame(['for all'], array_column($res->json('data.posts'), 'caption'));
        $this->assertStringNotContainsString($this->owner->email, $res->getContent());
        $this->assertStringNotContainsString('639170000001', $res->getContent());
        $this->assertStringNotContainsString('Hiring', $res->getContent());

        $this->privacy(['profile' => 'private', 'visitors' => true]);
        $this->as(null)->getJson($url)->assertStatus(404);
    }

    public function test_the_cv_link_respects_privacy(): void
    {
        $seeker = $this->account();
        $link = $this->as($seeker)->getJson('/api/cv/share-link')->assertOk()->json('url');
        $path = '/api/cv/shared/' . substr($link, strpos($link, '/cv/') + 4);
        $this->as(null)->getJson($path)->assertOk();

        $this->as($seeker)->putJson('/api/privacy-settings', ['profile' => 'private', 'contact' => 'public', 'experience' => 'public',
            'personal' => 'public', 'visitors' => false, 'cv_link' => true])->assertOk();
        $this->as(null)->getJson($path)->assertStatus(404);
        $this->as($seeker)->getJson('/api/cv/share-link')->assertStatus(409);
    }

    public function test_only_a_recruiter_edits_company_information_and_only_their_own(): void
    {
        $data = ['company' => 'New Co', 'industry' => 'IT', 'companywebsite' => 'new.example', 'location' => 'Cebu', 'about' => 'About us'];
        $this->as($this->stranger)->putJson('/api/company-details', $data)->assertStatus(403);
        $this->as($this->owner)->putJson('/api/company-details', $data)->assertOk();
        $seen = $this->profile($this->friend);
        $this->assertSame(['New Co', 'IT', 'About us'], [$seen['company'], $seen['industry'], $seen['summary']]);
    }

    public function test_the_connections_list_has_what_the_tab_needs(): void
    {
        DB::table('resources')->where('code', $this->friend->code)->update(['current_location' => 'Cebu']);
        $asker = $this->account();
        DB::table('follows')->insert(['follower_code' => $this->friend->code, 'following_code' => $asker->code, 'follow_status' => 'pending']);
        $list = function (User $viewer) {
            $r = $this->as($viewer)->getJson('/api/company/people/' . $this->owner->code)->assertOk();

            return collect(array_merge($r->json('online'), $r->json('offline')))->firstWhere('code', $this->friend->code);
        };

        $row = $list($asker);
        $this->assertSame(['connect_back', 'Cebu', $this->friend->id], [$row['follow_status'], $row['location'], $row['user_id']]);

        DB::table('users')->where('code', $this->friend->code)->update(['privacy_settings' => json_encode(['profile' => 'private'])]);
        $this->assertNull($list($asker)['location']);
    }

    public function test_only_the_owner_can_edit_or_delete_a_post(): void
    {
        $id = DB::table('posts')->where('posts_uuid', "zz-{$this->owner->code}-1")->value('id');
        $this->as($this->stranger)->deleteJson("/api/post/zz-{$this->owner->code}-1");
        $this->assertTrue(DB::table('posts')->where('id', $id)->exists());
    }
}
