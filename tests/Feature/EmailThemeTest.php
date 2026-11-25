<?php

namespace Tests\Feature;

use App\Mail\AppliedStatusUpdated;
use App\Mail\ContactInquiry as ContactInquiryMail;
use App\Mail\ContactReply;
use App\Mail\Forgetpassword;
use App\Mail\Registeractivation;
use App\Mail\ThemeTest as ThemeTestMail;
use App\Models\AppSetting;
use App\Models\User;
use App\Services\BusinessProfileService;
use App\Services\EmailBrandingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailThemeTest extends TestCase
{
    use DatabaseTransactions;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        AppSetting::forget(BusinessProfileService::KEY);
        app(EmailBrandingService::class)->forget();
        config(['mail.mailers.contact' => ['transport' => 'smtp', 'host' => 'smtp.gmail.com', 'port' => 465, 'encryption' => 'ssl', 'username' => 'sender@gmail.test',
            'password' => 'APP-PASSWORD-SECRET', 'scheme' => 'smtps'], 'mail.contact_from' => ['address' => 'sender@gmail.test', 'name' => 'Nexsuz Website']]);
    }

    private function master(): User
    {
        static $next = 896000;
        $code = $next += random_int(1, 40);
        $u = User::forceCreate(['fname' => 'M', 'lname' => 'A', 'fullname' => 'M A', 'role_code' => 'DEF-MASTERADMIN', 'email' => "t$code@example.test",
            'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']);
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($u);

        return $u;
    }

    private function save(array $theme, array $extra = [])
    {
        return $this->putJson('/api/business-info', ['business_name' => 'Acme Hiring', 'email_theme' => $theme] + $extra);
    }

    private function custom(array $colors = [], array $hg = [], array $fg = []): array
    {
        return ['preset' => 'custom', 'colors' => $colors + app(EmailBrandingService::class)->presets()['navy']['colors'], 'header_gradient' => $hg, 'footer_gradient' => $fg];
    }

    /** Every existing email, rendered through its real Mailable. */
    private function allEmails(): array
    {
        $job = (object) ['job_name' => 'Senior Designer', 'fullname' => 'Alex Applicant'];

        return [
            'status' => (new AppliedStatusUpdated($job, 'approved'))->render(),
            'reset' => (new Forgetpassword(['fname' => 'Alex', 'email' => 'alex@example.test', 'token' => 'TOK123']))->render(),
            'activation' => (new Registeractivation(['fname' => 'Alex', 'email' => 'alex@example.test', 'code' => '482913']))->render(),
            'reply' => (new ContactReply('v@example.test', 'Vera', 'Re: Hello', '<p>Reply <b>body</b></p>', []))->render(),
            'inquiry' => (new ContactInquiryMail('Acme Hiring', 'inbox@example.test', 'Vera', 'v@example.test', '+63 900', 'Hello', "Line 1\nLine 2"))->render(),
            'notice' => view('email.notice', ['subject' => 'Interview invitation', 'body' => "Hi Alex,\n\nYou are invited to an interview."])->render(),
        ];
    }

    public function test_presets_are_listed_defaults_to_navy_and_every_preset_is_a_complete_theme(): void
    {
        $this->master();
        $d = $this->getJson('/api/business-info')->assertOk()->json('data');
        $this->assertSame('navy', $d['email_theme']['preset']);
        $this->assertSame(['navy', 'corporate', 'minimal', 'dark', 'gray'], array_keys($d['email_presets']));
        $this->assertSame(['Professional Navy Blue', 'Corporate Blue', 'Minimal White', 'Dark Technology', 'Elegant Gray'], array_column($d['email_presets'], 'label'));
        foreach ($d['email_presets'] as $p) {
            $this->assertEqualsCanonicalizing(EmailBrandingService::COLOR_KEYS, array_keys($p['colors']));
            foreach ($p['colors'] as $hex) { $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $hex); }
        }
    }

    public function test_every_email_uses_the_saved_theme_keeps_its_content_and_shows_the_business_identity(): void
    {
        $this->master();
        $this->postJson('/api/business-info/logo', ['logo' => UploadedFile::fake()->createWithContent('l.png', base64_decode(self::PNG))])->assertOk();
        $this->save($this->custom(['header_bg' => '#123456', 'button_bg' => '#abcdef', 'footer_bg' => '#654321', 'body_bg' => '#fafbfc']))->assertOk();

        $mails = $this->allEmails();
        foreach ($mails as $name => $html) {
            $this->assertStringContainsString('#123456', $html, "$name header color");
            $this->assertStringContainsString('#654321', $html, "$name footer color");
            $this->assertStringContainsString('#fafbfc', $html, "$name body color");
            $this->assertStringContainsString('Acme Hiring', $html, "$name business name");
            $this->assertStringContainsString('/storage/uploads/business/logo/', $html, "$name logo");
            $this->assertStringContainsString('<table', $html, "$name is table based for mail clients");
        }
        // buttons take the themed button color, and the original wording / variables survive
        $this->assertStringContainsString('#abcdef', $mails['reset']);
        $this->assertStringContainsString('/reset-password/alex%40example.test/TOK123', $mails['reset']);
        $this->assertStringContainsString('Reset Password', $mails['reset']);
        $this->assertStringContainsString('482913', $mails['activation']);
        $this->assertStringContainsString('/activation/alex%40example.test?code=482913', $mails['activation']);
        $this->assertStringContainsString('Activate My Account', $mails['activation']);
        $this->assertStringContainsString('Dear Alex Applicant,', $mails['status']);
        $this->assertStringContainsString('Senior Designer', $mails['status']);
        $this->assertStringContainsString('Congratulations! Your application has been approved.', $mails['status']);
        $this->assertStringContainsString('<b>body</b>', $mails['reply']);
        $this->assertStringContainsString('v@example.test', $mails['inquiry']);
        $this->assertStringContainsString('Line 1', $mails['inquiry']);
        $this->assertStringContainsString('You are invited to an interview.', $mails['notice']);
    }

    public function test_gradients_keep_a_solid_fallback_and_are_off_unless_enabled(): void
    {
        $this->master();
        $this->save($this->custom(['header_bg' => '#123456'], ['enabled' => false, 'from' => '#111111', 'to' => '#222222', 'angle' => 90]))->assertOk();
        $off = (new Forgetpassword(['fname' => 'A', 'email' => 'a@example.test', 'token' => 't']))->render();
        $this->assertStringNotContainsString('linear-gradient', $off);

        $this->save($this->custom(['header_bg' => '#123456'], ['enabled' => true, 'from' => '#111111', 'to' => '#222222', 'angle' => 90]))->assertOk();
        $on = (new Forgetpassword(['fname' => 'A', 'email' => 'a@example.test', 'token' => 't']))->render();
        $this->assertStringContainsString('linear-gradient(90deg,#111111,#222222)', $on);
        $this->assertStringContainsString('bgcolor="#111111"', $on);            // clients without gradient support (Outlook) get a solid color
        $this->assertStringContainsString('background-color:#111111', $on);
    }

    public function test_saving_refreshes_the_cached_branding_for_the_next_email(): void
    {
        $this->master();
        $this->save($this->custom(['header_bg' => '#111111']))->assertOk();
        $this->assertStringContainsString('#111111', $this->allEmails()['reset']);                 // cache is warm now
        $this->save($this->custom(['header_bg' => '#222222']))->assertOk();
        $this->assertStringContainsString('#222222', $this->allEmails()['reset']);                 // refreshed by the save
        $this->assertStringNotContainsString('#111111', $this->allEmails()['reset']);
        $this->putJson('/api/business-info', ['business_name' => 'Renamed Co'])->assertOk();        // a name change refreshes it too
        $this->assertStringContainsString('Renamed Co', $this->allEmails()['reset']);
    }

    public function test_choosing_a_preset_applies_its_colors_and_unknown_or_invalid_values_are_refused(): void
    {
        $this->master();
        $this->save(['preset' => 'dark', 'colors' => []])->assertOk()->assertJsonPath('data.email_theme.preset', 'dark')->assertJsonPath('data.email_theme.colors.card_bg', '#162033');
        $this->assertStringContainsString('#162033', $this->allEmails()['status']);
        $this->assertStringContainsString('linear-gradient', $this->allEmails()['status']);          // Dark Technology has a header gradient

        foreach ([['preset' => 'rainbow'], ['preset' => 'custom', 'colors' => ['header_bg' => 'red']], ['preset' => 'custom', 'colors' => ['header_bg' => 'url(x)']],
                     ['preset' => 'custom', 'colors' => ['header_bg' => '#12345']], ['preset' => 'custom', 'header_gradient' => ['enabled' => true, 'from' => '#zzzzzz']],
                     ['preset' => 'custom', 'header_gradient' => ['angle' => 999]]] as $bad) {
            $this->save($bad)->assertStatus(422);
        }
        $this->getJson('/api/business-info')->assertJsonPath('data.email_theme.preset', 'dark');   // nothing bad was saved
    }

    public function test_the_live_preview_renders_the_unsaved_theme_without_saving_it_and_is_admin_only(): void
    {
        $this->master();
        $res = $this->postJson('/api/business-info/email-theme/preview', ['email_theme' => $this->custom(['header_bg' => '#0a0b0c', 'button_bg' => '#0d0e0f']), 'business_name' => 'Draft Name'])->assertOk();
        $html = $res->json('html');
        $this->assertStringContainsString('#0a0b0c', $html);
        $this->assertStringContainsString('#0d0e0f', $html);
        $this->assertStringContainsString('Draft Name', $html);
        $this->assertStringContainsString('View application', $html);                                // sample action button
        $this->getJson('/api/business-info')->assertJsonPath('data.email_theme.preset', 'navy');     // preview saved nothing
        $this->postJson('/api/business-info/email-theme/preview', ['email_theme' => ['preset' => 'custom', 'colors' => ['header_bg' => 'nope']]])->assertStatus(422);

        $u = User::forceCreate(['fname' => 'U', 'lname' => 'U', 'fullname' => 'U U', 'role_code' => 'DEF-USERS', 'email' => 'u' . random_int(1, 99999) . '@example.test',
            'password' => bcrypt('pw'), 'status' => 'A', 'code' => 895000 + random_int(1, 999), 'coverphoto' => '']);
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($u);
        $this->postJson('/api/business-info/email-theme/preview', ['email_theme' => $this->custom()])->assertStatus(403);
        $this->postJson('/api/business-info/email-theme/test')->assertStatus(403);
    }

    public function test_the_theme_test_email_goes_to_the_business_recipient_only(): void
    {
        Mail::fake();
        $this->master();
        $this->postJson('/api/business-info/email-theme/test')->assertStatus(422);                    // no recipient yet
        $this->save($this->custom(), ['contact_recipient_email' => 'inbox@nexsuz.test'])->assertOk();
        $this->postJson('/api/business-info/email-theme/test')->assertOk();
        Mail::assertSent(ThemeTestMail::class, fn ($m) => $m->hasTo('inbox@nexsuz.test') && $m->hasFrom('sender@gmail.test'));
    }

    public function test_the_public_endpoint_never_exposes_the_theme_or_credentials(): void
    {
        $this->master();
        $this->save($this->custom(['header_bg' => '#123456']))->assertOk();
        $body = $this->getJson('/api/business-info/public')->assertOk()->getContent();
        $this->assertStringNotContainsString('email_theme', $body);
        $this->assertStringNotContainsString('APP-PASSWORD-SECRET', $body);
    }
}
