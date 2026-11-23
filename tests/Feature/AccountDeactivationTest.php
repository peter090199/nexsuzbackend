<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Settings > Deactivation: a job seeker or recruiter pauses their own account.
 * Nothing is deleted; signing in again brings the account back. Rolled-back transactions.
 */
class AccountDeactivationTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $role): User
    {
        static $next = 970000;
        $code = $next += random_int(1, 40);

        return User::forceCreate([
            'fname' => 'Dee', 'lname' => 'Tester', 'fullname' => 'Dee Tester', 'role_code' => $role,
            'email' => "dee{$code}@example.test", 'password' => bcrypt('right-password'),
            'status' => 'A', 'code' => $code, 'coverphoto' => '',
        ]);
    }

    private function as(User $u): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs(User::find($u->id));
    }

    public function test_it_needs_a_signed_in_user(): void
    {
        $this->postJson('/api/account/deactivate', ['password' => 'x'])->assertStatus(401);
    }

    public function test_a_wrong_password_changes_nothing(): void
    {
        $u = $this->makeUser('DEF-USERS');
        $this->as($u);

        $this->postJson('/api/account/deactivate', ['password' => 'nope'])->assertStatus(422);

        $this->assertSame('A', $u->fresh()->status);
    }

    public function test_the_password_is_required(): void
    {
        $this->as($this->makeUser('DEF-USERS'));

        $this->postJson('/api/account/deactivate', [])->assertStatus(422);
    }

    /** @dataProvider owners */
    public function test_job_seekers_and_recruiters_can_deactivate_their_own_account(string $role): void
    {
        $u = $this->makeUser($role);
        $u->createToken('other device');
        $this->as($u);

        $this->postJson('/api/account/deactivate', ['password' => 'right-password'])->assertOk()->assertJsonPath('success', true);

        $fresh = $u->fresh();
        $this->assertSame('D', $fresh->status);
        $this->assertFalse((bool) $fresh->is_online);
        $this->assertSame(0, $fresh->tokens()->count(), 'every sign-in session is ended');
    }

    public static function owners(): array
    {
        return [['DEF-USERS'], ['DEF-CLIENT']];
    }

    /** @dataProvider admins */
    public function test_administrators_cannot_deactivate_here(string $role): void
    {
        $u = $this->makeUser($role);
        $this->as($u);

        $this->postJson('/api/account/deactivate', ['password' => 'right-password'])->assertStatus(403);

        $this->assertSame('A', $u->fresh()->status);
    }

    public static function admins(): array
    {
        return [['DEF-ADMIN'], ['DEF-MASTERADMIN']];
    }

    public function test_signing_in_again_reactivates_the_account(): void
    {
        $u = $this->makeUser('DEF-USERS');
        $u->forceFill(['status' => 'D'])->save();

        $r = $this->postJson('/api/login', ['email' => $u->email, 'password' => 'right-password'])->assertOk();

        $r->assertJsonPath('success', true)->assertJsonPath('reactivated', true);
        $this->assertSame('A', $u->fresh()->status);
    }

    public function test_a_normal_sign_in_is_not_reported_as_a_reactivation(): void
    {
        $u = $this->makeUser('DEF-CLIENT');

        $this->postJson('/api/login', ['email' => $u->email, 'password' => 'right-password'])
            ->assertOk()->assertJsonPath('reactivated', false);
    }

    public function test_an_email_inactive_account_still_cannot_sign_in(): void
    {
        $u = $this->makeUser('DEF-USERS');
        $u->forceFill(['status' => 'I'])->save();

        $this->postJson('/api/login', ['email' => $u->email, 'password' => 'right-password'])
            ->assertOk()->assertJsonPath('success', false);

        $this->assertSame('I', $u->fresh()->status);
    }

    public function test_a_deactivated_account_leaves_people_search(): void
    {
        $seeker = $this->makeUser('DEF-USERS');
        $u = $this->makeUser('DEF-USERS');
        $u->forceFill(['status' => 'D'])->save();

        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('users')->where('status', 'A')->where('code', $u->code)->count());
        $this->assertSame(1, \Illuminate\Support\Facades\DB::table('users')->where('status', 'A')->where('code', $seeker->code)->count());
    }
}
