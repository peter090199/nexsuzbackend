<?php

namespace Tests\Feature;

use App\Mail\ContactReply;
use App\Models\ContactInquiry;
use App\Models\Menu;
use App\Models\Roleaccessmenu;
use App\Models\User;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactInquiryApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        config(['mail.mailers.contact' => ['transport' => 'smtp', 'host' => 'smtp.gmail.com', 'port' => 465, 'encryption' => 'ssl', 'username' => 'sender@gmail.test',
            'password' => 'APP-PASSWORD-SECRET', 'scheme' => 'smtps'], 'mail.contact_from' => ['address' => 'sender@gmail.test', 'name' => 'Nexsuz Website']]);
    }

    private function actAs(string $role): User
    {
        static $next = 897000;
        $code = $next += random_int(1, 40);
        $u = User::forceCreate(['fname' => 'Ada', 'lname' => 'Admin', 'fullname' => 'Ada Admin', 'role_code' => $role, 'email' => "i$code@example.test",
            'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']);
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($u);

        return $u;
    }

    private function submit(array $over = []): int
    {
        $this->postJson('/api/contact-message', $over + ['fullName' => 'Vera Visitor', 'email' => 'vera@example.test', 'phone' => '+63 900 000 0000',
            'subject' => 'Pricing question', 'message' => "Hello,\nHow much is the plan?"])->assertOk();

        return ContactInquiry::latest('id')->firstOrFail()->id;
    }

    public function test_public_submission_is_saved_with_a_number_unread_and_the_original_message(): void
    {
        Mail::fake();
        $id = $this->submit();
        $i = ContactInquiry::findOrFail($id);
        $this->assertSame(sprintf('INQ-%06d', $id), $i->inquiry_no);
        $this->assertSame('new', $i->status);
        $this->assertNull($i->read_at);
        $this->assertSame('vera@example.test', $i->email);
        $this->assertDatabaseHas('contact_inquiry_messages', ['inquiry_id' => $id, 'direction' => 'in', 'from_email' => 'vera@example.test', 'status' => 'received']);
        $this->postJson('/api/contact-message', ['fullName' => 'x', 'email' => 'bad', 'subject' => 's', 'message' => 'm'])->assertStatus(422);   // invalid input saves nothing
        $this->assertSame($id, ContactInquiry::max('id'));
    }

    public function test_list_search_filter_unread_count_and_opening_marks_read(): void
    {
        Mail::fake();
        $this->submit(['subject' => 'Alpha topic', 'email' => 'a@example.test']);
        $second = $this->submit(['subject' => 'Beta topic', 'email' => 'b@example.test']);
        $this->actAs('DEF-MASTERADMIN');

        $before = $this->getJson('/api/contact-inquiries/unread-count')->assertOk()->json('unread');
        $this->assertGreaterThanOrEqual(2, $before);
        $this->getJson('/api/contact-inquiries?q=Beta')->assertOk()->assertJsonPath('data.0.id', $second)->assertJsonPath('data.0.unread', true)->assertJsonCount(1, 'data');
        $this->getJson('/api/contact-inquiries?q=b@example.test&status=unread')->assertJsonCount(1, 'data');

        $this->getJson("/api/contact-inquiries/$second")->assertOk()->assertJsonPath('data.status', 'read')->assertJsonPath('data.unread', false)
            ->assertJsonPath('data.messages.0.direction', 'in')->assertJsonPath('unread', $before - 1);
        $this->getJson('/api/contact-inquiries?q=Beta&status=unread')->assertJsonCount(0, 'data');

        $this->postJson("/api/contact-inquiries/$second/read", ['read' => false])->assertOk()->assertJsonPath('data.status', 'new')->assertJsonPath('unread', $before);
        $this->postJson("/api/contact-inquiries/$second/read", ['read' => true])->assertOk()->assertJsonPath('data.unread', false);
        $this->getJson('/api/contact-inquiries?status=bogus')->assertStatus(422);
    }

    public function test_reply_sends_from_the_business_mailbox_to_the_visitor_without_reply_to_and_keeps_the_conversation(): void
    {
        Mail::fake();
        $id = $this->submit();
        $this->actAs('DEF-MASTERADMIN');

        $res = $this->postJson("/api/contact-inquiries/$id/reply", ['subject' => 'Re: Pricing question', 'body' => '<p>Hi <b>Vera</b>, the plan is free.</p><script>alert(1)</script>'])
            ->assertOk()->assertJsonPath('data.status', 'replied');
        Mail::assertSent(ContactReply::class, fn ($m) => $m->hasTo('vera@example.test') && $m->hasFrom('sender@gmail.test') && !$m->hasReplyTo('vera@example.test')
            && !str_contains($m->bodyHtml, '<script') && str_contains($m->bodyHtml, '<b>Vera</b>'));
        $this->assertSame('out', $res->json('data.messages.1.direction'));
        $this->assertSame('sent', $res->json('data.messages.1.status'));

        // a second reply continues the same conversation, nothing is lost
        $res = $this->postJson("/api/contact-inquiries/$id/reply", ['subject' => 'Re: Pricing question', 'body' => '<p>Anything else?</p>'])->assertOk();
        $this->assertSame(['in', 'out', 'out'], array_column($res->json('data.messages'), 'direction'));
        $this->assertSame('Ada Admin', $res->json('data.messages.1.sent_by'));
        $this->assertStringNotContainsString('APP-PASSWORD-SECRET', $res->getContent());
    }

    public function test_the_client_cannot_choose_another_recipient(): void
    {
        Mail::fake();
        $id = $this->submit();
        $this->actAs('DEF-MASTERADMIN');
        $this->postJson("/api/contact-inquiries/$id/reply", ['subject' => 's', 'body' => 'Hello', 'to' => 'victim@example.test', 'email' => 'victim@example.test', 'cc' => 'x@y.test'])->assertOk();
        Mail::assertSent(ContactReply::class, fn ($m) => $m->hasTo('vera@example.test') && !$m->hasTo('victim@example.test') && !$m->hasCc('x@y.test'));
    }

    public function test_a_failed_delivery_is_recorded_and_does_not_mark_the_inquiry_replied(): void
    {
        $id = $this->submit();
        $this->actAs('DEF-MASTERADMIN');

        // SMTP settings missing: refused with a clear message, recorded as Failed
        config(['mail.mailers.contact.password' => null]);
        $res = $this->postJson("/api/contact-inquiries/$id/reply", ['subject' => 's', 'body' => 'Hello'])->assertStatus(502)->assertJsonPath('success', false);
        $this->assertStringContainsString('CONTACT_MAIL_PASSWORD', $res->json('message'));
        $this->assertSame('failed', $res->json('data.messages.1.status'));
        $this->assertNotSame('replied', ContactInquiry::find($id)->status);

        // SMTP rejects the sign-in: the real transport is used and the error is explained without secrets
        config(['mail.mailers.contact.password' => 'APP-PASSWORD-SECRET', 'mail.mailers.contact.host' => '127.0.0.1', 'mail.mailers.contact.port' => 1, 'mail.mailers.contact.timeout' => 2]);
        app('mail.manager')->purge('contact');
        $res = $this->postJson("/api/contact-inquiries/$id/reply", ['subject' => 's', 'body' => 'Hello again'])->assertStatus(502);
        $this->assertSame('failed', $res->json('data.messages.2.status'));
        $this->assertStringNotContainsString('APP-PASSWORD-SECRET', $res->getContent());
        $this->assertNotSame('replied', ContactInquiry::find($id)->status);
        $this->assertCount(3, $res->json('data.messages'));   // history kept: original + two failed attempts

        // retry after fixing the setup works and only now becomes Replied
        config(['mail.mailers.contact.host' => 'smtp.gmail.com', 'mail.mailers.contact.port' => 465]);
        Mail::fake();
        $this->postJson("/api/contact-inquiries/$id/reply", ['subject' => 's', 'body' => 'Third time'])->assertOk()->assertJsonPath('data.status', 'replied');
    }

    public function test_attachments_are_validated_stored_privately_and_downloadable_only_by_authorised_admins(): void
    {
        Mail::fake();
        $id = $this->submit();
        $this->actAs('DEF-MASTERADMIN');
        $url = "/api/contact-inquiries/$id/reply";

        foreach ([UploadedFile::fake()->create('x.exe', 10), UploadedFile::fake()->create('x.php', 1), UploadedFile::fake()->create('x.html', 1), UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf')] as $bad) {
            $this->post($url, ['subject' => 's', 'body' => 'Hello', 'attachments' => [$bad]], ['Accept' => 'application/json'])->assertStatus(422);
        }
        $six = array_map(fn ($n) => UploadedFile::fake()->create("f$n.pdf", 1, 'application/pdf'), range(1, 6));
        $this->post($url, ['subject' => 's', 'body' => 'Hello', 'attachments' => $six], ['Accept' => 'application/json'])->assertStatus(422);
        Mail::assertNotSent(ContactReply::class);

        $res = $this->post($url, ['subject' => 's', 'body' => 'See attached', 'attachments' => [UploadedFile::fake()->create('quote.pdf', 20, 'application/pdf')]], ['Accept' => 'application/json'])->assertOk();
        $att = $res->json('data.messages.1.attachments.0');
        $this->assertSame('quote.pdf', $att['name']);
        $mid = $res->json('data.messages.1.id');
        $this->assertSame([], Storage::disk('public')->allFiles());                 // never in public storage
        $this->assertNotEmpty(Storage::disk('local')->allFiles("contact-inquiries/$id"));
        $this->assertStringNotContainsString('contact-inquiries/', $res->getContent()); // no server path leaks
        Mail::assertSent(ContactReply::class, fn ($m) => count($m->attachments()) === 1);

        $this->get("/api/contact-inquiries/$id/messages/$mid/attachments/0")->assertOk();
        $this->actAs('DEF-USERS');
        $this->get("/api/contact-inquiries/$id/messages/$mid/attachments/0", ['Accept' => 'application/json'])->assertStatus(403);
    }

    public function test_close_reopen_assign_archive_restore_and_permanent_delete_rules(): void
    {
        Mail::fake();
        $id = $this->submit();
        $admin = $this->actAs('DEF-MASTERADMIN');

        $this->postJson("/api/contact-inquiries/$id/status", ['action' => 'close'])->assertOk()->assertJsonPath('data.status', 'closed');
        $this->postJson("/api/contact-inquiries/$id/status", ['action' => 'reopen'])->assertOk()->assertJsonPath('data.status', 'new');
        $this->postJson("/api/contact-inquiries/$id/assign", ['mine' => true])->assertOk()->assertJsonPath('data.assigned_name', 'Ada Admin');
        $this->postJson("/api/contact-inquiries/$id/assign", ['mine' => false])->assertOk()->assertJsonPath('data.assigned_code', null);
        $this->postJson("/api/contact-inquiries/$id/status", ['action' => 'nope'])->assertStatus(422);

        $this->deleteJson("/api/contact-inquiries/$id?permanent=1")->assertStatus(422);   // archive first
        $this->deleteJson("/api/contact-inquiries/$id")->assertOk();
        $this->assertNotNull(ContactInquiry::find($id)->archived_at);
        $this->assertEquals($admin->code, ContactInquiry::find($id)->archived_by);
        $this->getJson('/api/contact-inquiries?q=Pricing')->assertJsonCount(0, 'data');
        $this->getJson('/api/contact-inquiries?archived=1&q=Pricing')->assertJsonCount(1, 'data');
        $this->postJson("/api/contact-inquiries/$id/restore")->assertOk();
        $this->assertNull(ContactInquiry::find($id)->archived_at);

        $this->deleteJson("/api/contact-inquiries/$id")->assertOk();
        $this->deleteJson("/api/contact-inquiries/$id?permanent=1")->assertOk();
        $this->assertNull(ContactInquiry::find($id));
        $this->assertDatabaseMissing('contact_inquiry_messages', ['inquiry_id' => $id]);
    }

    public function test_permissions_master_admin_admin_by_menu_assignment_and_everyone_else_refused(): void
    {
        Mail::fake();
        $id = $this->submit();
        $menu = Menu::where('routes', 'contactinquiries')->firstOrFail();
        $this->assertSame('mail', $menu->icon);

        $this->getJson('/api/contact-inquiries')->assertStatus(401);
        foreach (['DEF-USERS', 'DEF-CLIENT'] as $role) {
            $this->actAs($role);
            $this->getJson('/api/contact-inquiries')->assertStatus(403);
            $this->getJson("/api/contact-inquiries/$id")->assertStatus(403);
            $this->postJson("/api/contact-inquiries/$id/reply", ['subject' => 's', 'body' => 'x'])->assertStatus(403);
            $this->deleteJson("/api/contact-inquiries/$id")->assertStatus(403);
        }
        Mail::assertNotSent(ContactReply::class);

        // Admin follows the Security Roles assignment
        Roleaccessmenu::where('rolecode', 'DEF-ADMIN')->where('menus_id', $menu->id)->delete();
        $this->actAs('DEF-ADMIN');
        $this->getJson('/api/contact-inquiries')->assertStatus(403);
        Roleaccessmenu::forceCreate(['rolecode' => 'DEF-ADMIN', 'transNo' => 999999, 'menus_id' => $menu->id]);
        $this->getJson('/api/contact-inquiries')->assertOk();
        $this->postJson("/api/contact-inquiries/$id/reply", ['subject' => 's', 'body' => 'Hello'])->assertOk();
        $this->deleteJson("/api/contact-inquiries/$id?permanent=1")->assertStatus(403);   // permanent delete is Master Admin only
    }

    public function test_reply_validation_and_the_html_sanitizer(): void
    {
        Mail::fake();
        $id = $this->submit();
        $this->actAs('DEF-MASTERADMIN');
        $this->postJson("/api/contact-inquiries/$id/reply", ['subject' => '', 'body' => 'x'])->assertStatus(422);
        $this->postJson("/api/contact-inquiries/$id/reply", ['subject' => 's', 'body' => ''])->assertStatus(422);
        $this->postJson("/api/contact-inquiries/$id/reply", ['subject' => 's', 'body' => '<p> <br> </p>'])->assertStatus(422);
        Mail::assertNotSent(ContactReply::class);

        $clean = HtmlSanitizer::clean('<p onclick="x()" style="color:red">Hi <a href="javascript:alert(1)">bad</a> <a href="https://ok.test/p">ok</a><img src=x onerror=alert(1)></p><iframe src="//e"></iframe>');
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('<img', $clean);
        $this->assertStringNotContainsString('iframe', $clean);
        $this->assertStringContainsString('href="https://ok.test/p"', $clean);
        $this->assertSame("Hi bad ok", preg_replace('/\s+/', ' ', HtmlSanitizer::toText($clean)));
    }
}
