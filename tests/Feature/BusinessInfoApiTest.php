<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Roleaccesssubmenu;
use App\Models\Submenu;
use App\Models\User;
use App\Services\BusinessProfileService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BusinessInfoApiTest extends TestCase
{
    use DatabaseTransactions;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        AppSetting::forget(BusinessProfileService::KEY);   // rolled back with the transaction
    }

    private function actAs(string $role): User
    {
        static $next = 898000;
        $code = $next += random_int(1, 40);
        $u = User::forceCreate(['fname' => 'x', 'lname' => 'T', 'fullname' => 'x T', 'role_code' => $role, 'email' => "b$code@example.test",
            'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']);
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($u);

        return $u;
    }

    private function body(array $over = []): array
    {
        return $over + ['business_name' => 'Nexsuz Inc.', 'registration_type' => 'SEC', 'registration_number' => 'CS2024-0001', 'city' => 'Makati',
            'country' => 'Philippines', 'email_official' => 'hello@nexsuz.test', 'email_support' => 'support@nexsuz.test', 'phone_primary' => '+63 912 345 6789',
            'facebook' => 'https://facebook.com/nexsuz', 'operating_hours' => 'Mon-Fri 9-6', 'show_phone' => false];
    }

    public function test_master_admin_saves_and_the_record_persists_as_one_row(): void
    {
        $this->actAs('DEF-MASTERADMIN');
        $this->putJson('/api/business-info', $this->body())->assertOk()->assertJsonPath('data.business_name', 'Nexsuz Inc.');
        $this->getJson('/api/business-info')->assertOk()->assertJsonPath('data.registration_number', 'CS2024-0001')->assertJsonPath('data.saved', true);
        $this->putJson('/api/business-info', $this->body(['business_name' => 'Nexsuz Updated']))->assertOk();
        $this->assertSame(1, AppSetting::where('setting_key', BusinessProfileService::KEY)->count());
    }

    public function test_admin_follows_the_assigned_submenu_permission_and_shares_the_record(): void
    {
        $sub = Submenu::where('routes', 'nexsuzinfo')->firstOrFail();
        Roleaccesssubmenu::where('rolecode', 'DEF-ADMIN')->where('submenus_id', $sub->id)->delete();   // start unassigned (rolled back after the test)

        $this->actAs('DEF-ADMIN');
        $this->getJson('/api/business-info')->assertStatus(403);

        Roleaccesssubmenu::forceCreate(['rolecode' => 'DEF-ADMIN', 'transNo' => 120, 'submenus_id' => $sub->id]);
        $this->putJson('/api/business-info', $this->body(['business_name' => 'By Admin']))->assertOk();

        $this->actAs('DEF-MASTERADMIN');   // the other page sees the admin's change
        $this->getJson('/api/business-info')->assertJsonPath('data.business_name', 'By Admin');
    }

    public function test_other_roles_and_guests_are_refused(): void
    {
        $this->getJson('/api/business-info')->assertStatus(401);
        foreach (['DEF-USERS', 'DEF-CLIENT'] as $role) {
            $this->actAs($role);
            $this->getJson('/api/business-info')->assertStatus(403);
            $this->putJson('/api/business-info', $this->body())->assertStatus(403);
            $this->postJson('/api/business-info/logo', ['logo' => UploadedFile::fake()->image('l.png')])->assertStatus(403);
            $this->deleteJson('/api/business-info/logo')->assertStatus(403);
        }
    }

    public function test_validation_rejects_bad_input(): void
    {
        $this->actAs('DEF-MASTERADMIN');
        $bads = [['business_name' => ''], ['email_official' => 'nope'], ['facebook' => 'javascript:alert(1)'], ['maps_url' => 'ftp://x'],
            ['registration_type' => 'XYZ'], ['phone_primary' => 'abc'], ['established_on' => '2999-01-01'], ['other_links' => [['label' => 'X', 'url' => 'not a url']]]];
        foreach ($bads as $bad) {
            $this->putJson('/api/business-info', $this->body($bad))->assertStatus(422);
        }
    }

    public function test_logo_upload_replace_and_remove_use_the_public_disk(): void
    {
        $this->actAs('DEF-MASTERADMIN');
        $first = $this->postJson('/api/business-info/logo', ['logo' => UploadedFile::fake()->createWithContent('a.png', base64_decode(self::PNG))])->assertOk();
        $url = $first->json('data.logo_url');
        $this->assertStringContainsString('/storage/uploads/business/logo/', $url);
        $path = 'uploads/business/logo/' . explode('/uploads/business/logo/', $url)[1];
        Storage::disk('public')->assertExists($path);

        $second = $this->postJson('/api/business-info/logo', ['logo' => UploadedFile::fake()->createWithContent('b.webp', base64_decode(self::PNG))]);
        $second->assertOk();
        Storage::disk('public')->assertMissing($path);
        $this->assertNotSame($url, $second->json('data.logo_url'));

        $this->deleteJson('/api/business-info/logo')->assertOk()->assertJsonPath('data.logo_url', null);
        $this->assertSame([], Storage::disk('public')->allFiles('uploads/business'));
    }

    public function test_logo_type_size_and_unsafe_svg_are_refused(): void
    {
        $this->actAs('DEF-MASTERADMIN');
        $this->postJson('/api/business-info/logo', ['logo' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])->assertStatus(422);
        $this->postJson('/api/business-info/logo', ['logo' => UploadedFile::fake()->create('x.php', 1)])->assertStatus(422);
        $this->postJson('/api/business-info/logo', ['logo' => UploadedFile::fake()->create('big.png', 3000, 'image/png')])->assertStatus(422);
        $evil = UploadedFile::fake()->createWithContent('e.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $this->postJson('/api/business-info/logo', ['logo' => $evil])->assertStatus(422);
        $evil2 = UploadedFile::fake()->createWithContent('e2.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="x()"><rect/></svg>');
        $this->postJson('/api/business-info/logo', ['logo' => $evil2])->assertStatus(422);
        $ok = UploadedFile::fake()->createWithContent('ok.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10"/></svg>');
        $this->postJson('/api/business-info/logo', ['logo' => $ok])->assertOk();
    }

    public function test_public_endpoint_honours_display_switches_and_hides_the_recipient(): void
    {
        $this->actAs('DEF-MASTERADMIN');
        $this->putJson('/api/business-info', $this->body(['contact_recipient_email' => 'inbox@nexsuz.test', 'show_hours' => false]))->assertOk();

        $res = $this->getJson('/api/business-info/public')->assertOk();
        $res->assertJsonPath('data.business_name', 'Nexsuz Inc.')->assertJsonPath('data.phone_primary', null)->assertJsonPath('data.operating_hours', null)
            ->assertJsonPath('data.email', 'support@nexsuz.test')->assertJsonPath('data.social.facebook', 'https://facebook.com/nexsuz');
        $this->assertStringNotContainsString('inbox@nexsuz.test', $res->getContent());
        $this->assertStringContainsString('Makati', (string) $res->json('data.address'));
    }

    private function smtp(array $over = []): void
    {
        config(['mail.mailers.contact' => $over + ['transport' => 'smtp', 'host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls', 'username' => 'sender@gmail.test',
            'password' => 'APP-PASSWORD-SECRET', 'scheme' => 'smtp'], 'mail.contact_from' => ['address' => 'sender@gmail.test', 'name' => 'Nexsuz Website']]);
    }

    public function test_contact_form_goes_to_the_dynamic_recipient_with_the_visitor_as_reply_to(): void
    {
        Mail::fake();
        $this->smtp();
        $this->actAs('DEF-MASTERADMIN');
        $this->putJson('/api/business-info', $this->body(['contact_recipient_email' => 'inbox@nexsuz.test']))->assertOk();
        $this->postJson('/api/contact-message', ['fullName' => 'Ann', 'email' => 'bad', 'subject' => 's', 'message' => 'm'])->assertStatus(422);
        $this->postJson('/api/contact-message', ['fullName' => 'Ann', 'email' => 'ann@example.test', 'subject' => "Hello\nBcc: x@y.z", 'message' => 'Hi there'])
            ->assertOk()->assertJsonPath('success', true);

        Mail::assertSent(\App\Mail\ContactInquiry::class, function ($m) {
            return $m->hasTo('inbox@nexsuz.test') && $m->hasReplyTo('ann@example.test') && $m->hasFrom('sender@gmail.test')   // sender login and recipient are independent
                && !str_contains($m->envelope()->subject, "\n");
        });

        // switching the recipient in Nexsuz Information changes where mail goes; nothing else changes
        $this->putJson('/api/business-info', $this->body(['contact_recipient_email' => 'other@nexsuz.test']))->assertOk();
        $this->postJson('/api/contact-message', ['fullName' => 'Bo', 'email' => 'bo@example.test', 'subject' => 's', 'message' => 'm'])->assertOk();
        Mail::assertSent(\App\Mail\ContactInquiry::class, fn ($m) => $m->hasTo('other@nexsuz.test'));
    }

    public function test_contact_form_works_for_gmail_and_for_a_domain_smtp_without_any_form_change(): void
    {
        Mail::fake();
        $this->actAs('DEF-MASTERADMIN');
        $this->putJson('/api/business-info', $this->body(['contact_recipient_email' => 'inbox@nexsuz.test']))->assertOk();
        foreach ([['host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls'], ['host' => 'smtp.hostinger.com', 'port' => 465, 'encryption' => 'ssl', 'scheme' => 'smtps']] as $cfg) {
            $this->smtp($cfg);
            $this->postJson('/api/contact-message', ['fullName' => 'Ann', 'email' => 'ann@example.test', 'subject' => 's', 'message' => 'm'])->assertOk();
        }
        Mail::assertSent(\App\Mail\ContactInquiry::class, 2);
    }

    public function test_missing_smtp_or_recipient_is_a_clear_503_for_visitors_and_a_precise_message_for_admins(): void
    {
        Mail::fake();
        $this->actAs('DEF-MASTERADMIN');
        $form = ['fullName' => 'Ann', 'email' => 'ann@example.test', 'subject' => 's', 'message' => 'm'];

        $this->smtp();
        // no recipient saved yet: the visitor's message is still kept (it shows in Contact Inquiries) but nothing is emailed
        $this->postJson('/api/contact-message', $form)->assertOk();
        $this->assertStringContainsString('No recipient email is set', (string) \App\Models\ContactInquiry::latest('id')->first()->notify_error);
        $this->putJson('/api/business-info', $this->body(['contact_recipient_email' => 'inbox@nexsuz.test']))->assertOk();

        $this->smtp(['password' => null]);
        $res = $this->postJson('/api/contact-message', $form)->assertOk();
        $this->assertStringNotContainsString('CONTACT_MAIL', $res->getContent());   // visitors never see setup details
        $this->assertStringContainsString('CONTACT_MAIL_PASSWORD', (string) \App\Models\ContactInquiry::latest('id')->first()->notify_error);   // the reason is kept for admins
        $st = $this->getJson('/api/business-info/contact-mail')->assertOk();
        $this->assertSame(['CONTACT_MAIL_PASSWORD (or MAIL_PASSWORD)'], $st->json('data.missing'));
        $this->postJson('/api/business-info/contact-mail/check')->assertStatus(422)->assertJsonPath('success', false);
        Mail::assertNothingSent();
    }

    public function test_status_never_exposes_the_password_and_is_admin_only(): void
    {
        $this->smtp();
        $this->actAs('DEF-USERS');
        $this->getJson('/api/business-info/contact-mail')->assertStatus(403);
        $this->postJson('/api/business-info/contact-mail/test')->assertStatus(403);

        $this->actAs('DEF-MASTERADMIN');
        $res = $this->getJson('/api/business-info/contact-mail')->assertOk()->assertJsonPath('data.has_password', true)->assertJsonPath('data.provider', 'Gmail / Google Workspace');
        $this->assertStringNotContainsString('APP-PASSWORD-SECRET', $res->getContent());
        $this->assertStringNotContainsString('APP-PASSWORD-SECRET', $this->getJson('/api/business-info')->getContent());
        $this->assertStringNotContainsString('APP-PASSWORD-SECRET', $this->getJson('/api/business-info/public')->getContent());
        $this->smtp(['username' => 'other@gmail.test']);
        $this->assertNotEmpty($this->getJson('/api/business-info/contact-mail')->json('data.warnings'));   // Gmail sender mismatch is flagged
    }

    public function test_admin_test_email_and_meaningful_delivery_errors(): void
    {
        Mail::fake();
        $this->smtp();
        $this->actAs('DEF-MASTERADMIN');
        $this->postJson('/api/business-info/contact-mail/test')->assertStatus(422);   // no recipient yet
        $this->putJson('/api/business-info', $this->body(['contact_recipient_email' => 'inbox@nexsuz.test']))->assertOk();
        $this->postJson('/api/business-info/contact-mail/test')->assertOk();
        Mail::assertSent(\App\Mail\ContactInquiry::class, fn ($m) => $m->hasTo('inbox@nexsuz.test'));

        $svc = app(\App\Services\ContactMailService::class);
        $this->assertStringContainsString('App Password', $svc->explain(new \RuntimeException('Expected response code "250" but got code "535", with message "535 5.7.8 Username and Password not accepted"')));
        $this->assertStringContainsString('Timed out', $svc->explain(new \RuntimeException('Connection to "smtp.gmail.com:587" timed out')));
        $this->assertStringContainsString('Could not connect', $svc->explain(new \RuntimeException('Connection could not be established with host "x"')));
        $this->assertStringNotContainsString('APP-PASSWORD-SECRET', $svc->explain(new \RuntimeException('AUTH failed for APP-PASSWORD-SECRET')));
    }

    public function test_recipient_is_validated_on_save_and_before_sending_and_status_reports_it(): void
    {
        Mail::fake();
        $this->smtp();
        $this->actAs('DEF-MASTERADMIN');
        $form = ['fullName' => 'Ann', 'email' => 'ann@example.test', 'subject' => 's', 'message' => 'm'];

        // nothing saved: not ready, clear message, nothing sent
        $st = $this->getJson('/api/business-info/contact-mail')->assertOk();
        $this->assertFalse($st->json('data.ready'));
        $this->assertSame('missing', $st->json('data.recipient_problem'));
        $this->assertStringContainsString('No recipient email is set', implode(' ', $st->json('data.warnings')));
        $this->postJson('/api/business-info/contact-mail/test')->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'No recipient email is set'));
        $this->postJson('/api/contact-message', $form)->assertOk();   // saved, not emailed

        // an invalid recipient cannot be saved...
        foreach (['not-an-email', 'a@b', 'x@y.com, z@w.com', 'a b@c.com'] as $bad) {
            $this->putJson('/api/business-info', $this->body(['contact_recipient_email' => $bad]))->assertStatus(422);
        }
        // ...and one that got into the record some other way is still refused at send time
        AppSetting::write(BusinessProfileService::KEY, ['contact_recipient_email' => 'broken@@x', 'email_support' => '', 'email_official' => ''] + app(BusinessProfileService::class)->record());
        $st = $this->getJson('/api/business-info/contact-mail')->assertOk();
        $this->assertSame('invalid', $st->json('data.recipient_problem'));
        $this->assertFalse($st->json('data.ready'));
        $this->postJson('/api/contact-message', $form)->assertOk();   // saved, not emailed
        Mail::assertNothingSent();

        // Gmail and custom-domain recipients both work and flip readiness to true
        foreach (['someone@gmail.com', 'info@mycompany.com.ph'] as $good) {
            $this->putJson('/api/business-info', $this->body(['contact_recipient_email' => $good]))->assertOk();
            $this->getJson('/api/business-info/contact-mail')->assertJsonPath('data.ready', true)->assertJsonPath('data.recipient', $good)->assertJsonPath('data.recipient_problem', null);
            $this->postJson('/api/contact-message', $form)->assertOk();
            Mail::assertSent(\App\Mail\ContactInquiry::class, fn ($m) => $m->hasTo($good));
        }
    }

    public function test_logo_style_saves_validates_and_reaches_the_public_endpoint_without_touching_the_logo(): void
    {
        $this->actAs('DEF-MASTERADMIN');
        $this->getJson('/api/business-info')->assertJsonPath('data.logo_style.bg_mode', 'white');   // defaults keep today's look
        $this->postJson('/api/business-info/logo', ['logo' => UploadedFile::fake()->createWithContent('a.png', base64_decode(self::PNG))])->assertOk();
        $before = $this->getJson('/api/business-info')->json('data.logo_url');

        $style = ['bg_mode' => 'gradient', 'bg_color' => '#112233', 'gradient_from' => '#0a66c2', 'gradient_to' => '#002a57', 'gradient_angle' => 90,
            'padding' => 10, 'radius' => 20, 'width' => 120, 'height' => null, 'align' => 'center'];
        $this->putJson('/api/business-info', $this->body(['logo_style' => $style]))->assertOk()->assertJsonPath('data.logo_style.bg_mode', 'gradient');
        $this->assertSame($before, $this->getJson('/api/business-info')->json('data.logo_url'));   // the uploaded image is untouched
        $this->getJson('/api/business-info/public')->assertJsonPath('data.logo_style.align', 'center')->assertJsonPath('data.logo_style.padding', 10);

        foreach ([['bg_mode' => 'rainbow'], ['bg_color' => 'red'], ['bg_color' => 'url(x)'], ['padding' => 99], ['radius' => -1], ['width' => 5], ['height' => 900], ['align' => 'top'], ['gradient_angle' => 400]] as $bad) {
            $this->putJson('/api/business-info', $this->body(['logo_style' => $bad + $style]))->assertStatus(422);
        }
    }
}
