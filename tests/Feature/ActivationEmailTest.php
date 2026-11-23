<?php

namespace Tests\Feature;

use App\Mail\Registeractivation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Sign-up activation email: the link, a sign-up that survives a mail failure, and "resend". Mail is faked; rows roll back. */
class ActivationEmailTest extends TestCase
{
    use DatabaseTransactions;

    private function payload(string $email, array $over = []): array
    {
        return $over + ['fname' => 'Test', 'lname' => 'Person', 'contactno' => '9171234567', 'email' => $email, 'password' => 'Aa1!aaaa',
            'password_confirmation' => 'Aa1!aaaa', 'statuscode' => 0, 'age' => 25, 'profession' => 'Tester'];
    }

    private function unique(string $tag): string { return $tag . bin2hex(random_bytes(4)) . '@example.test'; }

    public function test_the_email_button_uses_the_frontend_url_not_a_hard_coded_site(): void
    {
        config(['app.frontend_url' => 'http://localhost:4200/']);
        $html = (new Registeractivation(['fname' => 'Ann', 'email' => 'a+b@example.test', 'code' => 'Ab3dEf9']))->render();

        $url = 'http://localhost:4200/activation/a%2Bb%40example.test?code=Ab3dEf9';
        $this->assertStringContainsString('href="' . $url . '"', $html);
        $this->assertStringNotContainsString('nexsuz.com/activation', $html);
        $this->assertStringContainsString('Activate My Account', $html);
        $this->assertStringContainsString('Ab3dEf9', $html);
        // the plain link is shown as text too, for mail apps that block buttons
        $this->assertGreaterThanOrEqual(2, substr_count($html, $url));
        $this->assertStringContainsString('If the button does not work', $html);
    }

    public function test_the_link_follows_the_configured_frontend_url(): void
    {
        config(['app.frontend_url' => 'https://app.example.test']);
        $html = (new Registeractivation(['fname' => 'A', 'email' => 'x@example.test', 'code' => 'ZZ']))->render();
        $this->assertStringContainsString('https://app.example.test/activation/x%40example.test?code=ZZ', $html);
    }

    public function test_sign_up_sends_the_activation_email(): void
    {
        Mail::fake();
        $email = $this->unique('ok');

        $this->postJson('/api/register', $this->payload($email))->assertStatus(201)
            ->assertJsonPath('success', true)->assertJsonPath('email_sent', true);

        $this->assertSame(1, User::where('email', $email)->count());
        $code = DB::table('email_codes')->where('email', $email)->value('code');
        $this->assertNotEmpty($code);
        Mail::assertSent(Registeractivation::class, fn ($m) => $m->hasTo($email) && $m->data['code'] === $code);
    }

    public function test_a_mail_failure_does_not_undo_the_sign_up_or_leak_the_smtp_error(): void
    {
        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('send')->andThrow(new \Exception('Failed to authenticate on SMTP server with username "admin01@example.test"'));
        $email = $this->unique('fail');

        $r = $this->postJson('/api/register', $this->payload($email))->assertStatus(201)
            ->assertJsonPath('success', true)->assertJsonPath('email_sent', false);

        $this->assertStringContainsString('could not send the activation email', $r->json('message'));
        $this->assertStringNotContainsString('SMTP', $r->json('message'));
        $this->assertStringNotContainsString('authenticate', $r->json('message'));
        $this->assertSame(1, User::where('email', $email)->count(), 'the account must still exist');
        $this->assertSame(1, DB::table('email_codes')->where('email', $email)->count(), 'a code is stored so a resend can work');
    }

    public function test_sign_up_validation_still_applies(): void
    {
        Mail::fake();
        $this->postJson('/api/register', $this->payload('not-an-email'))->assertOk()->assertJsonPath('success', false);
        Mail::assertNothingSent();
    }

    public function test_resend_issues_a_fresh_code_and_replaces_the_old_one(): void
    {
        Mail::fake();
        $email = $this->unique('resend');
        $this->postJson('/api/register', $this->payload($email))->assertStatus(201);
        $old = DB::table('email_codes')->where('email', $email)->value('code');

        $this->postJson('/api/resend-activation', ['email' => $email])->assertOk()->assertJsonPath('success', true);

        $codes = DB::table('email_codes')->where('email', $email)->pluck('code');
        $this->assertCount(1, $codes);
        $this->assertNotSame($old, $codes[0]);
        Mail::assertSent(Registeractivation::class, fn ($m) => $m->hasTo($email) && $m->data['code'] === $codes[0]);
    }

    public function test_resend_gives_the_same_answer_for_everyone_so_it_cannot_be_used_to_find_accounts(): void
    {
        Mail::fake();
        $known = $this->unique('known');
        $this->postJson('/api/register', $this->payload($known))->assertStatus(201);
        $activated = $this->unique('active');
        $this->postJson('/api/register', $this->payload($activated))->assertStatus(201);
        User::where('email', $activated)->update(['status' => 'A']);
        Mail::fake();   // forget the sign-up emails

        $a = $this->postJson('/api/resend-activation', ['email' => $known])->assertOk()->json();
        $b = $this->postJson('/api/resend-activation', ['email' => $activated])->assertOk()->json();
        $c = $this->postJson('/api/resend-activation', ['email' => $this->unique('nobody')])->assertOk()->json();

        $this->assertSame($a, $b);
        $this->assertSame($a, $c);
        Mail::assertSent(Registeractivation::class, 1);                       // only the not-yet-activated account got one
        Mail::assertSent(Registeractivation::class, fn ($m) => $m->hasTo($known));
        Mail::assertNotSent(Registeractivation::class, fn ($m) => $m->hasTo($activated));
        $this->postJson('/api/resend-activation', ['email' => 'nope'])->assertStatus(422);
    }

    public function test_resend_survives_a_mail_failure(): void
    {
        $email = $this->unique('down');
        Mail::fake();
        $this->postJson('/api/register', $this->payload($email))->assertStatus(201);

        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('send')->andThrow(new \Exception('connection refused'));
        $this->postJson('/api/resend-activation', ['email' => $email])->assertOk()->assertJsonPath('success', true);
    }

    public function test_resend_is_rate_limited_per_address(): void
    {
        Mail::fake();
        $email = $this->unique('spam');
        $codes = [];
        for ($i = 0; $i < 5; $i++) {
            $codes[] = $this->postJson('/api/resend-activation', ['email' => $email])->getStatusCode();
        }
        $this->assertSame([200, 200, 200, 429, 429], $codes);
        // another address is not affected
        $this->postJson('/api/resend-activation', ['email' => $this->unique('other')])->assertOk();
    }

    public function test_the_code_from_the_email_activates_the_account(): void
    {
        Mail::fake();
        $email = $this->unique('act');
        $this->postJson('/api/register', $this->payload($email))->assertStatus(201);
        $code = DB::table('email_codes')->where('email', $email)->value('code');
        $this->assertNotSame('A', User::where('email', $email)->value('status'));

        $this->postJson('/api/accountactivation', ['email' => $email, 'code' => 'wrong'])->assertOk()->assertJsonPath('success', false);
        $this->postJson('/api/accountactivation', ['email' => $email, 'code' => $code])->assertOk()->assertJsonPath('success', true);
        $this->assertSame('A', User::where('email', $email)->value('status'));
    }
}
