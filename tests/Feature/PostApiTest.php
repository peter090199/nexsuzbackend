<?php

namespace Tests\Feature;

use App\Helpers\MediaUrl;
use App\Models\Attachmentpost;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Post creation, media storage, feed, reactions, comments and ownership.
 *
 * Runs against the configured database inside a transaction that is rolled
 * back after every test, and uses a fake "public" disk, so nothing is left
 * behind (no rows, no files).
 */
class PostApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function makeUser(string $name): User
    {
        $code = random_int(800000, 899999);

        return User::forceCreate([
            'fname'      => $name,
            'lname'      => 'Tester',
            'fullname'   => "$name Tester",
            'email'      => strtolower($name) . $code . '@example.test',
            'password'   => bcrypt('secret-pass'),
            'status'     => 'A',
            'code'       => $code,
            'coverphoto' => '',
        ]);
    }

    private function actAs(User $user): void
    {
        Sanctum::actingAs($user);
    }

    private function createPost(array $extra = [])
    {
        return $this->postJson('/api/savePost', array_merge(['caption' => 'Hello NEXSUZ', 'status' => 1], $extra));
    }

    // ------------------------------------------------------------------ creation

    public function test_guest_cannot_create_a_post(): void
    {
        $this->postJson('/api/savePost', ['caption' => 'x', 'status' => 1])->assertStatus(401);
    }

    public function test_text_only_post_is_saved_with_its_author(): void
    {
        $user = $this->makeUser('Alice');
        $this->actAs($user);

        $this->createPost()->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('posts', ['code' => $user->code, 'caption' => 'Hello NEXSUZ', 'status' => 1]);
        $this->assertSame(0, DB::table('attachmentposts')->where('code', $user->code)->count());
    }

    public function test_empty_post_is_rejected(): void
    {
        $this->actAs($this->makeUser('Alice'));

        $this->createPost(['caption' => ''])->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_single_image_is_stored_with_a_safe_name_and_a_working_url(): void
    {
        $user = $this->makeUser('Alice');
        $this->actAs($user);

        $this->createPost(['posts' => [UploadedFile::fake()->image('my photo (1).jpg', 600, 400)]])
            ->assertOk()->assertJson(['success' => true]);

        $row = DB::table('attachmentposts')->where('code', $user->code)->first();
        $this->assertNotNull($row);
        $this->assertSame('image', $row->posts_type);

        // served through the public/storage link: /storage/uploads/posts/..., never /storage/app/public/...
        $this->assertStringContainsString("/storage/uploads/posts/{$user->code}/", $row->path_url);
        $this->assertStringNotContainsString('storage/app/public', $row->path_url);
        // the client's filename is never used
        $this->assertStringNotContainsString('my photo', $row->path_url);

        Storage::disk('public')->assertExists(MediaUrl::relativePath($row->path_url));
    }

    public function test_webp_image_is_accepted_and_stored(): void
    {
        if (!function_exists('imagewebp')) {
            $this->markTestSkipped('GD without WEBP support cannot build a WEBP test image.');
        }
        $user = $this->makeUser('Alice');
        $this->actAs($user);

        $this->createPost(['posts' => [UploadedFile::fake()->image('photo.webp', 300, 200)]])
            ->assertOk()->assertJson(['success' => true]);

        $row = DB::table('attachmentposts')->where('code', $user->code)->first();
        $this->assertStringEndsWith('.webp', $row->path_url);
        Storage::disk('public')->assertExists(MediaUrl::relativePath($row->path_url));
    }

    public function test_multiple_images_are_stored(): void
    {
        $user = $this->makeUser('Alice');
        $this->actAs($user);

        $files = [
            UploadedFile::fake()->image('a.jpg'),
            UploadedFile::fake()->image('b.png'),
            UploadedFile::fake()->image('c.gif'),
        ];
        $this->createPost(['posts' => $files])->assertOk();

        $rows = DB::table('attachmentposts')->where('code', $user->code)->get();
        $this->assertCount(3, $rows);
        foreach ($rows as $row) {
            Storage::disk('public')->assertExists(MediaUrl::relativePath($row->path_url));
        }
        $this->assertCount(3, $rows->pluck('path_url')->unique());
    }

    public function test_non_image_upload_is_rejected_with_a_readable_message(): void
    {
        $this->actAs($this->makeUser('Alice'));

        $res = $this->createPost(['posts' => [UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf')]]);

        $res->assertStatus(422);
        $this->assertStringContainsString('JPG', $res->json('message'));
        $this->assertSame(0, DB::table('posts')->where('caption', 'Hello NEXSUZ')->count());
    }

    public function test_oversized_image_is_rejected(): void
    {
        $this->actAs($this->makeUser('Alice'));

        $res = $this->createPost(['posts' => [UploadedFile::fake()->image('big.jpg')->size(4000)]]);

        $res->assertStatus(422);
        $this->assertStringContainsString('3 MB', $res->json('message'));
    }

    public function test_too_many_images_are_rejected(): void
    {
        $this->actAs($this->makeUser('Alice'));

        $files = [];
        for ($i = 0; $i < 11; $i++) {
            $files[] = UploadedFile::fake()->image("p$i.jpg", 50, 50);
        }

        $this->createPost(['posts' => $files])->assertStatus(422);
    }

    // ------------------------------------------------------------------ feed

    public function test_feed_returns_own_posts_newest_first_with_working_urls_and_flags(): void
    {
        $user = $this->makeUser('Alice');
        $this->actAs($user);

        $this->createPost(['caption' => 'first post']);
        $this->createPost([
            'caption' => 'second post',
            'posts'   => [UploadedFile::fake()->image('x.jpg')],
        ]);
        // make ordering deterministic (both rows are created within the same second)
        DB::table('posts')->where('caption', 'first post')->update(['created_at' => now()->subMinute()]);

        $res = $this->getJson('/api/posts/followed')->assertOk()->assertJson(['success' => true]);
        $data = collect($res->json('data'))->whereIn('caption', ['first post', 'second post'])->values();

        $this->assertSame(['second post', 'first post'], $data->pluck('caption')->all());

        $second = $data[0];
        $this->assertTrue($second['is_owner']);
        $this->assertCount(1, $second['images']);
        $this->assertStringContainsString('/storage/uploads/posts/', $second['images'][0]['path_url']);
        $this->assertStringStartsWith('http', $second['images'][0]['path_url']);
        $this->assertNotEmpty($second['transNo']);
    }

    public function test_feed_survives_a_new_session(): void
    {
        $user = $this->makeUser('Alice');
        $this->actAs($user);
        $this->createPost(['caption' => 'persisted']);

        // "log in again": a brand-new authenticated request must still see it
        $this->app['auth']->forgetGuards();
        $this->actAs($user);

        $captions = collect($this->getJson('/api/posts/followed')->json('data'))->pluck('caption');
        $this->assertTrue($captions->contains('persisted'));
    }

    public function test_other_users_posts_are_not_owned_by_the_viewer(): void
    {
        $alice = $this->makeUser('Alice');
        $bob = $this->makeUser('Bob');

        $this->actAs($alice);
        $this->createPost(['caption' => 'alice public post']);

        // Bob is not connected to Alice, so Alice's post is not in his feed
        $this->actAs($bob);
        $captions = collect($this->getJson('/api/posts/followed')->json('data'))->pluck('caption');
        $this->assertFalse($captions->contains('alice public post'));
    }

    public function test_legacy_media_urls_are_normalised(): void
    {
        $this->assertSame(
            url('storage/uploads/posts/702/u/f.jpg'),
            MediaUrl::normalize('/storage/app/public/uploads/posts/702/u/f.jpg')
        );
        $this->assertSame(
            url('storage/uploads/posts/702/u/f.jpg'),
            MediaUrl::normalize('https://exploredition.com/storage/app/public/uploads/posts/702/u/f.jpg')
        );
        $this->assertSame('https://cdn.example.test/x.png', MediaUrl::normalize('https://cdn.example.test/x.png'));

        $a = new Attachmentpost();
        $a->setRawAttributes(['path_url' => '/storage/app/public/uploads/posts/1/u/f.jpg']);
        $this->assertSame(url('storage/uploads/posts/1/u/f.jpg'), $a->path_url);
    }

    // ------------------------------------------------------------------ ownership

    public function test_only_the_owner_can_edit_or_delete_a_post(): void
    {
        $alice = $this->makeUser('Alice');
        $bob = $this->makeUser('Bob');

        $this->actAs($alice);
        $this->createPost(['caption' => 'mine', 'posts' => [UploadedFile::fake()->image('x.jpg')]]);
        $post = DB::table('posts')->where('code', $alice->code)->first();

        $this->actAs($bob);
        $this->postJson("/api/updatePostByTransNo/{$post->transNo}", ['caption' => 'hacked', 'status' => 1])
            ->assertStatus(403);
        $this->deleteJson("/api/post/{$post->posts_uuid}")->assertStatus(403);

        $this->assertDatabaseHas('posts', ['posts_uuid' => $post->posts_uuid, 'caption' => 'mine']);
        $this->assertSame(1, DB::table('attachmentposts')->where('posts_uuid', $post->posts_uuid)->count());
    }

    public function test_caption_only_edit_keeps_existing_images(): void
    {
        $alice = $this->makeUser('Alice');
        $this->actAs($alice);
        $this->createPost(['caption' => 'before', 'posts' => [UploadedFile::fake()->image('x.jpg')]]);
        $post = DB::table('posts')->where('code', $alice->code)->first();

        $this->postJson("/api/updatePostByTransNo/{$post->transNo}", ['caption' => 'after', 'status' => 1])
            ->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('posts', ['posts_uuid' => $post->posts_uuid, 'caption' => 'after']);
        $this->assertSame(1, DB::table('attachmentposts')->where('posts_uuid', $post->posts_uuid)->count());
    }

    public function test_owner_can_delete_a_post_and_its_files(): void
    {
        $alice = $this->makeUser('Alice');
        $this->actAs($alice);
        $this->createPost(['caption' => 'bye', 'posts' => [UploadedFile::fake()->image('x.jpg')]]);
        $post = DB::table('posts')->where('code', $alice->code)->first();
        $path = MediaUrl::relativePath(DB::table('attachmentposts')->where('posts_uuid', $post->posts_uuid)->value('path_url'));
        Storage::disk('public')->assertExists($path);

        $this->deleteJson("/api/post/{$post->posts_uuid}")->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseMissing('posts', ['posts_uuid' => $post->posts_uuid]);
        Storage::disk('public')->assertMissing($path);
    }

    // ------------------------------------------------------------------ reactions

    public function test_like_is_saved_once_per_user_and_can_be_removed(): void
    {
        $alice = $this->makeUser('Alice');
        $bob = $this->makeUser('Bob');

        $this->actAs($alice);
        $this->createPost(['caption' => 'like me']);
        $postId = DB::table('posts')->where('code', $alice->code)->value('id');

        $this->actAs($bob);
        $this->postJson('/api/reactToPostById', ['post_id' => $postId, 'reaction' => 'Like'])->assertOk();
        $this->postJson('/api/reactToPostById', ['post_id' => $postId, 'reaction' => 'Like'])->assertOk(); // duplicate click
        $this->postJson('/api/reactToPostById', ['post_id' => $postId, 'reaction' => 'Love'])->assertOk(); // change

        $rows = DB::table('reactionPost')->where('post_id', $postId)->where('code', $bob->code)->get();
        $this->assertCount(1, $rows);                       // never a duplicate
        $this->assertSame('Love', $rows[0]->reaction);

        $this->postJson('/api/removeReactionByPostId', ['post_id' => $postId])->assertOk();
        $this->assertSame(0, DB::table('reactionPost')->where('post_id', $postId)->where('code', $bob->code)->count());
    }

    public function test_reaction_to_a_missing_post_is_a_404(): void
    {
        $this->actAs($this->makeUser('Alice'));
        $this->postJson('/api/reactToPostById', ['post_id' => 99999999, 'reaction' => 'Like'])->assertStatus(404);
    }

    public function test_my_reaction_and_comment_count_come_with_the_feed(): void
    {
        $alice = $this->makeUser('Alice');
        $this->actAs($alice);
        $this->createPost(['caption' => 'with extras']);
        $post = DB::table('posts')->where('code', $alice->code)->first();

        $this->postJson('/api/reactToPostById', ['post_id' => $post->id, 'reaction' => 'Wow'])->assertOk();
        $this->postJson('/api/comment', ['post_uuid' => $post->posts_uuid, 'comment' => 'nice one'])->assertSuccessful();

        $item = collect($this->getJson('/api/posts/followed')->json('data'))->firstWhere('caption', 'with extras');
        $this->assertSame('Wow', $item['my_reaction']);
        $this->assertSame(1, $item['comments_count']);
    }
}
