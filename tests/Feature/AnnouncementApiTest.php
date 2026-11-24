<?php

namespace Tests\Feature;

use App\Events\AnnouncementPublished;
use App\Models\User;
use App\Services\AnnouncementService;
use App\Services\DataResetService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Global announcements: DEF-ADMIN / DEF-MASTERADMIN write, every job seeker and recruiter reads, with no friendship needed.
 * Drafts, archived and expired announcements never reach a regular user. Every rule is the API's. Rows are InnoDB (rolled back).
 */
class AnnouncementApiTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<string,User> */
    private array $u = [];

    protected function setUp(): void
    {
        parent::setUp();
        static $next = 893000;
        foreach (['admin' => 'DEF-ADMIN', 'admin2' => 'DEF-ADMIN', 'master' => 'DEF-MASTERADMIN', 'seeker' => 'DEF-USERS', 'seeker2' => 'DEF-USERS', 'recruiter' => 'DEF-CLIENT'] as $k => $role) {
            $code = $next += random_int(1, 30);
            $this->u[$k] = User::forceCreate(['fname' => $k, 'lname' => 'T', 'fullname' => "$k T", 'role_code' => $role, 'email' => "$k$code@example.test",
                'password' => bcrypt('pw'), 'status' => 'A', 'code' => $code, 'coverphoto' => '']);
        }
        Storage::fake('public');
        Event::fake([AnnouncementPublished::class]);
    }

    private function as(string $who): void { $this->app['auth']->forgetGuards(); Sanctum::actingAs($this->u[$who]); }

    /** create through the real endpoint, as $who; returns the card */
    private function make(string $who, array $over = []): array
    {
        $this->as($who);
        $r = $this->post('/api/announcements', $over + ['title' => 'Maintenance', 'content' => 'The site is down on Sunday.', 'status' => 'published'], ['Accept' => 'application/json']);
        $r->assertStatus(201);

        return $r->json('data');
    }

    private function feedIds(string $who): array
    {
        $this->as($who);

        return collect($this->getJson('/api/announcements')->assertOk()->json('data'))->pluck('id')->all();
    }

    // ------------------------------------------------------------------ who may write

    public function test_only_administrators_can_manage_announcements(): void
    {
        $a = $this->make('admin');
        foreach (['seeker', 'recruiter'] as $who) {
            $this->as($who);
            $this->post('/api/announcements', ['title' => 'x', 'content' => 'y'], ['Accept' => 'application/json'])->assertStatus(403);
            $this->getJson('/api/announcements/manage')->assertStatus(403);
            $this->post("/api/announcements/{$a['id']}/update", ['title' => 'hacked'], ['Accept' => 'application/json'])->assertStatus(403);
            $this->postJson("/api/announcements/{$a['id']}/status", ['status' => 'archived'])->assertStatus(403);
            $this->deleteJson("/api/announcements/{$a['id']}")->assertStatus(403);
        }
        $this->assertSame('Maintenance', DB::table('announcements')->where('id', $a['id'])->value('title'));

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/announcements')->assertStatus(401);
        $this->postJson('/api/announcements', [])->assertStatus(401);
    }

    public function test_admins_and_master_admins_can_create(): void
    {
        foreach (['admin', 'master'] as $who) {
            $a = $this->make($who);
            $this->assertSame($who === 'master' ? 'DEF-MASTERADMIN' : 'DEF-ADMIN', $a['author']['role_code']);
        }
    }

    // ------------------------------------------------------------------ published: everybody sees it, no connection needed

    public function test_a_published_announcement_reaches_every_job_seeker_and_recruiter_with_everything_on_the_card(): void
    {
        DB::table('userprofiles')->insert(['code' => $this->u['admin']->code, 'transNo' => (int) DB::table('userprofiles')->max('transNo') + 1, 'email' => 'x@example.test',
            'photo_pic' => 'uploads/x/photo.jpg', 'contact_visibility' => 0, 'email_visibility' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $a = $this->make('admin', [
            'title' => 'Holiday schedule', 'content' => 'Offices close on Friday.', 'priority' => 'urgent', 'is_pinned' => '1',
            'expires_at' => now()->addDays(10)->toDateTimeString(),
            'images' => [UploadedFile::fake()->image('banner.jpg', 600, 300)], 'attachments' => [UploadedFile::fake()->create('schedule.pdf', 120, 'application/pdf')],
        ]);

        foreach (['seeker', 'seeker2', 'recruiter', 'admin2', 'master'] as $viewer) {   // none of them is connected to the author
            $this->assertContains($a['id'], $this->feedIds($viewer), "$viewer sees it");
        }

        $this->as('recruiter');
        $card = collect($this->getJson('/api/announcements')->json('data'))->firstWhere('id', $a['id']);
        $this->assertSame('Holiday schedule', $card['title']);
        $this->assertSame('Offices close on Friday.', $card['content']);
        $this->assertSame('urgent', $card['priority']);
        $this->assertTrue($card['is_pinned']);
        $this->assertSame('published', $card['status']);
        $this->assertNotEmpty($card['published_at']);
        $this->assertNotEmpty($card['expires_at']);
        $this->assertSame('admin T', $card['author']['fullname']);
        $this->assertSame('DEF-ADMIN', $card['author']['role_code']);
        $this->assertSame('uploads/x/photo.jpg', $card['author']['photo_pic']);
        $this->assertCount(1, $card['images']);
        $this->assertStringContainsString('uploads/announcements/', $card['images'][0]['url']);
        $this->assertCount(1, $card['attachments']);
        $this->assertSame('schedule.pdf', $card['attachments'][0]['name']);
        $this->assertFalse($card['can_edit']);
        $this->assertFalse($card['can_moderate']);
        Storage::disk('public')->assertExists(substr(parse_url($card['images'][0]['url'], PHP_URL_PATH), strlen('/storage/')));
    }

    public function test_the_live_signal_has_no_content(): void
    {
        $this->make('admin', ['title' => 'SECRET TITLE', 'content' => 'SECRET BODY', 'priority' => 'important']);
        Event::assertDispatched(AnnouncementPublished::class, fn ($e) => $e->announcement['priority'] === 'important'
            && array_keys($e->announcement) === ['id', 'priority'] && !str_contains(json_encode($e->announcement), 'SECRET'));
    }

    // ------------------------------------------------------------------ draft / archived / expired never reach a regular user

    public function test_a_draft_is_visible_to_its_author_only_and_announces_nothing(): void
    {
        $d = $this->make('admin', ['status' => 'draft', 'title' => 'Not yet']);
        Event::assertNotDispatched(AnnouncementPublished::class);

        foreach (['seeker', 'recruiter', 'admin2', 'master'] as $who) {
            $this->assertNotContains($d['id'], $this->feedIds($who), "$who feed");
            $this->as($who);
            $this->getJson("/api/announcements/{$d['id']}")->assertStatus(404);
        }
        foreach (['admin2', 'master'] as $who) {
            $this->as($who);
            $this->assertNotContains($d['id'], collect($this->getJson('/api/announcements/manage')->json('data'))->pluck('id')->all(), "$who manage list");
        }
        $this->as('admin');
        $this->getJson("/api/announcements/{$d['id']}")->assertOk()->assertJsonPath('data.status', 'draft');
        $this->assertContains($d['id'], collect($this->getJson('/api/announcements/manage')->json('data'))->pluck('id')->all());
    }

    public function test_publishing_a_draft_shows_it_everywhere_and_archiving_hides_it_again(): void
    {
        $d = $this->make('admin', ['status' => 'draft']);
        $this->as('admin');
        $r = $this->postJson("/api/announcements/{$d['id']}/status", ['status' => 'published'])->assertOk();
        $this->assertSame('published', $r->json('data.status'));
        $this->assertNotEmpty($r->json('data.published_at'));
        Event::assertDispatched(AnnouncementPublished::class);
        foreach (['seeker', 'recruiter'] as $who) { $this->assertContains($d['id'], $this->feedIds($who)); }

        $this->as('admin');
        $this->postJson("/api/announcements/{$d['id']}/status", ['status' => 'archived'])->assertOk();
        foreach (['seeker', 'recruiter'] as $who) {
            $this->assertNotContains($d['id'], $this->feedIds($who), "$who after archive");
            $this->as($who);
            $this->getJson("/api/announcements/{$d['id']}")->assertStatus(404);
        }
        $this->as('admin2');
        $this->assertContains($d['id'], collect($this->getJson('/api/announcements/manage?status=archived')->json('data'))->pluck('id')->all(), 'administrators still see archived ones');

        $this->as('admin');
        $this->postJson("/api/announcements/{$d['id']}/status", ['status' => 'published'])->assertOk();   // restore
        $this->assertContains($d['id'], $this->feedIds('seeker'));
    }

    public function test_only_valid_status_changes_are_allowed(): void
    {
        $d = $this->make('admin', ['status' => 'draft']);
        $this->as('admin');
        $this->postJson("/api/announcements/{$d['id']}/status", ['status' => 'archived'])->assertStatus(422);     // draft -> archived
        $this->postJson("/api/announcements/{$d['id']}/status", ['status' => 'published'])->assertOk();
        $this->postJson("/api/announcements/{$d['id']}/status", ['status' => 'draft'])->assertStatus(422);        // published -> draft
        $this->postJson("/api/announcements/{$d['id']}/status", ['status' => 'nonsense'])->assertStatus(422);
    }

    public function test_an_expired_announcement_disappears_for_users_but_not_for_administrators(): void
    {
        $a = $this->make('admin', ['expires_at' => now()->addDay()->toDateTimeString()]);
        $this->assertContains($a['id'], $this->feedIds('seeker'));

        DB::table('announcements')->where('id', $a['id'])->update(['expires_at' => now()->subMinute()]);
        $this->assertNotContains($a['id'], $this->feedIds('seeker'));
        $this->assertNotContains($a['id'], $this->feedIds('recruiter'));
        $this->as('seeker');
        $this->getJson("/api/announcements/{$a['id']}")->assertStatus(404);

        $this->as('admin2');
        $row = collect($this->getJson('/api/announcements/manage')->json('data'))->firstWhere('id', $a['id']);
        $this->assertTrue($row['is_expired']);

        // an expired one can't be re-published until it has a new date
        $this->as('admin');
        $this->postJson("/api/announcements/{$a['id']}/status", ['status' => 'archived'])->assertOk();
        $this->postJson("/api/announcements/{$a['id']}/status", ['status' => 'published'])->assertStatus(422);
        $this->post("/api/announcements/{$a['id']}/update", ['expires_at' => now()->addWeek()->toDateTimeString()], ['Accept' => 'application/json'])->assertOk();
        $this->postJson("/api/announcements/{$a['id']}/status", ['status' => 'published'])->assertOk();
        $this->assertContains($a['id'], $this->feedIds('seeker'));
    }

    public function test_a_new_published_announcement_cannot_already_be_expired(): void
    {
        $this->as('admin');
        $this->post('/api/announcements', ['title' => 't', 'content' => 'c', 'status' => 'published', 'expires_at' => now()->subDay()->toDateTimeString()], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('expires_at');
        $this->assertSame(0, DB::table('announcements')->where('title', 't')->count());
    }

    // ------------------------------------------------------------------ pinned and priority

    public function test_pinned_come_first_then_priority_then_newest(): void
    {
        $old = $this->make('admin', ['title' => 'old normal']);
        $urgent = $this->make('admin', ['title' => 'urgent', 'priority' => 'urgent']);
        $pinned = $this->make('admin', ['title' => 'pinned normal', 'is_pinned' => '1']);
        $new = $this->make('admin', ['title' => 'new normal']);
        $important = $this->make('master', ['title' => 'important', 'priority' => 'important']);

        $ids = $this->feedIds('seeker');
        $mine = array_values(array_intersect($ids, [$old['id'], $urgent['id'], $pinned['id'], $new['id'], $important['id']]));
        $this->assertSame([$pinned['id'], $urgent['id'], $important['id'], $new['id'], $old['id']], $mine);
    }

    // ------------------------------------------------------------------ edit, archive, delete

    public function test_only_the_author_edits_and_the_master_admin_may_moderate(): void
    {
        $a = $this->make('admin');

        foreach (['admin2', 'master'] as $other) {
            $this->as($other);
            $this->post("/api/announcements/{$a['id']}/update", ['title' => 'hacked'], ['Accept' => 'application/json'])->assertStatus(403);
        }
        $this->as('admin2');
        $this->postJson("/api/announcements/{$a['id']}/status", ['status' => 'archived'])->assertStatus(403);
        $this->deleteJson("/api/announcements/{$a['id']}")->assertStatus(403);
        $this->assertSame('Maintenance', DB::table('announcements')->where('id', $a['id'])->value('title'));

        // the author edits
        $this->as('admin');
        $this->post("/api/announcements/{$a['id']}/update", ['title' => 'Updated title', 'priority' => 'important', 'is_pinned' => '1'], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.title', 'Updated title')->assertJsonPath('data.priority', 'important')->assertJsonPath('data.is_pinned', true)
            ->assertJsonPath('data.can_edit', true);

        // the master admin moderates: archive, then delete (never edits, never touches somebody's draft)
        $draft = $this->make('admin', ['status' => 'draft']);
        $this->as('master');
        $this->postJson("/api/announcements/{$draft['id']}/status", ['status' => 'archived'])->assertStatus(404);
        $this->deleteJson("/api/announcements/{$draft['id']}")->assertStatus(404);
        $this->postJson("/api/announcements/{$a['id']}/status", ['status' => 'archived'])->assertOk();
        $this->deleteJson("/api/announcements/{$a['id']}")->assertOk();
        $this->assertNull(DB::table('announcements')->where('id', $a['id'])->first());
    }

    public function test_editing_adds_and_removes_attachments_but_only_its_own(): void
    {
        $a = $this->make('admin', ['attachments' => [UploadedFile::fake()->create('one.pdf', 50, 'application/pdf')]]);
        $other = $this->make('admin', ['attachments' => [UploadedFile::fake()->create('other.pdf', 50, 'application/pdf')]]);

        $this->as('admin');
        $r = $this->post("/api/announcements/{$a['id']}/update", [
            'images' => [UploadedFile::fake()->image('new.png')], 'remove_attachments' => [$a['attachments'][0]['id'], $other['attachments'][0]['id']],
        ], ['Accept' => 'application/json'])->assertOk();
        $this->assertCount(1, $r->json('data.images'));
        $this->assertCount(0, $r->json('data.attachments'), 'its own attachment is gone');
        $this->assertSame(1, DB::table('announcement_attachments')->where('announcement_id', $other['id'])->count(), 'another announcement\'s attachment is untouched');
    }

    public function test_deleting_removes_its_files_comments_and_reactions(): void
    {
        $a = $this->make('admin', ['images' => [UploadedFile::fake()->image('i.jpg')]]);
        $dir = "uploads/announcements/{$this->u['admin']->code}/{$a['announcement_uuid']}";
        Storage::disk('public')->assertExists($dir);

        $this->as('seeker');
        $this->postJson("/api/announcements/{$a['id']}/react", ['reaction' => 'Like'])->assertOk();
        $this->postJson('/api/comment', ['post_uuid' => $a['announcement_uuid'], 'comment' => 'ok'])->assertStatus(201);

        $this->as('admin');
        $this->deleteJson("/api/announcements/{$a['id']}")->assertOk();
        $this->assertSame(0, DB::table('announcement_attachments')->where('announcement_id', $a['id'])->count());
        $this->assertSame(0, DB::table('commentposts')->where('post_uuidOrUind', $a['announcement_uuid'])->count());
        $this->assertSame(0, DB::table('reactionpost')->where('post_id', -$a['id'])->count());
        Storage::disk('public')->assertMissing($dir);
    }

    // ------------------------------------------------------------------ validation

    public function test_input_is_validated(): void
    {
        $this->as('admin');
        $post = fn (array $d) => $this->post('/api/announcements', $d, ['Accept' => 'application/json']);
        $post(['content' => 'x'])->assertStatus(422)->assertJsonValidationErrors('title');
        $post(['title' => 'x'])->assertStatus(422)->assertJsonValidationErrors('content');
        $post(['title' => str_repeat('a', 201), 'content' => 'x'])->assertStatus(422)->assertJsonValidationErrors('title');
        $post(['title' => 't', 'content' => 'x', 'priority' => 'critical'])->assertStatus(422)->assertJsonValidationErrors('priority');
        $post(['title' => 't', 'content' => 'x', 'status' => 'archived'])->assertStatus(422)->assertJsonValidationErrors('status');
        $post(['title' => 't', 'content' => 'x', 'images' => [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]])->assertStatus(422);
        $post(['title' => 't', 'content' => 'x', 'attachments' => [UploadedFile::fake()->create('a.exe', 10, 'application/octet-stream')]])->assertStatus(422);
        $post(['title' => 't', 'content' => 'x', 'images' => array_map(fn ($i) => UploadedFile::fake()->image("$i.jpg"), range(1, 11))])->assertStatus(422);
        $this->assertSame(0, DB::table('announcements')->where('code', $this->u['admin']->code)->count());
    }

    // ------------------------------------------------------------------ reactions and comments

    public function test_anyone_can_react_without_a_connection_but_not_to_a_draft_or_archived_one(): void
    {
        $a = $this->make('admin');
        $draft = $this->make('admin', ['status' => 'draft']);

        $this->as('seeker');
        $r = $this->postJson("/api/announcements/{$a['id']}/react", ['reaction' => 'Love'])->assertOk();
        $this->assertSame(1, $r->json('reaction_summary.total'));
        $this->assertSame('Love', $r->json('reaction_summary.mine'));
        $r = $this->postJson("/api/announcements/{$a['id']}/react", ['reaction' => 'Wow'])->assertOk();    // one reaction per person: it changes
        $this->assertSame(1, $r->json('reaction_summary.total'));
        $this->assertSame('Wow', $r->json('reaction_summary.mine'));
        $this->assertSame(1, DB::table('reactionpost')->where('post_id', -$a['id'])->count());

        $this->postJson("/api/announcements/{$a['id']}/react", ['reaction' => 'Nope'])->assertStatus(422);
        $this->postJson("/api/announcements/{$draft['id']}/react", ['reaction' => 'Like'])->assertStatus(404);

        $this->as('seeker2');
        $this->postJson("/api/announcements/{$a['id']}/react", ['reaction' => 'Like'])->assertOk();
        $this->as('seeker');
        $card = collect($this->getJson('/api/announcements')->json('data'))->firstWhere('id', $a['id']);
        $this->assertSame(2, $card['reaction_summary']['total']);
        $this->assertSame('Wow', $card['my_reaction']);

        $r = $this->deleteJson("/api/announcements/{$a['id']}/react")->assertOk();
        $this->assertSame(1, $r->json('reaction_summary.total'));
        $this->assertNull($r->json('reaction_summary.mine'));
    }

    public function test_comments_reuse_the_existing_endpoints_and_follow_the_announcements_visibility(): void
    {
        $a = $this->make('admin');
        $draft = $this->make('admin', ['status' => 'draft']);

        $this->as('seeker');
        $c = $this->postJson('/api/comment', ['post_uuid' => $a['announcement_uuid'], 'comment' => 'Thanks for the notice'])->assertStatus(201);
        $list = $this->getJson('/api/comment?post_uuidOrUind=' . $a['announcement_uuid'])->assertOk();
        $this->assertCount(1, $list->json());   // this endpoint returns the thread as a plain list

        // a draft has no public comment thread
        $this->postJson('/api/comment', ['post_uuid' => $draft['announcement_uuid'], 'comment' => 'sneaky'])->assertStatus(403);
        $this->getJson('/api/comment?post_uuidOrUind=' . $draft['announcement_uuid'])->assertStatus(403);

        $this->as('recruiter');
        $card = collect($this->getJson('/api/announcements')->json('data'))->firstWhere('id', $a['id']);
        $this->assertSame(1, $card['comments_count']);

        // the administrator who wrote it can remove a comment on it, as with an author's own post
        $this->as('admin');
        $uuid = DB::table('commentposts')->where('post_uuidOrUind', $a['announcement_uuid'])->value('comment_uuid');
        $this->deleteJson("/api/comment/{$uuid}?comment_settings=com_headers")->assertOk();
        $this->assertSame(0, DB::table('commentposts')->where('post_uuidOrUind', $a['announcement_uuid'])->count());
    }

    // ------------------------------------------------------------------ notifications

    public function test_users_and_clients_are_notified_of_a_new_announcement_and_administrators_are_not(): void
    {
        foreach (['seeker', 'recruiter', 'admin2'] as $who) { $this->as($who); $this->getJson('/api/activity-notifications')->assertOk(); }   // first look: history counts as seen
        DB::table('user_notification_settings')->whereIn('user_id', [$this->u['seeker']->id, $this->u['recruiter']->id])->update(['seen_at' => now()->subMinutes(5)]);   // not the same second as what follows

        $a = $this->make('admin', ['title' => 'Policy update']);
        $draft = $this->make('admin', ['status' => 'draft', 'title' => 'Hidden draft']);

        foreach (['seeker', 'recruiter'] as $who) {
            $this->as($who);
            $items = collect($this->getJson('/api/activity-notifications')->assertOk()->json('data'));
            $n = $items->firstWhere('id', 'an-' . $a['id']);
            $this->assertNotNull($n, "$who is notified");
            $this->assertSame('announcement', $n['type']);
            $this->assertSame('published an announcement', $n['text']);
            $this->assertSame('Policy update', $n['excerpt']);
            $this->assertSame('admin T', $n['actor']['fullname']);
            $this->assertTrue($n['unread']);
            $this->assertNull($items->firstWhere('id', 'an-' . $draft['id']), 'a draft is never announced');
        }
        $this->as('admin2');
        $this->assertNull(collect($this->getJson('/api/activity-notifications')->json('data'))->firstWhere('id', 'an-' . $a['id']), 'administrators are not notified');

        // archiving withdraws the notification
        $this->as('admin');
        $this->postJson("/api/announcements/{$a['id']}/status", ['status' => 'archived'])->assertOk();
        $this->as('seeker');
        $this->assertNull(collect($this->getJson('/api/activity-notifications')->json('data'))->firstWhere('id', 'an-' . $a['id']));
    }

    public function test_the_announcements_notification_switch_is_respected(): void
    {
        $this->as('seeker');
        $this->getJson('/api/activity-notifications')->assertOk();
        $a = $this->make('admin');
        $this->as('seeker');
        $this->putJson('/api/notification-settings', ['announcements' => false])->assertOk();
        $this->assertNull(collect($this->getJson('/api/activity-notifications')->json('data'))->firstWhere('id', 'an-' . $a['id']));
        $this->putJson('/api/notification-settings', ['announcements' => true])->assertOk();
        $this->assertNotNull(collect($this->getJson('/api/activity-notifications')->json('data'))->firstWhere('id', 'an-' . $a['id']));
    }

    // ------------------------------------------------------------------ the rest of the system is untouched

    public function test_announcements_never_leak_into_the_post_feed_and_deleting_an_account_removes_them(): void
    {
        $a = $this->make('admin', ['attachments' => [UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]]);
        $this->as('seeker');
        $this->assertSame([], collect($this->getJson('/api/getPost')->json('data'))->where('caption', 'The site is down on Sunday.')->all(), 'not a post');

        $this->as('seeker');
        $this->postJson("/api/announcements/{$a['id']}/react", ['reaction' => 'Like'])->assertOk();
        $this->postJson('/api/comment', ['post_uuid' => $a['announcement_uuid'], 'comment' => 'hi'])->assertStatus(201);

        $svc = app(DataResetService::class);
        $svc->purge(false, $svc->doomedForUser($this->u['admin']));
        $this->assertNull(DB::table('announcements')->where('id', $a['id'])->first());
        $this->assertSame(0, DB::table('announcement_attachments')->where('announcement_id', $a['id'])->count());
        $this->assertSame(0, DB::table('commentposts')->where('post_uuidOrUind', $a['announcement_uuid'])->count());
        $this->assertSame(0, DB::table('reactionpost')->where('post_id', -$a['id'])->count());
    }

    public function test_the_service_rules_directly(): void
    {
        $svc = app(AnnouncementService::class);
        $draft = (object) ['status' => 'draft', 'code' => $this->u['admin']->code, 'published_at' => null, 'expires_at' => null];
        $this->assertTrue($svc->canView($draft, $this->u['admin']));
        $this->assertFalse($svc->canView($draft, $this->u['master']));
        $this->assertFalse($svc->canView($draft, $this->u['seeker']));
        $this->assertFalse($svc->canView($draft, null));
        $live = (object) ['status' => 'published', 'code' => $this->u['admin']->code, 'published_at' => now()->subMinute(), 'expires_at' => null];
        foreach (['seeker', 'recruiter', 'admin2'] as $who) { $this->assertTrue($svc->canView($live, $this->u[$who]), $who); }
    }
}
