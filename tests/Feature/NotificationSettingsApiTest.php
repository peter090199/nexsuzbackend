<?php

namespace Tests\Feature;

use App\Events\NotificationCountUpdated;
use App\Helpers\NotificationPrefs;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Notification preferences (stored per user) and what they really control on the server:
 *   - whether a new-message event is pushed to the receiver
 *   - which social activity (friend requests, accepted requests, reactions, comments) is delivered in the feed
 * Rolled-back transactions; nothing is left behind.
 */
class NotificationSettingsApiTest extends TestCase
{
    use DatabaseTransactions;

    private User $me;
    private User $pal;
    private User $other;

    protected function setUp(): void
    {
        parent::setUp();
        config(['broadcasting.default' => 'log']);

        $this->me = $this->makeUser('Mia');
        $this->pal = $this->makeUser('Pal');
        $this->other = $this->makeUser('Otto');
    }

    private function makeUser(string $name): User
    {
        static $next = 880000;
        $code = $next += random_int(1, 40);

        return User::forceCreate([
            'fname' => $name, 'lname' => 'Tester', 'fullname' => "$name Tester",
            'email' => strtolower($name) . $code . '@example.test', 'password' => bcrypt('secret-pass'),
            'status' => 'A', 'code' => $code, 'coverphoto' => '',
        ]);
    }

    /** a separate browser/device session of this user */
    private function as(User $user): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs(User::find($user->id));
    }

    // ------------------------------------------------------------------------ the API itself

    public function test_guests_are_refused(): void
    {
        $this->getJson('/api/notification-settings')->assertUnauthorized();
        $this->putJson('/api/notification-settings', ['sound' => true])->assertUnauthorized();
        $this->postJson('/api/notification-settings/reset')->assertUnauthorized();
        $this->getJson('/api/activity-notifications')->assertUnauthorized();
        $this->postJson('/api/activity-notifications/read')->assertUnauthorized();
    }

    public function test_a_new_user_gets_the_defaults_and_sound_and_desktop_are_off(): void
    {
        $this->as($this->me);
        $res = $this->getJson('/api/notification-settings')->assertOk()->assertJsonPath('success', true);

        $this->assertSame(NotificationPrefs::DEFAULTS, $res->json('data'));
        $this->assertFalse($res->json('data.sound'), 'sound is OFF by default');
        $this->assertFalse($res->json('data.desktop_notifications'), 'desktop notifications are OFF until the user opts in');
        $this->assertSame(70, $res->json('data.sound_volume'));
        $this->assertSame(0, DB::table('user_notification_settings')->where('user_id', $this->me->id)->count(), 'reading never creates a row');
    }

    #[DataProvider('toggles')]
    public function test_every_toggle_saves_and_changes_only_itself(string $key): void
    {
        $default = NotificationPrefs::DEFAULTS[$key];
        $this->as($this->me);

        $res = $this->putJson('/api/notification-settings', [$key => !$default])->assertOk();
        $expected = NotificationPrefs::DEFAULTS;
        $expected[$key] = !$default;
        $this->assertSame($expected, $res->json('data'), "$key flips and nothing else moves");
        $this->assertSame($expected, $this->getJson('/api/notification-settings')->json('data'), 'and it is really stored');

        $this->putJson('/api/notification-settings', [$key => $default])->assertOk();
        $this->assertSame(NotificationPrefs::DEFAULTS, $this->getJson('/api/notification-settings')->json('data'), 'switching back works');
    }

    public static function toggles(): array
    {
        return array_map(fn ($k) => [$k], NotificationPrefs::BOOLEAN_KEYS);
    }

    public function test_accepts_the_usual_boolean_forms(): void
    {
        $this->as($this->me);
        $this->putJson('/api/notification-settings', ['sound' => 1, 'toast' => '0', 'message_preview' => false])->assertOk();
        $d = $this->getJson('/api/notification-settings')->json('data');
        $this->assertTrue($d['sound']);
        $this->assertFalse($d['toast']);
        $this->assertFalse($d['message_preview']);
    }

    public function test_sound_volume_is_validated(): void
    {
        $this->as($this->me);
        foreach ([0, 55, 100] as $ok) {
            $this->putJson('/api/notification-settings', ['sound_volume' => $ok])->assertOk()->assertJsonPath('data.sound_volume', $ok);
        }
        foreach ([-1, 101, 'loud', 50.5, null] as $bad) {
            $this->putJson('/api/notification-settings', ['sound_volume' => $bad])->assertStatus(422);
        }
        $this->assertSame(100, $this->getJson('/api/notification-settings')->json('data.sound_volume'), 'a refused value changes nothing');
    }

    public function test_invalid_values_and_unknown_settings_are_refused(): void
    {
        $this->as($this->me);
        $this->putJson('/api/notification-settings', ['sound' => 'maybe'])->assertStatus(422)->assertJsonPath('success', false);
        $this->putJson('/api/notification-settings', ['sound' => ['x']])->assertStatus(422);
        $this->putJson('/api/notification-settings', ['made_up_setting' => true])->assertStatus(422);
        $this->putJson('/api/notification-settings', ['sound' => true, 'made_up_setting' => true])->assertStatus(422);
        $this->assertFalse($this->getJson('/api/notification-settings')->json('data.sound'), 'a refused request saves nothing, not even its valid part');
    }

    public function test_an_update_can_never_name_another_user(): void
    {
        $this->as($this->me);
        $this->putJson('/api/notification-settings', ['user_id' => $this->pal->id, 'sound' => true])->assertStatus(422);
        $this->putJson('/api/notification-settings', ['code' => $this->pal->code, 'sound' => true])->assertStatus(422);
        $this->assertSame(0, DB::table('user_notification_settings')->where('user_id', $this->pal->id)->count());
    }

    public function test_one_users_settings_are_invisible_and_untouched_by_another(): void
    {
        $this->as($this->me);
        $this->putJson('/api/notification-settings', ['sound' => true, 'toast' => false, 'friend_requests' => false])->assertOk();

        $this->as($this->pal);
        $this->assertSame(NotificationPrefs::DEFAULTS, $this->getJson('/api/notification-settings')->json('data'));
        $this->putJson('/api/notification-settings', ['post_comments' => false])->assertOk();
        $this->postJson('/api/notification-settings/reset')->assertOk();

        $this->as($this->me);
        $d = $this->getJson('/api/notification-settings')->json('data');
        $this->assertTrue($d['sound']);
        $this->assertFalse($d['toast']);
        $this->assertFalse($d['friend_requests'], "Pal's reset did not touch Mia's settings");
    }

    public function test_settings_survive_logout_and_login(): void
    {
        $this->as($this->me);
        $this->putJson('/api/notification-settings', ['sound' => true, 'sound_volume' => 35, 'unread_badge' => false])->assertOk();

        // logout: every token is revoked; login: a brand new session
        $this->me->tokens()->delete();
        $this->as($this->me);
        $d = $this->getJson('/api/notification-settings')->assertOk()->json('data');

        $this->assertTrue($d['sound']);
        $this->assertSame(35, $d['sound_volume']);
        $this->assertFalse($d['unread_badge']);
    }

    public function test_settings_synchronise_across_devices(): void
    {
        // phone saves
        $this->as($this->me);
        $this->putJson('/api/notification-settings', ['toast' => false])->assertOk();

        // laptop (another session) sees it, and saves something else
        $this->as($this->me);
        $this->assertFalse($this->getJson('/api/notification-settings')->json('data.toast'));
        $this->putJson('/api/notification-settings', ['sound' => true])->assertOk();

        // phone sees both on its next refresh
        $this->as($this->me);
        $d = $this->getJson('/api/notification-settings')->json('data');
        $this->assertFalse($d['toast']);
        $this->assertTrue($d['sound']);
        $this->assertSame(1, DB::table('user_notification_settings')->where('user_id', $this->me->id)->count(), 'still one row per user');
    }

    public function test_restore_defaults(): void
    {
        $this->as($this->me);
        $this->putJson('/api/notification-settings', ['sound' => true, 'sound_volume' => 10, 'friend_requests' => false, 'toast' => false])->assertOk();

        $res = $this->postJson('/api/notification-settings/reset')->assertOk();
        $this->assertSame(NotificationPrefs::DEFAULTS, $res->json('data'));
        $this->assertSame(NotificationPrefs::DEFAULTS, $this->getJson('/api/notification-settings')->json('data'));
    }

    public function test_enable_all_and_disable_all_are_one_request_each(): void
    {
        $this->as($this->me);
        $off = array_fill_keys(NotificationPrefs::BOOLEAN_KEYS, false);
        $this->putJson('/api/notification-settings', $off)->assertOk();
        $d = $this->getJson('/api/notification-settings')->json('data');
        foreach (NotificationPrefs::BOOLEAN_KEYS as $k) { $this->assertFalse($d[$k], "$k is off"); }

        $on = array_fill_keys(NotificationPrefs::BOOLEAN_KEYS, true);
        $this->putJson('/api/notification-settings', $on)->assertOk();
        $d = $this->getJson('/api/notification-settings')->json('data');
        foreach (NotificationPrefs::BOOLEAN_KEYS as $k) { $this->assertTrue($d[$k], "$k is on"); }
    }

    // ------------------------------------------------------------------------ messages: what the server really pushes

    private function sendTo(User $receiver, string $text = 'hello'): void
    {
        $this->as($this->pal);
        $this->postJson('/api/send-message', ['receiver_id' => $receiver->id, 'message' => $text])->assertOk();
    }

    private function prefs(User $u, array $set): void
    {
        $this->as($u);
        $this->putJson('/api/notification-settings', $set)->assertOk();
    }

    public function test_a_new_message_is_pushed_to_a_receiver_with_default_settings(): void
    {
        Event::fake([NotificationCountUpdated::class]);
        $this->sendTo($this->me);
        Event::assertDispatched(NotificationCountUpdated::class, fn ($e) => (int) $e->userId === $this->me->id);
    }

    public function test_message_push_is_skipped_only_when_both_notifications_and_badge_are_off(): void
    {
        Event::fake([NotificationCountUpdated::class]);

        $this->prefs($this->me, ['messages_enabled' => false, 'unread_badge' => true]);
        $this->sendTo($this->me, 'badge still wanted');
        Event::assertDispatchedTimes(NotificationCountUpdated::class, 1);

        $this->prefs($this->me, ['messages_enabled' => true, 'unread_badge' => false]);
        $this->sendTo($this->me, 'notifications still wanted');
        Event::assertDispatchedTimes(NotificationCountUpdated::class, 2);

        $this->prefs($this->me, ['messages_enabled' => false, 'unread_badge' => false]);
        $this->sendTo($this->me, 'nothing wanted');
        Event::assertDispatchedTimes(NotificationCountUpdated::class, 2);      // no third event: nothing was pushed
    }

    public function test_a_muted_user_still_gets_the_message_in_the_inbox(): void
    {
        Event::fake([NotificationCountUpdated::class]);
        $this->prefs($this->me, ['messages_enabled' => false, 'unread_badge' => false]);
        $this->sendTo($this->me, 'quiet one');

        $this->as($this->me);
        $this->assertSame('quiet one', $this->getJson('/api/conversations')->assertOk()->json('data.0.last_message.message'));
        $this->assertSame(1, $this->getJson('/api/update_count')->json('unreadCount'), 'the unread count is still correct when asked for');
    }

    public function test_one_users_settings_never_change_what_another_receives(): void
    {
        Event::fake([NotificationCountUpdated::class]);
        $this->prefs($this->me, ['messages_enabled' => false, 'unread_badge' => false]);

        $this->sendTo($this->other);          // Otto never changed anything
        Event::assertDispatched(NotificationCountUpdated::class, fn ($e) => (int) $e->userId === $this->other->id);
    }

    // ------------------------------------------------------------------------ social activity feed

    private function makePost(User $owner): array
    {
        $uuid = (string) Str::uuid();
        $id = DB::table('posts')->insertGetId([
            'code' => $owner->code, 'posts_uuid' => $uuid, 'transNo' => random_int(980000, 989999), 'caption' => 'a post', 'status' => 1,
            'created_by' => 'test', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$id, $uuid];
    }

    /** the first look initialises "seen", so events made after it are new and older history is already read */
    private function openedFirstTime(): void
    {
        $this->as($this->me);
        $this->getJson('/api/activity-notifications')->assertOk();
    }

    private function activity(User $u): array
    {
        $this->as($u);

        return $this->getJson('/api/activity-notifications')->assertOk()->json();
    }

    private function types(array $res): array
    {
        $t = collect($res['data'])->pluck('type')->unique()->sort()->values()->all();

        return $t;
    }

    private function seedAllActivity(): array
    {
        [$postId, $postUuid] = $this->makePost($this->me);
        $later = now()->addMinutes(5);

        DB::table('follows')->insert(['follower_code' => $this->pal->code, 'following_code' => $this->me->code, 'follow_status' => 'pending', 'created_at' => $later]);
        DB::table('follows')->insert(['follower_code' => $this->me->code, 'following_code' => $this->other->code, 'follow_status' => 'accepted', 'created_at' => $later]);
        DB::table('reactionpost')->insert(['post_id' => $postId, 'code' => (string) $this->pal->code, 'reaction' => 'Love', 'create_at' => $later, 'updated_at' => $later]);
        $commentUuid = (string) Str::uuid();
        DB::table('commentposts')->insert(['comment_uuid' => $commentUuid, 'post_uuidOrUind' => $postUuid, 'status' => 0, 'code' => $this->pal->code, 'comment' => 'nice post!', 'date_comment' => now()->toDateString(), 'created_at' => $later, 'updated_at' => $later]);

        // my own comment on a post of Pal's, and Pal's reply to it
        [, $palPost] = $this->makePost($this->pal);
        $mine = (string) Str::uuid();
        DB::table('commentposts')->insert(['comment_uuid' => $mine, 'post_uuidOrUind' => $palPost, 'status' => 0, 'code' => $this->me->code, 'comment' => 'my words', 'date_comment' => now()->toDateString(), 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()]);
        DB::table('commentreplies')->insert(['comment_uuid' => $mine, 'status' => 0, 'code' => $this->pal->code, 'comment' => 'thanks for that', 'date_comment' => now()->toDateString(), 'created_at' => $later, 'updated_at' => $later]);

        return [$postId, $postUuid];
    }

    public function test_the_feed_delivers_every_kind_of_activity_addressed_to_me(): void
    {
        $this->openedFirstTime();
        $this->seedAllActivity();

        $res = $this->activity($this->me);
        $this->assertSame(['comment', 'friend_accept', 'friend_request', 'reaction'], $this->types($res));
        $this->assertCount(5, $res['data']);                                     // request, accept, reaction, comment, reply
        $this->assertSame(5, $res['unread_count']);

        $first = collect($res['data'])->firstWhere('type', 'reaction');
        $this->assertSame('Pal Tester', $first['actor']['fullname']);
        $this->assertSame('reacted Love to your post', $first['text']);
        $this->assertTrue($first['unread']);
        $comment = collect($res['data'])->firstWhere('text', 'commented on your post');
        $this->assertSame('nice post!', $comment['excerpt']);
        $this->assertSame('Pal Tester', $comment['actor']['fullname']);
        $this->assertContains('replied to your comment', collect($res['data'])->pluck('text')->all());
    }

    #[DataProvider('categories')]
    public function test_a_category_that_is_switched_off_is_not_delivered(string $pref, string $type, int $expectedCount): void
    {
        $this->openedFirstTime();
        $this->seedAllActivity();
        $this->as($this->me);
        $this->putJson('/api/notification-settings', [$pref => false])->assertOk();

        $res = $this->activity($this->me);
        $this->assertNotContains($type, $this->types($res), "$type is gone from the feed");
        $this->assertCount(5 - $expectedCount, $res['data']);
        $this->assertSame(5 - $expectedCount, $res['unread_count'], 'and from the unread count');

        $count = $this->getJson('/api/activity-notifications?count_only=1')->assertOk()->json();
        $this->assertSame(5 - $expectedCount, $count['unread_count'], 'the lightweight count agrees');
        $this->assertArrayNotHasKey('data', $count);

        $this->putJson('/api/notification-settings', [$pref => true])->assertOk();
        $this->assertContains($type, $this->types($this->activity($this->me)), 'switching it back on brings it back');
    }

    public static function categories(): array
    {
        return [
            'friend requests'  => ['friend_requests', 'friend_request', 1],
            'accepted requests' => ['friend_accepts', 'friend_accept', 1],
            'reactions'        => ['post_reactions', 'reaction', 1],
            'comments + replies' => ['post_comments', 'comment', 2],
        ];
    }

    public function test_everything_off_delivers_nothing(): void
    {
        $this->openedFirstTime();
        $this->seedAllActivity();
        $this->as($this->me);
        $this->putJson('/api/notification-settings', array_fill_keys(NotificationPrefs::BOOLEAN_KEYS, false))->assertOk();

        $res = $this->activity($this->me);
        $this->assertSame([], $res['data']);
        $this->assertSame(0, $res['unread_count']);
    }

    public function test_my_own_actions_never_notify_me(): void
    {
        $this->openedFirstTime();
        [$postId, $postUuid] = $this->makePost($this->me);
        $later = now()->addMinutes(5);
        DB::table('reactionpost')->insert(['post_id' => $postId, 'code' => (string) $this->me->code, 'reaction' => 'Like', 'create_at' => $later]);
        DB::table('commentposts')->insert(['comment_uuid' => (string) Str::uuid(), 'post_uuidOrUind' => $postUuid, 'status' => 0, 'code' => $this->me->code, 'comment' => 'talking to myself', 'date_comment' => now()->toDateString(), 'created_at' => $later]);

        $this->assertSame([], $this->activity($this->me)['data']);
    }

    public function test_activity_is_only_delivered_to_the_person_it_is_for(): void
    {
        $this->openedFirstTime();
        $this->seedAllActivity();

        $this->assertSame([], $this->activity($this->other)['data'] ? array_filter($this->activity($this->other)['data'], fn ($i) => $i['type'] !== 'friend_accept') : []);
        $palFeed = $this->activity($this->pal);
        $this->assertNotContains('reaction', $this->types($palFeed), "Pal made the reaction, Mia receives it");
        $this->assertNotContains('friend_request', $this->types($palFeed));
    }

    public function test_marking_read_clears_the_count_and_new_activity_counts_again(): void
    {
        $this->openedFirstTime();
        $this->seedAllActivity();
        $this->assertSame(5, $this->activity($this->me)['unread_count']);

        $this->travelTo(now()->addMinutes(10));                  // time passes: the user opens their notifications
        $this->as($this->me);
        $this->postJson('/api/activity-notifications/read')->assertOk()->assertJsonPath('unread_count', 0);
        $after = $this->activity($this->me);
        $this->assertSame(0, $after['unread_count']);
        $this->assertCount(5, $after['data'], 'read items stay in the list');
        $this->assertFalse($after['data'][0]['unread']);

        [$postId] = $this->makePost($this->me);                  // and later somebody reacts again
        DB::table('reactionpost')->insert(['post_id' => $postId, 'code' => (string) $this->other->code, 'reaction' => 'Wow', 'create_at' => now()->addMinutes(5)]);
        $this->assertSame(1, $this->activity($this->me)['unread_count']);
        $this->travelBack();
    }

    public function test_a_users_first_visit_does_not_flood_them_with_old_history(): void
    {
        [$postId] = $this->makePost($this->me);                    // history that already exists before the first look
        DB::table('reactionpost')->insert(['post_id' => $postId, 'code' => (string) $this->pal->code, 'reaction' => 'Like', 'create_at' => now()->subDays(2)]);
        DB::table('follows')->insert(['follower_code' => $this->other->code, 'following_code' => $this->me->code, 'follow_status' => 'pending', 'created_at' => now()->subDays(3)]);
        $this->assertNull(NotificationPrefs::seenAt($this->me->id));

        $res = $this->activity($this->me);
        $this->assertCount(2, $res['data'], 'the history is listed');
        $this->assertSame(0, $res['unread_count'], 'but none of it is "new"');
        $this->assertNotNull(NotificationPrefs::seenAt($this->me->id), 'the first look recorded the seen time');
    }

    public function test_only_the_last_30_days_are_considered(): void
    {
        $this->openedFirstTime();
        [$postId] = $this->makePost($this->me);
        DB::table('reactionpost')->insert(['post_id' => $postId, 'code' => (string) $this->pal->code, 'reaction' => 'Like', 'create_at' => now()->subDays(45)]);
        $this->assertSame([], $this->activity($this->me)['data']);
    }

    public function test_marking_read_keeps_the_notification_settings(): void
    {
        $this->as($this->me);
        $this->putJson('/api/notification-settings', ['sound' => true])->assertOk();
        $this->postJson('/api/activity-notifications/read')->assertOk();
        $this->assertTrue($this->getJson('/api/notification-settings')->json('data.sound'));

        $this->postJson('/api/notification-settings/reset')->assertOk();
        $this->assertNotNull(NotificationPrefs::seenAt($this->me->id), 'restoring defaults does not forget when activity was last seen');
    }

    // ------------------------------------------------------------------------ appearance: navigation bar colour

    public function test_the_nav_colour_defaults_to_the_standard_look(): void
    {
        $this->as($this->me);
        $this->assertNull($this->getJson('/api/notification-settings')->assertOk()->json('data.nav_color'));
    }

    public function test_the_nav_colour_is_saved_and_returned(): void
    {
        $this->as($this->me);
        $this->putJson('/api/notification-settings', ['nav_color' => '#0A66C2'])->assertOk()->assertJsonPath('data.nav_color', '#0a66c2');   // stored lower-case
        $this->assertSame('#0a66c2', $this->getJson('/api/notification-settings')->json('data.nav_color'));
    }

    public function test_only_a_real_hex_colour_is_accepted(): void
    {
        $this->as($this->me);
        foreach (['red', '#fff', '#12345g', '0a66c2', '#0a66c2;background:url(x)', 'url(javascript:alert(1))', '<script>', 123, true, ['#0a66c2']] as $bad) {
            $this->putJson('/api/notification-settings', ['nav_color' => $bad])->assertStatus(422);
        }
        $this->assertNull($this->getJson('/api/notification-settings')->json('data.nav_color'), 'nothing was saved by the refused requests');
    }

    public function test_null_puts_the_standard_look_back(): void
    {
        $this->as($this->me);
        $this->putJson('/api/notification-settings', ['nav_color' => '#004182'])->assertOk();
        $this->putJson('/api/notification-settings', ['nav_color' => null])->assertOk()->assertJsonPath('data.nav_color', null);
        $this->assertNull($this->getJson('/api/notification-settings')->json('data.nav_color'));
    }

    public function test_the_nav_colour_belongs_to_one_user_and_follows_them_to_every_device(): void
    {
        $this->as($this->me);
        $this->putJson('/api/notification-settings', ['nav_color' => '#1b8a4b'])->assertOk();

        $this->as($this->pal);
        $this->assertNull($this->getJson('/api/notification-settings')->json('data.nav_color'), "Pal's bars are unchanged");

        $this->me->tokens()->delete();                         // logout, then login on another device
        $this->as($this->me);
        $this->assertSame('#1b8a4b', $this->getJson('/api/notification-settings')->json('data.nav_color'));
    }

    public function test_changing_notification_switches_never_touches_the_colour(): void
    {
        $this->as($this->me);
        $this->putJson('/api/notification-settings', ['nav_color' => '#6a1b9a'])->assertOk();
        $this->putJson('/api/notification-settings', ['sound' => true, 'toast' => false])->assertOk();
        $this->putJson('/api/notification-settings', array_fill_keys(NotificationPrefs::BOOLEAN_KEYS, false))->assertOk();
        $this->assertSame('#6a1b9a', $this->getJson('/api/notification-settings')->json('data.nav_color'));
    }

    public function test_restoring_the_notification_defaults_keeps_the_colour(): void
    {
        $this->as($this->me);
        $this->putJson('/api/notification-settings', ['nav_color' => '#c62828', 'sound' => true])->assertOk();

        $res = $this->postJson('/api/notification-settings/reset')->assertOk();
        $this->assertSame('#c62828', $res->json('data.nav_color'), 'the colour is an appearance choice, not a notification setting');
        $this->assertFalse($res->json('data.sound'), 'but the notification settings really were reset');
        $this->assertSame('#c62828', $this->getJson('/api/notification-settings')->json('data.nav_color'));
    }
}
