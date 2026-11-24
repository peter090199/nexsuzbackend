<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** POST /api/users/admin : only a master admin can add a DEF-ADMIN, and the role can never be anything else. */
class AddAdminTest extends TestCase
{
    use DatabaseTransactions;

    private array $u = [];

    protected function setUp(): void
    {
        parent::setUp();
        static $next = 899500;
        foreach (['master' => 'DEF-MASTERADMIN', 'admin' => 'DEF-ADMIN', 'seeker' => 'DEF-USERS', 'recruiter' => 'DEF-CLIENT'] as $k => $role) {
            $code = $next += random_int(1, 40);
            $this->u[$k] = User::forceCreate(['fname' => $k, 'lname' => 'T', 'fullname' => "$k T", 'role_code' => $role, 'email' => "$k$code@example.test",
                'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']);
        }
    }

    private function addAdmin(string $as, array $over = [])
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($this->u[$as]);
        return $this->postJson('/api/users/admin', $over + ['fname' => 'New', 'lname' => 'Admin', 'email' => 'na' . bin2hex(random_bytes(4)) . '@example.test',
            'password' => 'Aa1!aaaa', 'password_confirmation' => 'Aa1!aaaa']);
    }

    public function test_a_master_admin_creates_an_active_def_admin(): void
    {
        $email = 'na' . bin2hex(random_bytes(4)) . '@example.test';
        $this->addAdmin('master', ['email' => $email])->assertStatus(201)->assertJsonPath('data.role_code', 'DEF-ADMIN');
        $row = DB::table('users')->where('email', $email)->first();
        $this->assertSame('DEF-ADMIN', $row->role_code);
        $this->assertSame('A', $row->status);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Aa1!aaaa', $row->password));
        $this->assertSame(1, DB::table('resources')->where('code', $row->code)->count());
    }

    public function test_nobody_else_can_use_it_even_directly(): void
    {
        foreach (['admin', 'seeker', 'recruiter'] as $who) {
            $before = DB::table('users')->count();
            $this->addAdmin($who)->assertStatus(403);
            $this->assertSame($before, DB::table('users')->count(), "$who must not create anything");
        }
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/users/admin', [])->assertStatus(401);
    }

    public function test_the_role_cannot_be_chosen(): void
    {
        foreach (['DEF-MASTERADMIN', 'DEF-USERS', 'DEF-CLIENT', 'x'] as $role) {
            $email = 'na' . bin2hex(random_bytes(4)) . '@example.test';
            $this->addAdmin('master', ['email' => $email, 'role_code' => $role])->assertStatus(422)->assertJsonValidationErrors('role_code');
            $this->assertFalse(DB::table('users')->where('email', $email)->exists());
        }
        // naming DEF-ADMIN itself is fine
        $this->addAdmin('master', ['role_code' => 'DEF-ADMIN'])->assertStatus(201);
    }

    public function test_validation_and_duplicate_email(): void
    {
        $this->addAdmin('master', ['password_confirmation' => 'different'])->assertStatus(422)->assertJsonValidationErrors('password');
        $this->addAdmin('master', ['password' => 'short', 'password_confirmation' => 'short'])->assertStatus(422);
        $this->addAdmin('master', ['email' => $this->u['seeker']->email])->assertStatus(422)->assertJsonValidationErrors('email');
        $this->addAdmin('master', ['fname' => ''])->assertStatus(422)->assertJsonValidationErrors('fname');
    }

    // ------------------------------------------------------------------ PATCH /api/users/{code}

    private function edit(string $as, string $target, array $body)
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($this->u[$as]);
        return $this->patchJson('/api/users/' . $this->u[$target]->code, $body);
    }

    private function details(array $over = []): array
    {
        return $over + ['fname' => 'Edited', 'lname' => 'Name', 'email' => 'ed' . bin2hex(random_bytes(4)) . '@example.test', 'contactno' => '9170000000', 'company' => 'Acme'];
    }

    public function test_a_master_admin_edits_details_but_never_role_status_password_or_code(): void
    {
        DB::table('resources')->insert(['code' => $this->u['seeker']->code, 'fname' => 'old', 'lname' => 'old', 'fullname' => 'old old', 'email' => 'old@example.test', 'role_code' => 'DEF-USERS', 'coverphoto' => 'default.jpg']);
        $before = DB::table('users')->where('id', $this->u['seeker']->id)->first();
        $d = $this->details(['role_code' => 'DEF-MASTERADMIN', 'status' => 'I', 'password' => 'Hacked1!aa', 'code' => 1]);

        $this->edit('master', 'seeker', $d)->assertOk()->assertJsonPath('data.role_code', 'DEF-USERS');
        $row = DB::table('users')->where('id', $this->u['seeker']->id)->first();
        $this->assertSame('Edited Name', $row->fullname);
        $this->assertSame($d['email'], $row->email);
        $this->assertSame('Acme', $row->company);
        $this->assertSame('DEF-USERS', $row->role_code);
        $this->assertSame($before->status, $row->status);
        $this->assertSame($before->password, $row->password);
        $this->assertSame($before->code, $row->code);
        $this->assertSame($d['email'], DB::table('resources')->where('code', $before->code)->value('email'));
    }

    public function test_nobody_else_can_edit_an_account(): void
    {
        foreach (['admin', 'seeker', 'recruiter'] as $who) {
            $this->edit($who, 'recruiter' === $who ? 'seeker' : 'recruiter', $this->details())->assertStatus(403);
        }
        $this->assertSame('recruiter T', DB::table('users')->where('id', $this->u['recruiter']->id)->value('fullname'));
    }

    public function test_edit_validation_and_unknown_account(): void
    {
        $this->edit('master', 'seeker', $this->details(['email' => $this->u['recruiter']->email]))->assertStatus(422)->assertJsonValidationErrors('email');
        $this->edit('master', 'seeker', $this->details(['email' => $this->u['seeker']->email]))->assertOk();   // keeping your own email is fine
        $this->edit('master', 'seeker', $this->details(['fname' => '']))->assertStatus(422)->assertJsonValidationErrors('fname');
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($this->u['master']);
        $this->patchJson('/api/users/999999999', $this->details())->assertStatus(404);
    }
}
