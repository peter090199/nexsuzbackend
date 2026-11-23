<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use App\Services\MailConfigService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** SMTP behaviour is faked here (no real server is contacted); app_settings is InnoDB, so every test rolls back. */
class FakeSmtpMailConfig extends MailConfigService
{
    public static bool $connects = true;
    public static array $sent = [];

    public function testConnection(array $s): array
    {
        return self::$connects ? ['ok' => true, 'message' => 'Connected and signed in to ' . $s['host'] . ':' . $s['port'] . '.']
            : ['ok' => false, 'message' => 'Failed to authenticate on SMTP server'];
    }

    public function sendTest(array $s, string $to, string $appName): array
    {
        self::$sent[] = [$s['host'], $to];
        return self::$connects ? ['ok' => true, 'message' => 'Test email sent to ' . $to . '.'] : ['ok' => false, 'message' => 'Could not send'];
    }
}

class EmailConfigApiTest extends TestCase
{
    use DatabaseTransactions;

    private const SECRET = 'S3cret-App-Pass!';

    private function body(array $over = []): array
    {
        return $over + ['provider' => 'custom', 'host' => 'smtp.example.test', 'port' => 587, 'encryption' => 'tls',
            'username' => 'mailer@example.test', 'password' => self::SECRET, 'from_address' => 'noreply@example.test', 'from_name' => 'Nexsuz'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        FakeSmtpMailConfig::$connects = true;
        FakeSmtpMailConfig::$sent = [];
        $this->app->singleton(MailConfigService::class, fn () => new FakeSmtpMailConfig());
        $this->actAs('DEF-MASTERADMIN');
    }

    private function actAs(string $role): void
    {
        static $next = 899000;
        $code = $next += random_int(1, 40);
        $u = User::forceCreate(['fname' => 'x', 'lname' => 'T', 'fullname' => 'x T', 'role_code' => $role, 'email' => "x$code@example.test",
            'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']);
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($u);
    }

    private function stored(): ?array { return AppSetting::read(MailConfigService::KEY); }

    public function test_only_master_admin_can_use_every_endpoint(): void
    {
        foreach (['DEF-ADMIN', 'DEF-USERS', 'DEF-CLIENT'] as $role) {
            $this->actAs($role);
            $this->getJson('/api/email-config')->assertStatus(403);
            $this->postJson('/api/email-config', $this->body())->assertStatus(403);
            $this->putJson('/api/email-config', $this->body())->assertStatus(403);
            $this->deleteJson('/api/email-config')->assertStatus(403);
            $this->postJson('/api/email-config/toggle', ['enabled' => true])->assertStatus(403);
            $this->postJson('/api/email-config/test-connection', [])->assertStatus(403);
            $this->postJson('/api/email-config/test-email', ['to' => 'a@b.test'])->assertStatus(403);
        }
        $this->assertNull($this->stored());
    }

    public function test_get_uses_env_fallback_when_nothing_is_saved_and_never_returns_a_password(): void
    {
        $r = $this->getJson('/api/email-config')->assertOk()->assertJsonPath('data.saved', false)->assertJsonPath('data.source', 'env')
            ->assertJsonPath('data.mailer', 'smtp')->assertJsonPath('data.status.enabled', true)->assertJsonPath('data.status.connection', 'not_tested');
        $this->assertArrayNotHasKey('password', $r->json('data.settings'));
        $this->assertArrayHasKey('hostinger', $r->json('data.providers'));
        $this->assertSame(465, $r->json('data.providers.hostinger.port'));
        $this->assertSame('ssl', $r->json('data.providers.hostinger.encryption'));
    }

    public function test_save_validates_the_input(): void
    {
        $this->postJson('/api/email-config', [])->assertStatus(422);
        $this->postJson('/api/email-config', $this->body(['host' => 'http://smtp.example.test']))->assertStatus(422)->assertJsonValidationErrors('host');
        $this->postJson('/api/email-config', $this->body(['port' => 70000]))->assertStatus(422)->assertJsonValidationErrors('port');
        $this->postJson('/api/email-config', $this->body(['encryption' => 'starttls']))->assertStatus(422)->assertJsonValidationErrors('encryption');
        $this->postJson('/api/email-config', $this->body(['from_address' => 'nope']))->assertStatus(422)->assertJsonValidationErrors('from_address');
        $this->postJson('/api/email-config', $this->body(['provider' => 'mailgun']))->assertStatus(422)->assertJsonValidationErrors('provider');
        $this->postJson('/api/email-config', $this->body(['password' => '']))->assertStatus(422)->assertJsonValidationErrors('password');
        $this->assertNull($this->stored());
    }

    public function test_credentials_must_authenticate_before_anything_is_saved(): void
    {
        FakeSmtpMailConfig::$connects = false;
        $this->postJson('/api/email-config', $this->body())->assertStatus(422)->assertJsonPath('success', false);
        $this->assertNull($this->stored());
        $this->assertSame('failed', $this->getJson('/api/email-config')->json('data.status.connection'));
    }

    public function test_save_encrypts_the_password_applies_it_live_and_never_returns_it(): void
    {
        $res = $this->postJson('/api/email-config', $this->body())->assertOk()
            ->assertJsonPath('data.saved', true)->assertJsonPath('data.source', 'database')
            ->assertJsonPath('data.settings.has_password', true)->assertJsonPath('data.status.enabled', true)->assertJsonPath('data.status.connection', 'connected');
        $this->assertStringNotContainsString(self::SECRET, $res->getContent());
        $this->assertStringNotContainsString(self::SECRET, $this->getJson('/api/email-config')->getContent());

        // encrypted at rest
        $raw = DB::table('app_settings')->where('setting_key', 'mail.smtp')->value('value');
        $this->assertStringNotContainsString(self::SECRET, $raw);
        $this->assertSame(self::SECRET, Crypt::decryptString($this->stored()['password']));

        // applied to Laravel's mail config immediately, no restart
        $this->assertSame('smtp.example.test', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertSame(self::SECRET, config('mail.mailers.smtp.password'));
        $this->assertSame('noreply@example.test', config('mail.from.address'));
        $this->assertSame('smtp', config('mail.default'));

        $this->postJson('/api/email-config', $this->body())->assertStatus(409);
    }

    public function test_ssl_uses_implicit_tls_and_none_disables_starttls(): void
    {
        $this->assertSame('smtps', MailConfigService::mailerConfig($this->body(['encryption' => 'ssl', 'port' => 465]))['scheme']);
        $tls = MailConfigService::mailerConfig($this->body(['encryption' => 'tls']));
        $this->assertSame('smtp', $tls['scheme']);
        $this->assertTrue($tls['auto_tls']);
        $none = MailConfigService::mailerConfig($this->body(['encryption' => 'none']));
        $this->assertFalse($none['auto_tls']);
        $this->assertNull($none['encryption']);
    }

    public function test_update_keeps_the_stored_password_when_left_blank_and_refuses_bad_settings(): void
    {
        $this->putJson('/api/email-config', $this->body())->assertStatus(404);   // nothing saved yet
        $this->postJson('/api/email-config', $this->body())->assertOk();

        $this->putJson('/api/email-config', $this->body(['host' => 'smtp2.example.test', 'password' => '']))->assertOk()
            ->assertJsonPath('data.settings.host', 'smtp2.example.test');
        $this->assertSame(self::SECRET, Crypt::decryptString($this->stored()['password']));
        $this->assertSame('smtp2.example.test', config('mail.mailers.smtp.host'));

        FakeSmtpMailConfig::$connects = false;
        $this->putJson('/api/email-config', $this->body(['host' => 'broken.example.test']))->assertStatus(422);
        $this->assertSame('smtp2.example.test', $this->stored()['host'], 'a failed update must leave the working settings alone');
        $this->assertSame('smtp2.example.test', config('mail.mailers.smtp.host'));
    }

    public function test_disable_discards_mail_and_enable_re_checks_the_connection(): void
    {
        $this->postJson('/api/email-config', $this->body())->assertOk();

        $this->postJson('/api/email-config/toggle', ['enabled' => false])->assertOk()->assertJsonPath('data.status.enabled', false);
        $this->assertFalse($this->stored()['enabled']);
        $this->assertSame('array', config('mail.default'), 'disabled means nothing is sent');

        FakeSmtpMailConfig::$connects = false;
        $this->postJson('/api/email-config/toggle', ['enabled' => true])->assertStatus(422);
        $this->assertFalse($this->stored()['enabled']);
        $this->assertSame('array', config('mail.default'));

        FakeSmtpMailConfig::$connects = true;
        $this->postJson('/api/email-config/toggle', ['enabled' => true])->assertOk()->assertJsonPath('data.status.enabled', true);
        $this->assertSame('smtp', config('mail.default'));
        $this->postJson('/api/email-config/toggle', [])->assertStatus(422);
    }

    public function test_disabling_with_nothing_saved_snapshots_the_env_settings(): void
    {
        $this->postJson('/api/email-config/toggle', ['enabled' => false])->assertOk();
        $s = $this->stored();
        $this->assertFalse($s['enabled']);
        $this->assertNotEmpty($s['host']);
        $this->assertStringNotContainsString((string) config('mail.mailers.smtp.password'), json_encode($s) ?: '');
    }

    public function test_reset_returns_to_the_env_defaults(): void
    {
        $env = (new MailConfigService())->envSettings();
        $this->postJson('/api/email-config', $this->body())->assertOk();
        $this->assertSame('smtp.example.test', config('mail.mailers.smtp.host'));

        $this->deleteJson('/api/email-config')->assertOk()->assertJsonPath('data.saved', false)->assertJsonPath('data.source', 'env');
        $this->assertNull($this->stored());
        $this->assertSame($env['host'], config('mail.mailers.smtp.host'));
        $this->assertSame($env['from_address'], config('mail.from.address'));
        $this->assertSame(env('MAIL_MAILER', 'smtp'), config('mail.default'));
    }

    public function test_test_endpoints_use_form_values_without_saving_them(): void
    {
        $this->postJson('/api/email-config/test-connection', $this->body())->assertOk();
        $this->assertNull($this->stored(), 'testing must not save');

        FakeSmtpMailConfig::$connects = false;
        $this->postJson('/api/email-config/test-connection', $this->body())->assertStatus(422)->assertJsonPath('success', false);
        $this->postJson('/api/email-config/test-connection', ['host' => 'bad host'])->assertStatus(422);

        FakeSmtpMailConfig::$connects = true;
        $this->postJson('/api/email-config/test-connection', [])->assertOk();   // empty body tests the saved / .env settings
    }

    public function test_send_test_email_validates_and_records_the_last_success_or_error(): void
    {
        $this->postJson('/api/email-config/test-email', ['to' => 'not-an-email'])->assertStatus(422);

        $this->postJson('/api/email-config/test-email', ['to' => 'me@example.test', 'settings' => $this->body()])->assertOk();
        $this->assertSame([['smtp.example.test', 'me@example.test']], FakeSmtpMailConfig::$sent);
        $this->assertNotNull($this->getJson('/api/email-config')->json('data.status.last_success_at'));

        FakeSmtpMailConfig::$connects = false;
        $this->postJson('/api/email-config/test-email', ['to' => 'me@example.test'])->assertStatus(422);
        $this->assertSame('Could not send', $this->getJson('/api/email-config')->json('data.status.last_error'));
    }

    public function test_real_emails_update_last_successful_email(): void
    {
        $this->postJson('/api/email-config', $this->body())->assertOk();
        AppSetting::write(MailConfigService::STATUS_KEY, ['last_success_at' => null, 'last_error' => 'old']);

        event(new \Illuminate\Mail\Events\MessageSent(
            new \Illuminate\Mail\SentMessage(new \Symfony\Component\Mailer\SentMessage(
                (new \Symfony\Component\Mime\Email())->from('a@b.test')->to('c@d.test')->subject('x')->text('x'),
                \Symfony\Component\Mailer\Envelope::create((new \Symfony\Component\Mime\Email())->from('a@b.test')->to('c@d.test'))
            ))
        ));

        $s = $this->getJson('/api/email-config')->json('data.status');
        $this->assertNotNull($s['last_success_at']);
        $this->assertNull($s['last_error']);
    }

    public function test_private_network_hosts_are_refused_outside_local_development(): void
    {
        $svc = new MailConfigService();
        $this->assertTrue($svc->hostAllowed('127.0.0.1'), 'local development may use a local relay');
        $this->app['env'] = 'production';
        $this->assertFalse($svc->hostAllowed('127.0.0.1'));
        $this->assertFalse($svc->hostAllowed('192.168.1.10'));
        $this->assertFalse($svc->hostAllowed('169.254.169.254'));
        $this->assertTrue($svc->hostAllowed('8.8.8.8'));
    }

    public function test_error_text_never_contains_the_password(): void
    {
        $svc = new MailConfigService();
        $msg = $svc->safeError(new \RuntimeException('auth failed for password ' . self::SECRET . ' ok'), self::SECRET);
        $this->assertStringNotContainsString(self::SECRET, $msg);
        $this->assertStringContainsString('***', $msg);
    }
}
