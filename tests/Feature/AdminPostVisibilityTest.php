<?php

namespace Tests\Feature;

use App\Events\NewPostCreated;
use App\Helpers\PostVisibility;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Posts of DEF-ADMIN and DEF-MASTERADMIN reach every job seeker and recruiter (no connection needed) according to their
 * privacy, and nobody but the owner can edit or delete them. All rules are enforced by the API.
 * posts.status: 0 private, 1 public, 2 connections only. Rows are InnoDB, so everything rolls back.
 */
class AdminPostVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<string,User> */
    private array $u = [];

    protected function setUp(): void
    {
        parent::setUp();
        static $next = 894000;
        foreach (['admin' => 'DEF-ADMIN', 'admin2' => 'DEF-ADMIN', 'master' => 'DEF-MASTERADMIN', 'seeker' => 'DEF-USERS', 'friend' => 'DEF-USERS',
                  'stranger' => 'DEF-USERS', 'recruiter' => 'DEF-CLIENT'] as $k => $role) {
            $code = $next += random_int(1, 30);
            $this->u[$k] = User::forceCreate(['fname' => $k, 'lname' => 'T', 'fullname' => "$k T", 'role_code' => $role, 'email' => "$k$code@example.test",
                'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']);
        }
        // "friend" is an accepted connection of "admin" (and nobody else's)
        DB::table('follows')->insert(['follower_code' => $this->u['friend']->code, 'following_code' => $this->u['admin']->code, 'follow_status' => 'accepted', 'created_at' => now(), 'updated_at' => now()]);
        Event::fake([NewPostCreated::class]);
    }

    private function as(string $who): void { $this->app['auth']->forgetGuards(); Sanctum::actingAs($this->u[$who]); }

    /** publish a post through the real endpoint */
    private function publish(string $who, int $status, string $caption = 'hello'): object
    {
        $this->as($who);
        $this->postJson('/api/savePost', ['caption' => $caption, 'status' => $status])->assertOk()->assertJsonPath('success', true);
        return DB::table('posts')->where('code', $this->u[$who]->code)->orderByDesc('id')->first();
    }

    private function feedIds(string $who): array
    {
        $this->as($who);
        return collect($this->getJson('/api/getPost')->assertOk()->json('data'))->pluck('id')->all();
    }

    private function profileIds(string $viewer, string $owner): array
    {
        $this->as($viewer);
        return collect($this->getJson('/api/post?code=' . $this->u[$owner]->code)->assertOk()->json('data'))->pluck('id')->all();
    }

    // ------------------------------------------------------------------ creating

    public function test_admins_and_master_admins_can_publish_posts(): void
    {
        foreach (['admin', 'admin2', 'master'] as $who) {
            $post = $this->publish($who, 1, "post by $who");
            $this->assertSame((string) $this->u[$who]->code, (string) $post->code);
            $this->assertSame("post by $who", $post->caption);
            $this->assertSame(1, (int) $post->status);
        }
    }

    // ------------------------------------------------------------------ public

    public function test_a_public_admin_post_reaches_every_job_seeker_and_recruiter_without_a_connection(): void
    {
        $byAdmin = $this->publish('admin', PostVisibility::PUBLIC);
        $byMaster = $this->publish('master', PostVisibility::PUBLIC);

        foreach (['stranger', 'seeker', 'recruiter', 'friend'] as $viewer) {
            $feed = $this->feedIds($viewer);
            $this->assertContains($byAdmin->id, $feed, "$viewer sees the DEF-ADMIN post");
            $this->assertContains($byMaster->id, $feed, "$viewer sees the DEF-MASTERADMIN post");
        }
        $this->assertContains($byAdmin->id, $this->feedIds('admin2'), 'and so does another administrator');
    }

    public function test_the_feed_card_has_author_picture_text_media_and_date(): void
    {
        DB::table('userprofiles')->insert(['code' => $this->u['admin']->code, 'transNo' => (int) DB::table('userprofiles')->max('transNo') + 1, 'email' => $this->u['admin']->email,
            'photo_pic' => 'uploads/x/photo.jpg', 'contact_visibility' => 0, 'email_visibility' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $post = $this->publish('admin', 1, 'Maintenance on Sunday');
        DB::table('attachmentposts')->insert(['code' => $this->u['admin']->code, 'transNo' => $post->transNo, 'posts_uuid' => $post->posts_uuid, 'posts_uuind' => 'u-img-1',
            'status' => 1, 'path_url' => 'uploads/posts/a.jpg', 'posts_type' => 'image', 'created_by' => 'x']);
        DB::table('attachmentposts')->insert(['code' => $this->u['admin']->code, 'transNo' => $post->transNo, 'posts_uuid' => $post->posts_uuid, 'posts_uuind' => 'u-vid-1',
            'status' => 1, 'path_url' => 'uploads/posts/a.mp4', 'posts_type' => 'video', 'created_by' => 'x']);

        $this->as('stranger');
        $card = collect($this->getJson('/api/getPost')->json('data'))->firstWhere('id', $post->id);
        $this->assertSame('admin T', $card['fullname']);
        $this->assertSame('DEF-ADMIN', $card['role_code']);
        $this->assertSame('uploads/x/photo.jpg', $card['profile_pic']);
        $this->assertSame('Maintenance on Sunday', $card['caption']);
        $this->assertNotEmpty($card['created_at']);
        $this->assertCount(1, $card['images']);
        $this->assertCount(1, $card['videos']);
    }

    public function test_a_job_seekers_public_post_still_needs_a_connection_to_reach_a_feed(): void
    {
        $mine = $this->publish('seeker', PostVisibility::PUBLIC);
        $this->assertNotContains($mine->id, $this->feedIds('stranger'), 'ordinary public posts are not pushed to unconnected people');
        $this->assertContains($mine->id, $this->feedIds('seeker'), 'the owner sees their own post');
    }

    // ------------------------------------------------------------------ connections only / private

    public function test_a_connections_only_admin_post_is_for_its_connections(): void
    {
        $post = $this->publish('admin', PostVisibility::FRIENDS);
        $this->assertContains($post->id, $this->feedIds('friend'));
        foreach (['stranger', 'seeker', 'recruiter'] as $viewer) {
            $this->assertNotContains($post->id, $this->feedIds($viewer), "$viewer is not connected");
            $this->assertNotContains($post->id, $this->profileIds($viewer, 'admin'), "$viewer on the profile page");
        }
        $this->assertContains($post->id, $this->profileIds('friend', 'admin'));
    }

    public function test_a_private_post_is_for_its_owner_and_other_administrators_only(): void
    {
        $post = $this->publish('admin', PostVisibility::PRIVATE, 'internal note');

        foreach (['stranger', 'seeker', 'friend', 'recruiter'] as $viewer) {
            $this->assertNotContains($post->id, $this->feedIds($viewer), $viewer);
            $this->assertNotContains($post->id, $this->profileIds($viewer, 'admin'), "$viewer on the profile page");
        }
        $this->assertNotContains($post->id, $this->feedIds('admin'), 'private posts never go into a feed');
        $this->assertContains($post->id, $this->profileIds('admin', 'admin'), 'the owner');
        $this->assertContains($post->id, $this->profileIds('admin2', 'admin'), 'another DEF-ADMIN');
        $this->assertContains($post->id, $this->profileIds('master', 'admin'), 'the master admin');
    }

    public function test_administrators_do_not_get_to_read_a_job_seekers_private_post(): void
    {
        $post = $this->publish('seeker', PostVisibility::PRIVATE, 'my diary');
        foreach (['admin', 'master'] as $viewer) {
            $this->assertNotContains($post->id, $this->profileIds($viewer, 'seeker'), $viewer);
            $this->assertFalse(PostVisibility::canView($this->u['seeker']->code, 0, $this->u[$viewer]->code), $viewer);
        }
        $this->assertContains($post->id, $this->profileIds('seeker', 'seeker'));
    }

    // ------------------------------------------------------------------ reactions and comments follow the same rule

    public function test_anyone_may_react_and_comment_on_a_public_admin_post_but_not_on_a_hidden_one(): void
    {
        $public = $this->publish('admin', 1);
        $friends = $this->publish('admin', 2);
        $private = $this->publish('admin', 0);

        $this->as('stranger');
        $this->postJson('/api/reactToPostById', ['post_id' => $public->id, 'reaction' => 'Like'])->assertOk();
        $this->postJson('/api/comment', ['post_uuid' => $public->posts_uuid, 'comment' => 'nice'])->assertStatus(201);

        foreach ([$friends, $private] as $hidden) {
            $this->postJson('/api/reactToPostById', ['post_id' => $hidden->id, 'reaction' => 'Like'])->assertStatus(403);
            $r = $this->postJson('/api/comment', ['post_uuid' => $hidden->posts_uuid, 'comment' => 'sneaky']);
            $this->assertSame(403, $r->getStatusCode());
        }
        $this->as('friend');
        $this->postJson('/api/reactToPostById', ['post_id' => $friends->id, 'reaction' => 'Like'])->assertOk();
    }

    // ------------------------------------------------------------------ nobody else edits or deletes an administrator's post

    public function test_only_the_owner_can_edit_or_delete_an_administrator_post(): void
    {
        $post = $this->publish('admin', 1, 'original');

        foreach (['admin2', 'master', 'seeker', 'recruiter', 'stranger'] as $intruder) {
            $this->as($intruder);
            $this->postJson("/api/updatePostByTransNo/{$post->transNo}", ['caption' => 'hacked', 'status' => 1])->assertStatus(403);
            $this->putJson("/api/post/{$post->id}", ['caption' => 'hacked', 'status' => 1])->assertStatus(403);
            $this->deleteJson("/api/post/{$post->posts_uuid}")->assertStatus(403);
        }
        $row = DB::table('posts')->where('id', $post->id)->first();
        $this->assertNotNull($row, 'still there');
        $this->assertSame('original', $row->caption, 'unchanged');

        $this->as('admin');
        $this->postJson("/api/updatePostByTransNo/{$post->transNo}", ['caption' => 'edited by owner', 'status' => 1])->assertOk();
        $this->assertSame('edited by owner', DB::table('posts')->where('id', $post->id)->value('caption'));
        $this->deleteJson("/api/post/{$post->posts_uuid}")->assertOk();
        $this->assertNull(DB::table('posts')->where('id', $post->id)->first());
    }

    // ------------------------------------------------------------------ live updates

    public function test_the_live_event_is_only_a_signal_and_never_leaks_content_or_private_posts(): void
    {
        $this->publish('admin', 1, 'secret caption text');
        Event::assertDispatched(NewPostCreated::class, function ($e) {
            return array_keys($e->post) === ['transNo', 'code', 'status', 'created_at'] && !str_contains(json_encode($e->post), 'secret caption text');
        });

        Event::fake([NewPostCreated::class]);
        $this->publish('admin', 0, 'private words');
        Event::assertNotDispatched(NewPostCreated::class);

        $this->publish('master', 2, 'friends words');
        Event::assertDispatched(NewPostCreated::class, fn ($e) => $e->post['status'] === 2 && !str_contains(json_encode($e->post), 'friends words'));
    }

    /** a viewer who reloads after a publish sees it at once: the feed is read from the API, never cached */
    public function test_a_new_public_admin_post_is_in_the_very_next_feed_request(): void
    {
        $before = $this->feedIds('recruiter');
        $post = $this->publish('master', 1);
        $after = $this->feedIds('recruiter');
        $this->assertNotContains($post->id, $before);
        $this->assertSame($post->id, $after[0], 'newest first');
    }
}
