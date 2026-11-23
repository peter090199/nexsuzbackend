<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Settings > Account Information: DEF-ADMIN and DEF-MASTERADMIN edit their own details (everything except the role).
 * Everyone else gets 403. Synthetic accounts only; the resources rows written here are removed again.
 */
class AdminAccountDetailsTest extends TestCase
{
    use DatabaseTransactions;

    /** @var int[] */
    private array $codes = [];

    private function makeUser(string $role): User
    {
        static $next = 950000;
        $code = $next += random_int(1, 40);
        $this->codes[] = $code;

        $u = User::forceCreate([
            'fname' => 'Ada', 'lname' => 'Admin', 'fullname' => 'Ada Admin', 'role_code' => $role,
            'email' => "ada{$code}@example.test", 'password' => bcrypt('right-password'),
            'status' => 'A', 'code' => $code, 'coverphoto' => '',
        ]);
        DB::table('resources')->insert([
            'code' => $code, 'fname' => 'Ada', 'lname' => 'Admin', 'fullname' => 'Ada Admin', 'email' => $u->email,
            'role_code' => $role, 'profession' => 'old job', 'company' => 'Old Co', 'companywebsite' => 'old.com',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $u;
    }

    protected function tearDown(): void
    {
        DB::table('resources')->whereIn('code', $this->codes)->delete();
        parent::tearDown();
    }

    private function as(User $u): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs(User::find($u->id));
    }

    private function body(User $u, array $extra = []): array
    {
        return $extra + [
            'fname' => 'Grace', 'lname' => 'Hopper', 'mname' => '', 'email' => $u->email,
            'profession' => 'Engineer', 'company' => 'New Co', 'companywebsite' => 'new.com',
        ];
    }

    public static function admins(): array
    {
        return [['DEF-ADMIN'], ['DEF-MASTERADMIN']];
    }

    #[DataProvider('admins')]
    public function test_an_admin_can_edit_every_detail_except_the_role(string $role): void
    {
        $u = $this->makeUser($role);
        $this->as($u);

        $this->putJson('/api/account/details', $this->body($u, ['role_code' => 'DEF-USERS', 'code' => 1]))
            ->assertOk()->assertJsonPath('success', true);

        $users = DB::table('users')->where('id', $u->id)->first();
        $res = DB::table('resources')->where('code', $u->code)->first();
        $this->assertSame('Grace Hopper', $users->fullname);
        $this->assertSame('Grace', $res->fname);
        $this->assertSame('Engineer', $res->profession);
        $this->assertSame('New Co', $res->company);
        $this->assertSame('new.com', $res->companywebsite);
        // the role and the account code are never editable
        $this->assertSame($role, $users->role_code);
        $this->assertSame($role, $res->role_code);
        $this->assertSame($u->code, (int) $users->code);
    }

    #[DataProvider('admins')]
    public function test_changing_the_email_needs_the_current_password(string $role): void
    {
        $u = $this->makeUser($role);
        $this->as($u);
        $new = "changed{$u->code}@example.test";

        $this->putJson('/api/account/details', $this->body($u, ['email' => $new]))->assertStatus(422);
        $this->putJson('/api/account/details', $this->body($u, ['email' => $new, 'current_password' => 'wrong']))->assertStatus(422);
        $this->assertNotSame($new, User::find($u->id)->email);

        $this->putJson('/api/account/details', $this->body($u, ['email' => $new, 'current_password' => 'right-password']))->assertOk();

        $this->assertSame($new, User::find($u->id)->email);
        $this->assertSame($new, DB::table('resources')->where('code', $u->code)->value('email'));
    }

    public function test_the_email_must_not_belong_to_someone_else(): void
    {
        $a = $this->makeUser('DEF-ADMIN');
        $b = $this->makeUser('DEF-MASTERADMIN');
        $this->as($a);

        $this->putJson('/api/account/details', $this->body($a, ['email' => $b->email, 'current_password' => 'right-password']))
            ->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_names_and_a_valid_email_are_required(): void
    {
        $u = $this->makeUser('DEF-ADMIN');
        $this->as($u);

        $this->putJson('/api/account/details', $this->body($u, ['fname' => '']))->assertStatus(422)->assertJsonValidationErrors('fname');
        $this->putJson('/api/account/details', $this->body($u, ['email' => 'not-an-email']))->assertStatus(422)->assertJsonValidationErrors('email');
    }

    /** @return array<int, array{string}> */
    public static function others(): array
    {
        return [['DEF-USERS'], ['DEF-CLIENT']];
    }

    #[DataProvider('others')]
    public function test_job_seekers_and_recruiters_cannot_use_it(string $role): void
    {
        $u = $this->makeUser($role);
        $this->as($u);

        $this->putJson('/api/account/details', $this->body($u))->assertStatus(403);

        $this->assertSame('Ada Admin', User::find($u->id)->fullname);
        $this->assertSame('old job', DB::table('resources')->where('code', $u->code)->value('profession'));
    }

    public function test_it_needs_a_signed_in_user(): void
    {
        $this->putJson('/api/account/details', [])->assertStatus(401);
    }
}
