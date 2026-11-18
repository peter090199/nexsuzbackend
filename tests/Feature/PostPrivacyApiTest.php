<?php

namespace Tests\Feature;

use App\Helpers\PostVisibility;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Post privacy is enforced by Laravel, not by the UI.
 *   posts.status 1 = public (everyone), 2 = friends (accepted connections), 0 = private (owner only)
 * Rolled-back transactions + fake public disk: nothing is left behind.
 */
class PostPrivacyApiTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;
    private User $friend;
    private User $stranger;
    private array $post = [];   // ['public' => row, 'friends' => row, 'private' => row]

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->owner = $this->makeUser('Olive');
        $this->friend = $this->makeUser('Frank');
        $this->stranger = $this->makeUser('Sam');

        // Frank follows Olive and Olive accepted: they are friends. Sam has no connection.
        DB::table('follows')->insert([
            'follower_code' => $this->friend->code, 'following_code' => $this->owner->code, 'follow_status' => 'accepted',
        ]);

        $this->as($this->owner);
        foreach (['public' => 1, 'friends' => 2, 'private' => 0] as $name => $status) {
            $this->postJson('/api/savePost', ['caption' => "A $name post", 'status' => $status])->assertOk();
            $this->post[$name] = DB::table('posts')->where('code', $this->owner->code)->where('caption', "A $name post")->first();
        }
    }

    private function makeUser(string $name): User
    {
        static $next = 850000;
        $code = $next += random_int(1, 40);

        return User::forceCreate([
            'fname' => $name, 'lname' => 'Tester', 'fullname' => "$name Tester",
            'email' => strtolower($name) . $code . '@example.test', 'password' => bcrypt('secret-pass'),
            'status' => 'A', 'code' => $code, 'coverphoto' => '',
        ]);
    }

    private function as(User $user): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($user);
    }

    private function captionsSeenBy(User $viewer): array
    {
        $this->as($viewer);
        $rows = $this->getJson('/api/post?code=' . $this->owner->code)->assertOk()->json('data');

        return collect($rows)->pluck('caption')->sort()->values()->all();
    }

    // ---------------------------------------------------------------- saving the privacy

    public function test_friends_is_a_valid_privacy_and_is_saved(): void
    {
        $this->assertSame(2, (int) $this->post['friends']->status);
        $this->as($this->owner);
        $this->postJson('/api/savePost', ['caption' => 'bad', 'status' => 3])->assertStatus(422);
    }

    public function test_the_privacy_of_a_post_can_be_changed_when_editing(): void
    {
        $this->as($this->owner);
        $this->postJson('/api/updatePostByTransNo/' . $this->post['private']->transNo, ['caption' => 'A private post', 'status' => 2])->assertOk();
        $this->assertSame(2, (int) DB::table('posts')->where('id', $this->post['private']->id)->value('status'));
    }

    // ---------------------------------------------------------------- who sees what

    public function test_the_owner_sees_all_three(): void
    {
        $this->assertSame(['A friends post', 'A private post', 'A public post'], $this->captionsSeenBy($this->owner));
    }

    public function test_a_friend_sees_public_and_friends_but_never_private(): void
    {
        $this->assertSame(['A friends post', 'A public post'], $this->captionsSeenBy($this->friend));
    }

    public function test_a_stranger_sees_only_public(): void
    {
        $this->assertSame(['A public post'], $this->captionsSeenBy($this->stranger));
    }

    public function test_a_pending_request_is_not_a_friendship(): void
    {
        $pending = $this->makeUser('Pat');
        DB::table('follows')->insert(['follower_code' => $pending->code, 'following_code' => $this->owner->code, 'follow_status' => 'pending']);

        $this->assertSame(['A public post'], $this->captionsSeenBy($pending));
        $this->assertFalse(PostVisibility::areFriends($pending->code, $this->owner->code));
    }

    public function test_friendship_works_in_either_direction(): void
    {
        $other = $this->makeUser('Dee');
        DB::table('follows')->insert(['follower_code' => $this->owner->code, 'following_code' => $other->code, 'follow_status' => 'accepted']);

        $this->assertSame(['A friends post', 'A public post'], $this->captionsSeenBy($other));
    }

    public function test_the_home_feed_shows_friends_posts_to_friends_only(): void
    {
        $feed = function (User $u) {
            $this->as($u);
            $json = $this->getJson('/api/posts/followed?per_page=50')->assertOk()->json();
            $rows = $json['data'] ?? $json;

            return collect($rows)->pluck('caption')->filter(fn ($c) => str_ends_with((string) $c, ' post'))->sort()->values()->all();
        };

        $this->assertSame(['A friends post', 'A public post'], $feed($this->friend));
        $this->assertSame([], $feed($this->stranger));
    }

    // ---------------------------------------------------------------- reacting

    private function react(User $who, string $post, string $reaction = 'Love')
    {
        $this->as($who);

        return $this->postJson('/api/reactToPostById', ['post_id' => $this->post[$post]->id, 'reaction' => $reaction]);
    }

    public function test_reacting_follows_the_privacy_rules(): void
    {
        $this->react($this->owner, 'private')->assertOk();
        $this->react($this->friend, 'friends')->assertOk();
        $this->react($this->friend, 'public')->assertOk();
        $this->react($this->friend, 'private')->assertStatus(403);
        $this->react($this->stranger, 'public')->assertOk();
        $this->react($this->stranger, 'friends')->assertStatus(403);
        $this->react($this->stranger, 'private')->assertStatus(403);

        $this->assertSame(0, DB::table('reactionpost')->where('post_id', $this->post['private']->id)->where('code', (string) $this->friend->code)->count());
    }

    public function test_one_reaction_per_user_per_post_and_it_can_be_changed_and_removed(): void
    {
        foreach (['Like', 'Haha', 'Wow'] as $r) { $this->react($this->friend, 'public', $r)->assertOk(); }
        $rows = DB::table('reactionpost')->where('post_id', $this->post['public']->id)->where('code', (string) $this->friend->code)->get();

        $this->assertCount(1, $rows);
        $this->assertSame('Wow', $rows[0]->reaction);

        $this->as($this->friend);
        $this->postJson('/api/removeReactionByPostId', ['post_id' => $this->post['public']->id])->assertOk();
        $this->assertSame(0, DB::table('reactionpost')->where('post_id', $this->post['public']->id)->count());
    }

    public function test_only_the_six_reactions_and_the_legacy_one_are_accepted(): void
    {
        foreach (['Like', 'Love', 'Haha', 'Wow', 'Sad', 'Angry'] as $r) { $this->react($this->owner, 'public', $r)->assertOk(); }
        $this->react($this->owner, 'public', 'Nope')->assertStatus(422);
    }

    public function test_who_reacted_and_my_reaction_are_hidden_from_people_who_cannot_see_the_post(): void
    {
        $this->react($this->owner, 'private', 'Like');

        $this->as($this->stranger);
        $this->getJson('/api/getReactionPost/' . $this->post['private']->id)->assertStatus(403);
        $this->as($this->friend);
        $this->getJson('/api/getReactionByPostId/' . $this->post['private']->id)->assertStatus(403);
        $this->as($this->owner);
        $this->getJson('/api/getReactionPost/' . $this->post['private']->id)->assertOk();
    }

    public function test_reacting_can_always_be_taken_back_even_after_the_post_became_private(): void
    {
        $this->react($this->friend, 'friends', 'Love')->assertOk();
        DB::table('posts')->where('id', $this->post['friends']->id)->update(['status' => 0]);   // owner made it private afterwards

        $this->as($this->friend);
        $this->postJson('/api/removeReactionByPostId', ['post_id' => $this->post['friends']->id])->assertOk()->assertJsonPath('reactions.total', 0);
        $this->assertSame(0, DB::table('reactionpost')->where('post_id', $this->post['friends']->id)->count());
    }

    // ---------------------------------------------------------------- comments follow the post

    public function test_comments_follow_the_privacy_rules(): void
    {
        $uuid = fn (string $n) => $this->post[$n]->posts_uuid;

        $this->as($this->stranger);
        $this->getJson('/api/comment?post_uuidOrUind=' . $uuid('friends'))->assertStatus(403);
        $this->getJson('/api/comment?post_uuidOrUind=' . $uuid('private'))->assertStatus(403);
        $this->postJson('/api/comment', ['post_uuid' => $uuid('private'), 'comment' => 'hi'])->assertStatus(403);
        $this->postJson('/api/comment', ['post_uuid' => $uuid('public'), 'comment' => 'hello'])->assertSuccessful();

        $this->as($this->friend);
        $this->getJson('/api/comment?post_uuidOrUind=' . $uuid('friends'))->assertOk();
        $this->postJson('/api/comment', ['post_uuid' => $uuid('friends'), 'comment' => 'hey'])->assertSuccessful();
        $this->getJson('/api/comment?post_uuidOrUind=' . $uuid('private'))->assertStatus(403);
    }

    public function test_replies_and_comment_reactions_on_a_hidden_post_are_refused(): void
    {
        $this->as($this->owner);
        $commentUuid = $this->postJson('/api/comment', ['post_uuid' => $this->post['private']->posts_uuid, 'comment' => 'mine'])
            ->assertSuccessful()->json('data.comment_uuid');
        $this->assertNotEmpty($commentUuid);

        $this->as($this->stranger);
        $this->postJson('/api/commentreply', ['comment_uuid' => $commentUuid, 'comment' => 'nosy'])->assertStatus(403);
        $this->postJson('/api/reactToComment', ['target_type' => 'comment', 'target_ref' => $commentUuid, 'reaction' => 'Like'])->assertStatus(403);
        $this->getJson('/api/commentReactors?target_type=comment&target_ref=' . $commentUuid)->assertStatus(403);
    }

    // ---------------------------------------------------------------- the profile feed returns what the UI needs

    public function test_the_profile_feed_carries_reactions_comments_and_the_privacy_value(): void
    {
        $this->react($this->friend, 'public', 'Love');
        $this->as($this->friend);
        $this->postJson('/api/comment', ['post_uuid' => $this->post['public']->posts_uuid, 'comment' => 'nice'])->assertSuccessful();

        $row = collect($this->getJson('/api/post?code=' . $this->owner->code)->assertOk()->json('data'))
            ->firstWhere('caption', 'A public post');

        $this->assertSame(1, $row['status']);
        $this->assertSame('Love', $row['my_reaction']);
        $this->assertSame(1, $row['reaction_summary']['total']);
        $this->assertSame(1, $row['comments_count']);
        $this->assertSame($this->owner->fullname, $row['fullname']);     // the author, not the viewer
    }
}
