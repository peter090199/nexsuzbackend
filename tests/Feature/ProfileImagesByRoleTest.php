<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Cover photo and profile photo uploads work for every account type, admins and recruiters included (they have no CV). */
class ProfileImagesByRoleTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function user(string $role): User
    {
        static $next = 899800;
        $code = $next += random_int(1, 20);
        return User::forceCreate(['fname' => 'p', 'lname' => 'T', 'fullname' => 'p T', 'role_code' => $role, 'email' => "p$code@example.test",
            'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => 'default.jpg']);
    }

    private function as(User $u): void { $this->app['auth']->forgetGuards(); Sanctum::actingAs($u); }

    public function test_every_role_can_upload_and_remove_a_cover_photo_and_a_profile_photo(): void
    {
        foreach (['DEF-ADMIN', 'DEF-MASTERADMIN', 'DEF-CLIENT', 'DEF-USERS'] as $role) {
            $u = $this->user($role); $this->as($u);

            $this->post('/api/uploadCoverPhoto', ['coverphoto' => UploadedFile::fake()->image('c.jpg', 600, 200)], ['Accept' => 'application/json'])
                ->assertStatus(201)->assertJsonPath('success', true);
            $this->assertStringContainsString("uploads/{$u->code}/coverphoto/", (string) DB::table('users')->where('code', $u->code)->value('coverphoto'));

            $this->post('/api/uploadProfilePhoto', ['photo' => UploadedFile::fake()->image('p.png', 300, 300)], ['Accept' => 'application/json'])
                ->assertStatus(201)->assertJsonPath('success', true);
            $this->assertStringContainsString("uploads/{$u->code}/cvphoto/", (string) DB::table('userprofiles')->where('code', $u->code)->value('photo_pic'));

            $this->postJson('/api/removeProfilePhoto')->assertOk();
            $this->postJson('/api/removeCoverPhoto')->assertOk();
        }
    }

    public function test_bad_files_are_refused_with_a_message(): void
    {
        $this->as($this->user('DEF-ADMIN'));
        $this->post('/api/uploadCoverPhoto', ['coverphoto' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonPath('success', false);
        $this->post('/api/uploadProfilePhoto', ['photo' => UploadedFile::fake()->image('big.jpg')->size(6000)], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }
}
