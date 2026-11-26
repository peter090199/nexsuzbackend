<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** The shared CV link: no sign in, signature required, privacy switches applied. Synthetic accounts; rows roll back. */
class SharedCvTest extends TestCase
{
    use DatabaseTransactions;

    private function account(string $role = 'DEF-USERS', array $profile = []): User
    {
        static $next = 897000;
        $code = $next += random_int(1, 40);
        $u = User::forceCreate(['fname' => 'Share', 'lname' => 'Tester', 'fullname' => 'Share Tester', 'role_code' => $role,
            'email' => "share$code@example.test", 'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']);
        DB::table('resources')->insert(['code' => $code, 'fname' => 'Share', 'lname' => 'Tester', 'fullname' => 'Share Tester',
            'email' => $u->email, 'profession' => 'Engineer', 'role_code' => $role]);
        DB::table('userprofiles')->insert($profile + ['code' => $code, 'transNo' => random_int(100000, 999999), 'email' => $u->email,
            'contact_no' => '+639171234567', 'summary' => 'Backend developer', 'contact_visibility' => 0, 'email_visibility' => 0]);
        DB::table('userskills')->insert(['code' => $code, 'transNo' => 1, 'skills' => 'PHP']);
        return $u;
    }

    /** The path part of the owner's link, e.g. "897012/abc..." ; the visitor is then signed out. */
    private function linkFor(User $u): string
    {
        Sanctum::actingAs($u);
        $url = $this->getJson('/api/cv/share-link')->assertOk()->json('url');
        $this->app['auth']->forgetGuards();
        $this->assertStringStartsWith(rtrim(config('app.frontend_url'), '/') . '/cv/' . $u->code . '/', $url);

        return substr($url, strpos($url, '/cv/') + 4);
    }

    public function test_only_a_signed_in_job_seeker_can_make_a_link(): void
    {
        $this->getJson('/api/cv/share-link')->assertStatus(401);
        Sanctum::actingAs($this->account('DEF-CLIENT'));
        $this->getJson('/api/cv/share-link')->assertStatus(403);
    }

    public function test_the_link_opens_the_owners_cv_without_signing_in(): void
    {
        $u = $this->account();
        $this->getJson('/api/cv/shared/' . $this->linkFor($u))->assertOk()
            ->assertJsonPath('message.0.code', $u->code)
            ->assertJsonPath('message.0.email', $u->email)
            ->assertJsonPath('message.0.contact_no', '+639171234567')
            ->assertJsonPath('message.0.summary', 'Backend developer')
            ->assertJsonPath('message.0.lines.skills.0.skills', 'PHP');
    }

    public function test_hidden_email_and_phone_are_never_sent(): void
    {
        $u = $this->account('DEF-USERS', ['contact_visibility' => 1, 'email_visibility' => 1]);
        $res = $this->getJson('/api/cv/shared/' . $this->linkFor($u))->assertOk()
            ->assertJsonPath('message.0.email', null)
            ->assertJsonPath('message.0.contact_no', null);
        $this->assertStringNotContainsString($u->email, $res->getContent());
        $this->assertStringNotContainsString('639171234567', $res->getContent());
    }

    public function test_a_signature_does_not_work_for_another_account_or_when_altered(): void
    {
        $a = $this->account();
        $b = $this->account();
        [, $signature] = explode('/', $this->linkFor($a));
        $this->getJson("/api/cv/shared/{$b->code}/{$signature}")->assertStatus(404);
        $this->getJson("/api/cv/shared/{$a->code}/{$signature}x")->assertStatus(404);
        $this->getJson("/api/cv/shared/{$b->code}/{$signature}/photo")->assertStatus(404);
    }

    public function test_the_link_stops_working_when_the_account_is_not_active(): void
    {
        $u = $this->account();
        $link = $this->linkFor($u);
        $u->forceFill(['status' => 'D'])->save();
        $this->getJson('/api/cv/shared/' . $link)->assertStatus(404);
    }

    public function test_the_photo_is_served_only_from_the_owners_own_photo_folder(): void
    {
        Storage::fake('public');
        $u = $this->account();
        $link = $this->linkFor($u);
        $this->getJson("/api/cv/shared/{$link}/photo")->assertStatus(404);   // no photo

        $own = "uploads/{$u->code}/cvphoto/abc/1.jpg";
        Storage::disk('public')->put($own, 'jpg');
        DB::table('userprofiles')->where('code', $u->code)->update(['photo_pic' => url('storage/' . $own)]);
        $this->get("/api/cv/shared/{$link}/photo")->assertOk();

        // binary responses are corrupted by any text printed before them (whitespace before <?php in an included file)
        $this->expectOutputString('');
        foreach ([base_path('routes/api.php'), base_path('routes/web.php')] as $file) {
            $this->assertStringStartsWith('<?php', file_get_contents($file), $file);
        }

        $other = 'uploads/1/cvphoto/abc/1.jpg';
        Storage::disk('public')->put($other, 'jpg');
        DB::table('userprofiles')->where('code', $u->code)->update(['photo_pic' => url('storage/' . $other)]);
        $this->getJson("/api/cv/shared/{$link}/photo")->assertStatus(404);
    }
}
