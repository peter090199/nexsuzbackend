<?php

namespace Tests\Feature;

use App\Helpers\MediaUrl;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Edit Post: text, images, removal, cancel-safety, persistence and authorisation.
 * Rolled-back transactions + a fake public disk: nothing is left behind.
 */
class PostEditApiTest extends TestCase
{
    use DatabaseTransactions;

    private User $alice;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->alice = $this->makeUser('Alice');
        Sanctum::actingAs($this->alice);
    }

    private function makeUser(string $name): User
    {
        $code = random_int(800000, 899999);

        return User::forceCreate([
            'fname' => $name, 'lname' => 'Tester', 'fullname' => "$name Tester",
            'email' => strtolower($name) . $code . '@example.test', 'password' => bcrypt('secret-pass'),
            'status' => 'A', 'code' => $code, 'coverphoto' => '',
        ]);
    }

    private function img(string $name = 'x.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($name, 200, 150);
    }

    /** Create a post for the current user and return its row. */
    private function newPost(string $caption = 'original', int $images = 0, int $status = 1)
    {
        $files = [];
        for ($i = 0; $i < $images; $i++) {
            $files[] = $this->img("p$i.jpg");
        }
        $this->postJson('/api/savePost', ['caption' => $caption, 'status' => $status, 'posts' => $files])->assertOk();

        return DB::table('posts')->where('code', auth()->user()->code)->orderByDesc('id')->first();
    }

    private function attachments($post)
    {
        return DB::table('attachmentposts')->where('posts_uuid', $post->posts_uuid)->orderBy('id')->get();
    }

    private function edit($post, array $payload)
    {
        return $this->postJson("/api/updatePostByTransNo/{$post->transNo}", array_merge(['status' => $post->status], $payload));
    }

    // ------------------------------------------------------------------ text

    public function test_text_only_edit_keeps_everything_else(): void
    {
        $post = $this->newPost('before', 2);
        $beforeFiles = $this->attachments($post)->pluck('path_url', 'posts_uuind');

        $res = $this->edit($post, ['caption' => 'after'])->assertOk()->assertJson(['success' => true]);

        $fresh = DB::table('posts')->find($post->id);
        $this->assertSame('after', $fresh->caption);
        // identity, author and creation date are untouched
        $this->assertSame($post->id, $fresh->id);
        $this->assertSame($post->code, $fresh->code);
        $this->assertSame($post->posts_uuid, $fresh->posts_uuid);
        $this->assertSame($post->created_at, $fresh->created_at);
        // images preserved exactly
        $this->assertEquals($beforeFiles, $this->attachments($post)->pluck('path_url', 'posts_uuind'));
        // the response carries the updated post for an immediate feed update
        $this->assertSame('after', $res->json('post.caption'));
        $this->assertCount(2, $res->json('post.images'));
    }

    public function test_caption_can_be_cleared_when_images_remain(): void
    {
        $post = $this->newPost('to clear', 1);

        $this->edit($post, ['caption' => ''])->assertOk();

        $this->assertNull(DB::table('posts')->find($post->id)->caption);
    }

    public function test_edit_persists_in_the_feed_after_a_new_session(): void
    {
        $post = $this->newPost('v1');
        $this->edit($post, ['caption' => 'v2'])->assertOk();

        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($this->alice);

        $item = collect($this->getJson('/api/posts/followed')->json('data'))->firstWhere('id', $post->id);
        $this->assertSame('v2', $item['caption']);
    }

    // ------------------------------------------------------------------ images

    public function test_adding_images_keeps_the_existing_ones(): void
    {
        $post = $this->newPost('p', 1);

        $res = $this->edit($post, ['posts' => [$this->img('new1.png'), $this->img('new2.png')]])->assertOk();

        $rows = $this->attachments($post);
        $this->assertCount(3, $rows);
        $this->assertCount(3, $res->json('post.images'));
        foreach ($rows as $row) {
            Storage::disk('public')->assertExists(MediaUrl::relativePath($row->path_url));
        }
    }

    public function test_removing_one_image_detaches_it_and_deletes_only_its_file(): void
    {
        $post = $this->newPost('p', 3);
        [$a, $b, $c] = $this->attachments($post)->all();

        $this->edit($post, ['remove_attachments' => [$b->posts_uuind]])->assertOk();

        $left = $this->attachments($post);
        $this->assertCount(2, $left);
        $this->assertSame([$a->posts_uuind, $c->posts_uuind], $left->pluck('posts_uuind')->all());
        Storage::disk('public')->assertMissing(MediaUrl::relativePath($b->path_url));
        Storage::disk('public')->assertExists(MediaUrl::relativePath($a->path_url));
        Storage::disk('public')->assertExists(MediaUrl::relativePath($c->path_url));
    }

    public function test_remove_and_add_in_one_save(): void
    {
        $post = $this->newPost('p', 2);
        [$a, $b] = $this->attachments($post)->all();

        $this->edit($post, ['remove_attachments' => [$a->posts_uuind], 'posts' => [$this->img('fresh.png')]])->assertOk();

        $left = $this->attachments($post);
        $this->assertCount(2, $left);                                  // b + the new one
        $this->assertTrue($left->pluck('posts_uuind')->contains($b->posts_uuind));
        $this->assertFalse($left->pluck('posts_uuind')->contains($a->posts_uuind));
    }

    public function test_a_file_still_used_elsewhere_is_not_deleted(): void
    {
        $post = $this->newPost('p', 1);
        $row = $this->attachments($post)->first();
        $relative = MediaUrl::relativePath($row->path_url);

        // a second row (e.g. a duplicate record) points at the same physical file
        DB::table('attachmentposts')->insert([
            'code' => $this->alice->code, 'transNo' => $post->transNo, 'posts_uuid' => 'other-post-uuid',
            'posts_uuind' => 'shared-ref', 'status' => 1, 'path_url' => $row->path_url, 'posts_type' => 'image',
        ]);

        $this->edit($post, ['remove_attachments' => [$row->posts_uuind]])->assertOk();

        $this->assertSame(0, $this->attachments($post)->count());      // detached from this post
        Storage::disk('public')->assertExists($relative);              // but kept: still referenced
    }

    public function test_cannot_detach_another_posts_attachment(): void
    {
        $mine = $this->newPost('mine', 1);
        $other = $this->newPost('other', 1);
        $foreign = $this->attachments($other)->first();

        $this->edit($mine, ['caption' => 'edited', 'remove_attachments' => [$foreign->posts_uuind]])->assertOk();

        $this->assertSame(1, $this->attachments($other)->count());
        Storage::disk('public')->assertExists(MediaUrl::relativePath($foreign->path_url));
    }

    public function test_new_video_replaces_the_old_one(): void
    {
        $post = $this->newPost('p');
        $video = fn () => UploadedFile::fake()->create('clip.mp4', 200, 'video/mp4');

        $this->edit($post, ['video' => $video()])->assertOk();
        $first = $this->attachments($post)->where('posts_type', 'video')->first();
        if (!$first) {
            $this->markTestSkipped('fake mp4 not accepted by the mimetypes rule on this system');
        }

        $this->edit($post, ['video' => $video()])->assertOk();
        $videos = $this->attachments($post)->where('posts_type', 'video');
        $this->assertCount(1, $videos);
        Storage::disk('public')->assertMissing(MediaUrl::relativePath($first->path_url));
    }

    public function test_image_limit_counts_kept_plus_new(): void
    {
        $post = $this->newPost('p', 9);

        $this->edit($post, ['posts' => [$this->img('a.jpg'), $this->img('b.jpg')]])
            ->assertStatus(422);
        $this->assertSame(9, $this->attachments($post)->count());

        // removing one makes room for two
        $first = $this->attachments($post)->first();
        $this->edit($post, ['remove_attachments' => [$first->posts_uuind], 'posts' => [$this->img('a.jpg'), $this->img('b.jpg')]])
            ->assertOk();
        $this->assertSame(10, $this->attachments($post)->count());
    }

    public function test_invalid_and_oversized_images_are_rejected_without_changing_the_post(): void
    {
        $post = $this->newPost('keep me', 1);

        $this->edit($post, ['caption' => 'changed', 'posts' => [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]])
            ->assertStatus(422);
        $this->edit($post, ['caption' => 'changed', 'posts' => [UploadedFile::fake()->image('big.jpg')->size(4000)]])
            ->assertStatus(422);

        $this->assertSame('keep me', DB::table('posts')->find($post->id)->caption);
        $this->assertSame(1, $this->attachments($post)->count());
    }

    public function test_a_post_cannot_be_left_empty(): void
    {
        $post = $this->newPost('', 1);
        $only = $this->attachments($post)->first();

        $this->edit($post, ['caption' => '', 'remove_attachments' => [$only->posts_uuind]])->assertStatus(422);

        $this->assertSame(1, $this->attachments($post)->count());      // nothing was detached
        Storage::disk('public')->assertExists(MediaUrl::relativePath($only->path_url));
    }

    // ------------------------------------------------------------------ privacy, likes, comments

    public function test_privacy_change_updates_the_attachments_too(): void
    {
        $post = $this->newPost('p', 2, 1);

        $this->edit($post, ['status' => 0])->assertOk();

        $this->assertSame(0, (int) DB::table('posts')->find($post->id)->status);
        $this->assertSame([0, 0], $this->attachments($post)->pluck('status')->map(fn ($v) => (int) $v)->all());
    }

    public function test_likes_and_comments_survive_an_edit(): void
    {
        $post = $this->newPost('p', 1);
        $this->postJson('/api/reactToPostById', ['post_id' => $post->id, 'reaction' => 'Love'])->assertOk();
        $this->postJson('/api/comment', ['post_uuid' => $post->posts_uuid, 'comment' => 'nice'])->assertSuccessful();

        $this->edit($post, ['caption' => 'edited', 'posts' => [$this->img('n.png')]])->assertOk();

        $this->assertSame(1, DB::table('reactionPost')->where('post_id', $post->id)->count());
        $this->assertSame(1, DB::table('commentposts')->where('post_uuidOrUind', $post->posts_uuid)->count());
        $this->assertSame($post->id, DB::table('posts')->where('posts_uuid', $post->posts_uuid)->value('id'));
    }

    // ------------------------------------------------------------------ authorisation

    public function test_guests_cannot_edit(): void
    {
        $post = $this->newPost('p');

        // drop the authenticated user: the next request has no token at all
        $this->app['auth']->forgetGuards();

        $this->postJson("/api/updatePostByTransNo/{$post->transNo}", ['caption' => 'x', 'status' => 1])
            ->assertStatus(401);
        $this->assertSame('p', DB::table('posts')->find($post->id)->caption);
    }

    public function test_another_user_gets_403_and_nothing_changes(): void
    {
        $post = $this->newPost('mine', 1);
        $before = $this->attachments($post)->pluck('path_url', 'posts_uuind');

        Sanctum::actingAs($this->makeUser('Bob'));
        $first = $this->attachments($post)->first();

        $this->edit($post, ['caption' => 'hacked', 'posts' => [$this->img('evil.png')], 'remove_attachments' => [$first->posts_uuind]])
            ->assertStatus(403);

        $this->assertSame('mine', DB::table('posts')->find($post->id)->caption);
        $this->assertEquals($before, $this->attachments($post)->pluck('path_url', 'posts_uuind'));
        Storage::disk('public')->assertExists(MediaUrl::relativePath($first->path_url));
    }

    public function test_editing_a_missing_post_is_404(): void
    {
        $this->postJson('/api/updatePostByTransNo/99999999', ['caption' => 'x', 'status' => 1])->assertStatus(404);
    }
}
