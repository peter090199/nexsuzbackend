<?php

namespace Tests\Feature;

use App\Helpers\MediaUrl;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Profile photo and cover photo: upload, replace, remove, validation, ownership, and URL rewriting.
 * Rolled-back transactions + a fake public disk: nothing is left behind.
 */
class ProfilePhotosApiTest extends TestCase
{
    use DatabaseTransactions;

    private User $alice;
    private User $bob;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->alice = $this->makeUser('Alice');
        $this->bob = $this->makeUser('Bob');
        // resources row: the cover photo is mirrored there
        foreach ([$this->alice, $this->bob] as $u) {
            DB::table('resources')->insert(['code' => $u->code, 'email' => $u->email, 'coverphoto' => 'default.jpg', 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->as($this->alice);
    }

    private function makeUser(string $name): User
    {
        static $next = 600000;
        $code = $next += random_int(1, 40);

        return User::forceCreate([
            'fname' => $name, 'lname' => 'Tester', 'fullname' => "$name Tester",
            'email' => strtolower($name) . $code . '@example.test', 'password' => bcrypt('secret-pass'),
            'status' => 'A', 'code' => $code, 'coverphoto' => 'default.jpg',
        ]);
    }

    private function as(User $u): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($u);
    }

    private function img(string $name = 'photo.jpg', int $w = 400, int $h = 400): UploadedFile
    {
        return UploadedFile::fake()->image($name, $w, $h);
    }

    private function profileRow(User $u)
    {
        return DB::table('userprofiles')->where('code', $u->code)->first();
    }

    private function relative(?string $url): string
    {
        return (string) MediaUrl::relativePath($url);
    }

    // ================================================================== profile photo

    public function test_a_profile_photo_is_stored_on_the_public_disk_and_saved_in_the_existing_column(): void
    {
        $res = $this->postJson('/api/uploadProfilePhoto', ['photo' => $this->img()])->assertStatus(201)->assertJson(['success' => true]);

        $url = $res->json('photo_pic');
        $this->assertStringStartsWith('http', $url);
        $this->assertStringContainsString("/storage/uploads/{$this->alice->code}/cvphoto/", $url);
        $this->assertSame($url, MediaUrl::normalize($this->profileRow($this->alice)->photo_pic));
        Storage::disk('public')->assertExists($this->relative($url));
    }

    public function test_changing_the_photo_never_touches_the_other_profile_fields(): void
    {
        DB::table('userprofiles')->insert([
            'code' => $this->alice->code, 'transNo' => 99999, 'contact_no' => '09171234567', 'contact_visibility' => 1,
            'email' => 'alice@example.test', 'email_visibility' => 1, 'summary' => 'My summary', 'home_country' => 'PH',
            'home_state' => 'Cebu', 'current_location' => 'Manila', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->postJson('/api/uploadProfilePhoto', ['photo' => $this->img()])->assertStatus(201);

        $row = $this->profileRow($this->alice);
        $this->assertSame('09171234567', $row->contact_no);
        $this->assertSame('My summary', $row->summary);
        $this->assertSame('Cebu', $row->home_state);
        $this->assertSame('Manila', $row->current_location);
        $this->assertSame(1, (int) $row->contact_visibility);
        $this->assertNotNull($row->photo_pic);
    }

    public function test_a_profile_row_is_created_when_the_user_has_none_yet(): void
    {
        $this->assertNull($this->profileRow($this->alice));

        $this->postJson('/api/uploadProfilePhoto', ['photo' => $this->img()])->assertStatus(201);

        $this->assertNotNull($this->profileRow($this->alice)->photo_pic);
        $this->assertSame(1, DB::table('userprofiles')->where('code', $this->alice->code)->count());
    }

    public function test_replacing_the_photo_deletes_the_old_file(): void
    {
        $first = $this->postJson('/api/uploadProfilePhoto', ['photo' => $this->img('a.jpg')])->json('photo_pic');
        Storage::disk('public')->assertExists($this->relative($first));

        $second = $this->postJson('/api/uploadProfilePhoto', ['photo' => $this->img('b.png')])->json('photo_pic');

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($this->relative($first));
        Storage::disk('public')->assertExists($this->relative($second));
    }

    public function test_removing_the_photo_clears_the_column_and_deletes_the_file(): void
    {
        $url = $this->postJson('/api/uploadProfilePhoto', ['photo' => $this->img()])->json('photo_pic');

        $this->postJson('/api/removeProfilePhoto')->assertOk()->assertJson(['success' => true, 'photo_pic' => null]);

        $this->assertNull($this->profileRow($this->alice)->photo_pic);
        Storage::disk('public')->assertMissing($this->relative($url));
    }

    public function test_bad_photos_are_rejected_with_a_readable_message(): void
    {
        $pdf = $this->postJson('/api/uploadProfilePhoto', ['photo' => UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf')])->assertStatus(422);
        $this->assertStringContainsString('JPG, PNG, GIF or WEBP', $pdf->json('message'));

        $big = $this->postJson('/api/uploadProfilePhoto', ['photo' => $this->img('big.jpg')->size(6000)])->assertStatus(422);
        $this->assertStringContainsString('5 MB', $big->json('message'));

        $this->postJson('/api/uploadProfilePhoto', [])->assertStatus(422);
        $this->assertNull($this->profileRow($this->alice));
    }

    public function test_webp_is_accepted(): void
    {
        if (!function_exists('imagewebp')) {
            $this->markTestSkipped('GD without WEBP support');
        }
        $this->postJson('/api/uploadProfilePhoto', ['photo' => $this->img('p.webp')])->assertStatus(201);
    }

    // ================================================================== cover photo

    public function test_a_cover_photo_is_stored_and_mirrored_in_both_tables(): void
    {
        $res = $this->postJson('/api/uploadCoverPhoto', ['coverphoto' => $this->img('c.jpg', 1500, 500)])->assertStatus(201);
        $url = $res->json('coverphoto');

        $this->assertStringContainsString("/storage/uploads/{$this->alice->code}/coverphoto/", $url);
        Storage::disk('public')->assertExists($this->relative($url));
        $this->assertSame($url, MediaUrl::normalize(DB::table('users')->where('code', $this->alice->code)->value('coverphoto')));
        $this->assertSame($url, MediaUrl::normalize(DB::table('resources')->where('code', $this->alice->code)->value('coverphoto')));
    }

    public function test_replacing_and_removing_the_cover_deletes_the_files(): void
    {
        $first = $this->postJson('/api/uploadCoverPhoto', ['coverphoto' => $this->img('a.jpg')])->json('coverphoto');
        $second = $this->postJson('/api/uploadCoverPhoto', ['coverphoto' => $this->img('b.jpg')])->json('coverphoto');
        Storage::disk('public')->assertMissing($this->relative($first));
        Storage::disk('public')->assertExists($this->relative($second));

        $this->postJson('/api/removeCoverPhoto')->assertOk()->assertJson(['coverphoto' => null]);
        Storage::disk('public')->assertMissing($this->relative($second));
        $this->assertSame('default.jpg', DB::table('users')->where('code', $this->alice->code)->value('coverphoto'));
        $this->assertSame('default.jpg', DB::table('resources')->where('code', $this->alice->code)->value('coverphoto'));
    }

    public function test_bad_covers_are_rejected(): void
    {
        $this->postJson('/api/uploadCoverPhoto', ['coverphoto' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])->assertStatus(422);
        $this->postJson('/api/uploadCoverPhoto', ['coverphoto' => $this->img('big.jpg')->size(6000)])->assertStatus(422);
        $this->assertSame('default.jpg', DB::table('users')->where('code', $this->alice->code)->value('coverphoto'));
    }

    // ================================================================== ownership and authentication

    public function test_a_user_can_only_change_their_own_photos_whatever_the_request_says(): void
    {
        $this->postJson('/api/uploadProfilePhoto', ['photo' => $this->img(), 'code' => $this->alice->code])->assertStatus(201);
        $alicesPhoto = $this->profileRow($this->alice)->photo_pic;

        $this->as($this->bob);
        $this->postJson('/api/uploadProfilePhoto', ['photo' => $this->img(), 'code' => $this->alice->code, 'user_code' => $this->alice->code])->assertStatus(201);
        $this->postJson('/api/uploadCoverPhoto', ['coverphoto' => $this->img(), 'code' => $this->alice->code])->assertStatus(201);
        $this->postJson('/api/removeProfilePhoto', ['code' => $this->alice->code])->assertOk();

        $this->assertSame($alicesPhoto, $this->profileRow($this->alice)->photo_pic);                          // untouched
        $this->assertSame('default.jpg', DB::table('users')->where('code', $this->alice->code)->value('coverphoto'));
        Storage::disk('public')->assertExists($this->relative($alicesPhoto));
    }

    public function test_replacing_a_photo_never_deletes_files_that_belong_to_someone_else(): void
    {
        // Bob's row (wrongly) points at a file inside ALICE's folder: replacing Bob's photo must not delete it
        $alicesFile = "uploads/{$this->alice->code}/cvphoto/abc/keep.jpg";
        Storage::disk('public')->put($alicesFile, 'alice');
        DB::table('userprofiles')->insert([
            'code' => $this->bob->code, 'transNo' => 99998, 'contact_visibility' => 0, 'email_visibility' => 0,
            'photo_pic' => url('storage/' . $alicesFile), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->as($this->bob);
        $this->postJson('/api/uploadProfilePhoto', ['photo' => $this->img()])->assertStatus(201);

        Storage::disk('public')->assertExists($alicesFile);
    }

    public function test_guests_cannot_upload_or_remove(): void
    {
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/uploadProfilePhoto', ['photo' => $this->img()])->assertStatus(401);
        $this->postJson('/api/uploadCoverPhoto', ['coverphoto' => $this->img()])->assertStatus(401);
        $this->postJson('/api/removeProfilePhoto')->assertStatus(401);
        $this->postJson('/api/removeCoverPhoto')->assertStatus(401);
    }

    // ================================================================== URL rewriting (all pages)

    public function test_legacy_urls_in_any_api_response_point_at_this_server(): void
    {
        Route::middleware(['api', 'auth:sanctum'])->get('/api/_media_probe', fn () => response()->json([
            'photo_pic'  => 'https://api.nexsuz.com/storage/uploads/702/cvphoto/u1/1.jpg',
            'profile_pic' => 'https://exploredition.com/storage/app/public/uploads/703/cvphoto/u2/2.jpg',
            'coverphoto' => 'https://exploredition.com/storage/app/public/uploads/702/coverphoto/u3/3.png',
            'path_url'   => '/storage/app/public/uploads/posts/702/u/4.jpg',
            'attachment_url' => 'https://x.test/storage/uploads/comments/702/5.png',
            'default'    => 'https://exploredition.com/storage/app/public/uploads/DEFAULTPROFILE/DEFAULTPROFILE.png',
            'nested'     => [['photo_pic' => 'https://api.nexsuz.com/storage/uploads/1/cvphoto/u/a.jpg']],
        ]));

        $d = $this->getJson('/api/_media_probe')->assertOk()->json();

        $this->assertSame(url('storage/uploads/702/cvphoto/u1/1.jpg'), $d['photo_pic']);
        $this->assertSame(url('storage/uploads/703/cvphoto/u2/2.jpg'), $d['profile_pic']);
        $this->assertSame(url('storage/uploads/702/coverphoto/u3/3.png'), $d['coverphoto']);
        $this->assertSame(url('storage/uploads/posts/702/u/4.jpg'), $d['path_url']);
        $this->assertSame(url('storage/uploads/comments/702/5.png'), $d['attachment_url']);
        $this->assertSame(url('storage/uploads/DEFAULTPROFILE/DEFAULTPROFILE.png'), $d['default']);
        $this->assertSame(url('storage/uploads/1/cvphoto/u/a.jpg'), $d['nested'][0]['photo_pic']);
    }

    public function test_the_rewrite_leaves_everything_else_exactly_as_it_was(): void
    {
        Route::middleware(['api', 'auth:sanctum'])->get('/api/_media_probe2', fn () => response()->json([
            'empty_object' => new \stdClass(),
            'empty_list'   => [],
            'name'         => 'Pedro',
            'count'        => 7,
            'ok'           => true,
            'nothing'      => null,
            'link'         => 'https://example.test/uploads/readme.txt',      // "uploads" but not one of our media paths
            'cdn'          => 'https://cdn.example.test/images/photo.png',
            'obj'          => ['photo_pic' => 'https://api.nexsuz.com/storage/uploads/9/cvphoto/u/a.jpg'],
        ]));

        $raw = $this->get('/api/_media_probe2', ['Accept' => 'application/json'])->assertOk()->getContent();
        $d = json_decode($raw, false);

        $this->assertInstanceOf(\stdClass::class, $d->empty_object);          // {} stays an object (not turned into [])
        $this->assertSame([], $d->empty_list);
        $this->assertSame('Pedro', $d->name);
        $this->assertSame(7, $d->count);
        $this->assertTrue($d->ok);
        $this->assertNull($d->nothing);
        $this->assertSame('https://example.test/uploads/readme.txt', $d->link);
        $this->assertSame('https://cdn.example.test/images/photo.png', $d->cdn);
        $this->assertSame(url('storage/uploads/9/cvphoto/u/a.jpg'), $d->obj->photo_pic);
    }

    public function test_a_missing_cover_is_reported_as_null_instead_of_the_bare_name_default_jpg(): void
    {
        Route::middleware(['api', 'auth:sanctum'])->get('/api/_media_probe3', fn () => response()->json([
            'data' => ['coverphoto' => 'default.jpg'], 'other' => ['coverphoto' => ''], 'real' => ['coverphoto' => 'https://exploredition.com/storage/app/public/uploads/5/coverphoto/u/c.jpg'],
        ]));

        $d = $this->getJson('/api/_media_probe3')->json();

        $this->assertNull($d['data']['coverphoto']);
        $this->assertNull($d['other']['coverphoto']);
        $this->assertSame(url('storage/uploads/5/coverphoto/u/c.jpg'), $d['real']['coverphoto']);
    }

    public function test_the_default_avatar_exists_so_that_rewritten_default_urls_resolve(): void
    {
        $this->assertFileExists(storage_path('app/public/uploads/DEFAULTPROFILE/DEFAULTPROFILE.png'));
    }
}
