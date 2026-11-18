<?php

namespace Tests\Feature;

use App\Helpers\MediaUrl;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Facebook-style reactions and comments, with several users.
 * Rolled-back transactions + fake public disk: nothing is left behind.
 */
class ReactionsAndCommentsApiTest extends TestCase
{
    use DatabaseTransactions;

    private User $alice;   // owns the post
    private User $bob;
    private User $carol;
    private $post;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->alice = $this->makeUser('Alice');
        $this->bob = $this->makeUser('Bob');
        $this->carol = $this->makeUser('Carol');

        $this->as($this->alice);
        $this->postJson('/api/savePost', ['caption' => 'A post to react to', 'status' => 1])->assertOk();
        $this->post = DB::table('posts')->where('code', $this->alice->code)->first();
    }

    private function makeUser(string $name): User
    {
        static $next = 800000;
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

    private function react(string $reaction, ?int $postId = null)
    {
        return $this->postJson('/api/reactToPostById', ['post_id' => $postId ?? $this->post->id, 'reaction' => $reaction]);
    }

    private function comment(string $text = 'Nice post', array $extra = [])
    {
        return $this->postJson('/api/comment', array_merge(['post_uuid' => $this->post->posts_uuid, 'comment' => $text], $extra));
    }

    private function thread(array $query = [])
    {
        return $this->getJson('/api/comment?' . http_build_query(array_merge(['post_uuidOrUind' => $this->post->posts_uuid, 'per_page' => 50], $query)));
    }

    // ======================================================================== POST REACTIONS

    public function test_every_offered_reaction_is_accepted_and_unknown_ones_are_not(): void
    {
        $this->as($this->bob);
        foreach (['Like', 'Love', 'Care', 'Haha', 'Wow', 'Sad', 'Angry'] as $r) {
            $this->react($r)->assertOk()->assertJson(['success' => true, 'my_reaction' => $r]);
        }
        $this->react('Meh')->assertStatus(422);
        $this->react('Unlike')->assertStatus(422);
    }

    public function test_a_user_has_one_reaction_per_post_which_can_be_changed_and_removed(): void
    {
        $this->as($this->bob);

        $this->react('Like')->assertOk();
        $this->react('Like')->assertOk();                  // repeat click
        $res = $this->react('Love')->assertOk();           // change

        $this->assertSame(1, DB::table('reactionpost')->where('post_id', $this->post->id)->where('code', (string) $this->bob->code)->count());
        $this->assertSame('Love', $res->json('reactions.mine'));
        $this->assertSame(1, $res->json('reactions.total'));

        $res = $this->postJson('/api/removeReactionByPostId', ['post_id' => $this->post->id])->assertOk();
        $this->assertSame(0, $res->json('reactions.total'));
        $this->assertNull($res->json('my_reaction'));
    }

    public function test_the_database_itself_refuses_a_duplicate_reaction(): void
    {
        $row = ['post_id' => $this->post->id, 'code' => (string) $this->bob->code, 'reaction' => 'Like'];
        DB::table('reactionpost')->insert($row);

        $this->expectException(QueryException::class);
        DB::table('reactionpost')->insert($row);
    }

    public function test_counts_and_breakdown_across_several_users(): void
    {
        $this->as($this->alice);
        $this->react('Like');
        $this->as($this->bob);
        $this->react('Love');
        $this->as($this->carol);
        $res = $this->react('Love')->assertOk();

        $this->assertSame(3, $res->json('reactions.total'));
        $this->assertSame([['reaction' => 'Love', 'count' => 2], ['reaction' => 'Like', 'count' => 1]], $res->json('reactions.breakdown'));
        $this->assertSame('Love', $res->json('reactions.mine'));   // each user only sees their own as "mine"

        $this->as($this->alice);
        $feedItem = collect($this->getJson('/api/posts/followed')->json('data'))->firstWhere('id', $this->post->id);
        $this->assertSame('Like', $feedItem['my_reaction']);
        $this->assertSame(3, $feedItem['reaction_summary']['total']);
    }

    public function test_who_reacted_is_listed_by_reaction(): void
    {
        $this->as($this->bob);
        $this->react('Wow');
        $this->as($this->carol);
        $this->react('Wow');

        $data = $this->getJson("/api/getReactionPost/{$this->post->id}")->assertOk()->json();
        $wow = collect($data['react'])->firstWhere('reaction', 'Wow');
        $this->assertSame(2, $wow['count']);
        $this->assertEqualsCanonicalizing(['Bob Tester', 'Carol Tester'], collect($wow['person'])->pluck('fullname')->all());
    }

    public function test_reacting_to_a_missing_post_is_a_404_and_guests_are_refused(): void
    {
        $this->as($this->bob);
        $this->react('Like', 99999999)->assertStatus(404);

        $this->app['auth']->forgetGuards();
        $this->react('Like')->assertStatus(401);
    }

    // ======================================================================== COMMENTS

    public function test_a_comment_is_saved_and_returned_with_author_date_and_flags(): void
    {
        $this->as($this->bob);
        $res = $this->comment('Great post!')->assertStatus(201)->assertJson(['success' => true]);
        $c = $res->json('data');

        $this->assertSame('Great post!', $c['comment']);
        $this->assertSame('Bob Tester', $c['fullname']);
        $this->assertNotEmpty($c['profile_pic']);
        $this->assertNotEmpty($c['created_at']);
        $this->assertNotEmpty($c['date_comment']);
        $this->assertTrue($c['is_owner']);
        $this->assertSame(0, $c['reactions']['total']);
        $this->assertSame([], $c['replies']);
        $this->assertDatabaseHas('commentposts', ['comment_uuid' => $c['comment_uuid'], 'code' => $this->bob->code]);
    }

    public function test_empty_oversized_and_orphan_comments_are_rejected(): void
    {
        $this->as($this->bob);
        $this->comment('')->assertStatus(422);
        $this->comment("   \n  ")->assertStatus(422);
        $this->comment(str_repeat('x', 2001))->assertStatus(422);
        $this->postJson('/api/comment', ['post_uuid' => 'no-such-post', 'comment' => 'hi'])->assertStatus(404);
        $this->assertSame(0, DB::table('commentposts')->where('post_uuidOrUind', $this->post->posts_uuid)->count());
    }

    public function test_a_double_submit_does_not_create_two_comments(): void
    {
        $this->as($this->bob);
        $a = $this->comment('same text')->assertStatus(201)->json('data.comment_uuid');
        $b = $this->comment('same text')->assertOk()->assertJson(['duplicate' => true])->json('data.comment_uuid');

        $this->assertSame($a, $b);
        $this->assertSame(1, DB::table('commentposts')->where('post_uuidOrUind', $this->post->posts_uuid)->count());
    }

    public function test_a_comment_can_carry_an_image(): void
    {
        $this->as($this->bob);
        $res = $this->comment('with a picture', ['image' => UploadedFile::fake()->image('pic.png', 300, 200)])->assertStatus(201);

        $url = $res->json('data.attachment_url');
        $this->assertStringContainsString('/storage/uploads/comments/' . $this->bob->code . '/', $url);
        $this->assertStringStartsWith('http', $url);
        Storage::disk('public')->assertExists(MediaUrl::relativePath($url));
    }

    public function test_an_image_only_comment_is_allowed_but_bad_images_are_not(): void
    {
        $this->as($this->bob);
        $this->comment('', ['image' => UploadedFile::fake()->image('only.jpg')])->assertStatus(201);
        $this->comment('x', ['image' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')])->assertStatus(422);
        $this->comment('x', ['image' => UploadedFile::fake()->image('big.jpg')->size(4000)])->assertStatus(422);
    }

    // ---------------------------------------------------------------------- replies

    public function test_replies_are_nested_under_their_comment_including_replies_to_replies(): void
    {
        $this->as($this->bob);
        $parent = $this->comment('top level')->json('data.comment_uuid');

        $this->as($this->carol);
        $r1 = $this->postJson('/api/commentreply', ['comment_uuid' => $parent, 'comment' => 'first reply'])->assertStatus(201)->json('data');
        $this->as($this->alice);
        $this->postJson('/api/commentreply', ['comment_uuid' => $parent, 'comment' => '@Carol Tester replying to a reply'])->assertStatus(201);

        $this->assertSame('reply', $r1['type']);
        $this->assertSame('Carol Tester', $r1['fullname']);

        $comments = $this->thread()->json('data');
        $this->assertCount(1, $comments);
        $this->assertSame(2, $comments[0]['replies_count']);
        $this->assertSame(['first reply', '@Carol Tester replying to a reply'], array_column($comments[0]['replies'], 'comment'));
    }

    public function test_empty_replies_and_replies_to_missing_comments_are_rejected(): void
    {
        $this->as($this->bob);
        $parent = $this->comment('top')->json('data.comment_uuid');
        $this->postJson('/api/commentreply', ['comment_uuid' => $parent, 'comment' => '  '])->assertStatus(422);
        $this->postJson('/api/commentreply', ['comment_uuid' => 'ghost', 'comment' => 'hi'])->assertStatus(404);
    }

    // ---------------------------------------------------------------------- edit

    public function test_only_the_author_can_edit_a_comment_or_reply(): void
    {
        $this->as($this->bob);
        $uuid = $this->comment('original')->json('data.comment_uuid');
        $replyId = $this->postJson('/api/commentreply', ['comment_uuid' => $uuid, 'comment' => 'my reply'])->json('data.id');

        $res = $this->putJson("/api/comment/{$uuid}", ['comment' => 'edited', 'comment_settings' => 'com_headers'])->assertOk();
        $this->assertSame('edited', $res->json('data.comment'));
        $this->assertTrue($res->json('data.edited'));
        $this->putJson("/api/comment/{$replyId}", ['comment' => 'edited reply', 'comment_settings' => 'com_replies'])->assertOk();

        $this->as($this->carol);
        $this->putJson("/api/comment/{$uuid}", ['comment' => 'hijacked', 'comment_settings' => 'com_headers'])->assertStatus(403);
        $this->putJson("/api/comment/{$replyId}", ['comment' => 'hijacked', 'comment_settings' => 'com_replies'])->assertStatus(403);
        $this->as($this->alice);                                         // even the post owner may not edit someone else's words
        $this->putJson("/api/comment/{$uuid}", ['comment' => 'hijacked', 'comment_settings' => 'com_headers'])->assertStatus(403);

        $this->assertSame('edited', DB::table('commentposts')->where('comment_uuid', $uuid)->value('comment'));
    }

    public function test_editing_cannot_blank_a_comment_and_can_remove_or_replace_its_image(): void
    {
        $this->as($this->bob);
        $uuid = $this->comment('text', ['image' => UploadedFile::fake()->image('a.png')])->json('data.comment_uuid');
        $old = DB::table('commentposts')->where('comment_uuid', $uuid)->value('attachment_url');
        Storage::disk('public')->assertExists(MediaUrl::relativePath($old));

        // blank text is fine while an image remains, but not once the image is removed as well
        $this->putJson("/api/comment/{$uuid}", ['comment' => '', 'comment_settings' => 'com_headers'])->assertOk();
        $this->putJson("/api/comment/{$uuid}", ['comment' => '', 'remove_image' => 1, 'comment_settings' => 'com_headers'])->assertStatus(422);

        // replacing the image deletes the old file
        $this->post("/api/comment/{$uuid}", [
            '_method' => 'PUT', 'comment' => 'new text', 'comment_settings' => 'com_headers',
            'image' => UploadedFile::fake()->image('b.png'),
        ], ['Accept' => 'application/json'])->assertOk();
        $new = DB::table('commentposts')->where('comment_uuid', $uuid)->value('attachment_url');
        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing(MediaUrl::relativePath($old));
        Storage::disk('public')->assertExists(MediaUrl::relativePath($new));

        // removing the image detaches it and deletes the file
        $this->putJson("/api/comment/{$uuid}", ['comment' => 'text only now', 'remove_image' => 1, 'comment_settings' => 'com_headers'])->assertOk();
        $this->assertNull(DB::table('commentposts')->where('comment_uuid', $uuid)->value('attachment_url'));
        Storage::disk('public')->assertMissing(MediaUrl::relativePath($new));
    }

    // ---------------------------------------------------------------------- delete

    public function test_deleting_a_comment_removes_its_replies_reactions_and_images(): void
    {
        $this->as($this->bob);
        $uuid = $this->comment('to delete', ['image' => UploadedFile::fake()->image('c.png')])->json('data.comment_uuid');
        $image = DB::table('commentposts')->where('comment_uuid', $uuid)->value('attachment_url');

        $this->as($this->carol);
        $replyId = $this->postJson('/api/commentreply', ['comment_uuid' => $uuid, 'comment' => 'a reply', 'image' => UploadedFile::fake()->image('d.png')])->json('data.id');
        $replyImage = DB::table('commentreplies')->where('id', $replyId)->value('attachment_url');
        $this->postJson('/api/reactToComment', ['target_type' => 'comment', 'target_ref' => $uuid, 'reaction' => 'Like'])->assertOk();
        $this->postJson('/api/reactToComment', ['target_type' => 'reply', 'target_ref' => (string) $replyId, 'reaction' => 'Love'])->assertOk();

        $this->as($this->bob);
        $this->deleteJson("/api/comment/{$uuid}?comment_settings=com_headers")->assertOk()->assertJson(['success' => true]);

        $this->assertSame(0, DB::table('commentposts')->where('comment_uuid', $uuid)->count());
        $this->assertSame(0, DB::table('commentreplies')->where('comment_uuid', $uuid)->count());
        $this->assertSame(0, DB::table('commentreactions')->where('target_ref', $uuid)->count());
        $this->assertSame(0, DB::table('commentreactions')->where('target_type', 'reply')->where('target_ref', (string) $replyId)->count());
        Storage::disk('public')->assertMissing(MediaUrl::relativePath($image));
        Storage::disk('public')->assertMissing(MediaUrl::relativePath($replyImage));
    }

    public function test_delete_permissions_author_or_post_owner_only(): void
    {
        $this->as($this->bob);
        $bobsComment = $this->comment('bob says hi')->json('data.comment_uuid');
        $bobsReply = $this->postJson('/api/commentreply', ['comment_uuid' => $bobsComment, 'comment' => 'bob reply'])->json('data.id');

        // an unrelated user cannot delete it
        $this->as($this->carol);
        $this->deleteJson("/api/comment/{$bobsComment}?comment_settings=com_headers")->assertStatus(403);
        $this->deleteJson("/api/comment/{$bobsReply}?comment_settings=com_replies")->assertStatus(403);
        $this->assertSame(1, DB::table('commentposts')->where('comment_uuid', $bobsComment)->count());

        // the author of the POST can moderate: delete a reply, then the comment
        $this->as($this->alice);
        $this->deleteJson("/api/comment/{$bobsReply}?comment_settings=com_replies")->assertOk();
        $this->assertSame(0, DB::table('commentreplies')->where('id', $bobsReply)->count());
        $this->deleteJson("/api/comment/{$bobsComment}?comment_settings=com_headers")->assertOk();
        $this->assertSame(0, DB::table('commentposts')->where('comment_uuid', $bobsComment)->count());

        $this->deleteJson('/api/comment/ghost?comment_settings=com_headers')->assertStatus(404);
    }

    // ======================================================================== COMMENT REACTIONS

    public function test_reacting_to_comments_and_replies_one_per_user_changeable_removable(): void
    {
        $this->as($this->bob);
        $uuid = $this->comment('react to me')->json('data.comment_uuid');
        $replyId = $this->postJson('/api/commentreply', ['comment_uuid' => $uuid, 'comment' => 'and me'])->json('data.id');

        $this->as($this->carol);
        $body = ['target_type' => 'comment', 'target_ref' => $uuid];
        $this->postJson('/api/reactToComment', $body + ['reaction' => 'Like'])->assertOk();
        $this->postJson('/api/reactToComment', $body + ['reaction' => 'Like'])->assertOk();           // repeat
        $res = $this->postJson('/api/reactToComment', $body + ['reaction' => 'Care'])->assertOk();     // change
        $this->assertSame('Care', $res->json('reactions.mine'));
        $this->assertSame(1, $res->json('reactions.total'));
        $this->assertSame(1, DB::table('commentreactions')->where('target_ref', $uuid)->where('code', $this->carol->code)->count());

        $this->as($this->alice);
        $res = $this->postJson('/api/reactToComment', $body + ['reaction' => 'Haha'])->assertOk();
        $this->assertSame(2, $res->json('reactions.total'));

        $this->postJson('/api/reactToComment', ['target_type' => 'reply', 'target_ref' => (string) $replyId, 'reaction' => 'Wow'])->assertOk();

        // the thread shows the counts and each viewer's own reaction
        $c = $this->thread()->json('data.0');
        $this->assertSame(2, $c['reactions']['total']);
        $this->assertSame('Haha', $c['reactions']['mine']);
        $this->assertSame(1, $c['replies'][0]['reactions']['total']);

        $res = $this->postJson('/api/removeCommentReaction', $body)->assertOk();
        $this->assertSame(1, $res->json('reactions.total'));
        $this->assertNull($res->json('reactions.mine'));
    }

    public function test_comment_reaction_validation_and_database_constraint(): void
    {
        $this->as($this->bob);
        $uuid = $this->comment('x')->json('data.comment_uuid');

        $this->postJson('/api/reactToComment', ['target_type' => 'comment', 'target_ref' => $uuid, 'reaction' => 'Meh'])->assertStatus(422);
        $this->postJson('/api/reactToComment', ['target_type' => 'post', 'target_ref' => $uuid, 'reaction' => 'Like'])->assertStatus(422);
        $this->postJson('/api/reactToComment', ['target_type' => 'comment', 'target_ref' => 'ghost', 'reaction' => 'Like'])->assertStatus(404);
        $this->postJson('/api/reactToComment', ['target_type' => 'reply', 'target_ref' => '99999999', 'reaction' => 'Like'])->assertStatus(404);

        $row = ['target_type' => 'comment', 'target_ref' => $uuid, 'code' => $this->bob->code, 'reaction' => 'Like'];
        DB::table('commentreactions')->insert($row);
        $this->expectException(QueryException::class);
        DB::table('commentreactions')->insert($row);
    }

    public function test_who_reacted_to_a_comment(): void
    {
        $this->as($this->bob);
        $uuid = $this->comment('who liked')->json('data.comment_uuid');
        $this->as($this->carol);
        $this->postJson('/api/reactToComment', ['target_type' => 'comment', 'target_ref' => $uuid, 'reaction' => 'Love'])->assertOk();
        $this->as($this->alice);
        $this->postJson('/api/reactToComment', ['target_type' => 'comment', 'target_ref' => $uuid, 'reaction' => 'Love'])->assertOk();

        $res = $this->getJson("/api/commentReactors?target_type=comment&target_ref={$uuid}")->assertOk();
        $this->assertSame(2, $res->json('count'));
        $this->assertEqualsCanonicalizing(['Alice Tester', 'Carol Tester'], collect($res->json('react.0.person'))->pluck('fullname')->all());
    }

    // ======================================================================== LIST / PAGINATION / PERSISTENCE

    public function test_comments_load_in_pages_newest_first_without_duplicates(): void
    {
        $this->as($this->bob);
        for ($i = 1; $i <= 7; $i++) {
            DB::table('commentposts')->insert([
                'comment_uuid' => (string) \Illuminate\Support\Str::uuid(), 'post_uuidOrUind' => $this->post->posts_uuid,
                'status' => 0, 'code' => $this->bob->code, 'comment' => "c$i", 'date_comment' => now(),
                'created_by' => 'Bob Tester', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $p1 = $this->thread(['per_page' => 3])->json();
        $this->assertSame(['c7', 'c6', 'c5'], array_column($p1['data'], 'comment'));
        $this->assertTrue($p1['has_more']);
        $this->assertSame(7, $p1['total']);

        $p2 = $this->thread(['per_page' => 3, 'before' => $p1['next_before']])->json();
        $this->assertSame(['c4', 'c3', 'c2'], array_column($p2['data'], 'comment'));
        $this->assertTrue($p2['has_more']);

        $p3 = $this->thread(['per_page' => 3, 'before' => $p2['next_before']])->json();
        $this->assertSame(['c1'], array_column($p3['data'], 'comment'));
        $this->assertFalse($p3['has_more']);
    }

    public function test_without_paging_parameters_the_legacy_plain_array_is_still_returned(): void
    {
        $this->as($this->bob);
        $this->comment('legacy shape');

        $res = $this->getJson('/api/comment?post_uuidOrUind=' . $this->post->posts_uuid)->assertOk()->json();
        $this->assertIsList($res);
        $this->assertSame('legacy shape', $res[0]['comment']);
        $this->assertArrayHasKey('replies', $res[0]);
    }

    public function test_the_feed_comment_count_includes_replies_and_everything_persists_after_a_new_session(): void
    {
        $this->as($this->bob);
        $uuid = $this->comment('one')->json('data.comment_uuid');
        $this->postJson('/api/commentreply', ['comment_uuid' => $uuid, 'comment' => 'two'])->assertStatus(201);
        $this->react('Love')->assertOk();
        $this->postJson('/api/reactToComment', ['target_type' => 'comment', 'target_ref' => $uuid, 'reaction' => 'Wow'])->assertOk();

        // "log in again"
        $this->as($this->bob);
        $item = collect($this->getJson('/api/posts/followed')->json('data'))->firstWhere('id', $this->post->id);
        $this->assertNull($item); // Bob is not connected to Alice, so her post is not in his feed ...

        $this->as($this->alice);                                       // ... but is in Alice's own
        $item = collect($this->getJson('/api/posts/followed')->json('data'))->firstWhere('id', $this->post->id);
        $this->assertSame(2, $item['comments_count']);                  // 1 comment + 1 reply
        $this->assertSame(1, $item['reaction_summary']['total']);

        $thread = $this->thread()->json('data.0');
        $this->assertSame(1, $thread['reactions']['total']);
        $this->assertSame(1, $thread['replies_count']);
    }

    public function test_guests_cannot_comment_reply_react_edit_or_delete(): void
    {
        $this->as($this->bob);
        $uuid = $this->comment('x')->json('data.comment_uuid');

        $this->app['auth']->forgetGuards();
        $this->comment('guest')->assertStatus(401);
        $this->postJson('/api/commentreply', ['comment_uuid' => $uuid, 'comment' => 'guest'])->assertStatus(401);
        $this->postJson('/api/reactToComment', ['target_type' => 'comment', 'target_ref' => $uuid, 'reaction' => 'Like'])->assertStatus(401);
        $this->putJson("/api/comment/{$uuid}", ['comment' => 'g', 'comment_settings' => 'com_headers'])->assertStatus(401);
        $this->deleteJson("/api/comment/{$uuid}")->assertStatus(401);
        $this->assertSame(1, DB::table('commentposts')->where('comment_uuid', $uuid)->count());
    }
}
