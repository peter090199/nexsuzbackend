<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The messaging API behind /message: conversation list, history, send, read state, unread counts, search,
 * permissions and validation. Rolled-back transactions; broadcasting is switched to the log driver so
 * no test ever talks to Pusher.
 */
class ChatApiTest extends TestCase
{
    use DatabaseTransactions;

    private User $alice;
    private User $bob;
    private User $carol;
    private User $dave;   // never talks to anyone

    protected function setUp(): void
    {
        parent::setUp();
        config(['broadcasting.default' => 'log']);

        $this->alice = $this->makeUser('Alice');
        $this->bob = $this->makeUser('Bob', true);
        $this->carol = $this->makeUser('Carol');
        $this->dave = $this->makeUser('Dave');

        DB::table('userprofiles')->insert([
            'code' => $this->bob->code, 'transNo' => 990001, 'photo_pic' => 'http://localhost/storage/uploads/bob.png',
            'email' => 'bobprofile' . $this->bob->code . '@example.test', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function makeUser(string $name, bool $online = false): User
    {
        static $next = 870000;
        $code = $next += random_int(1, 40);

        return User::forceCreate([
            'fname' => $name, 'lname' => 'Tester', 'fullname' => "$name Tester",
            'email' => strtolower($name) . $code . '@example.test', 'password' => bcrypt('secret-pass'),
            'status' => 'A', 'code' => $code, 'coverphoto' => '', 'is_online' => $online ? 1 : 0,
        ]);
    }

    private function as(User $user): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($user);
    }

    private function say(User $from, User $to, string $text, ?string $at = null, bool $read = false): int
    {
        return DB::table('messages')->insertGetId([
            'code' => $from->code, 'sender_id' => $from->id, 'receiver_id' => $to->id, 'message' => $text,
            'is_read' => $read ? 1 : 0, 'created_at' => $at ?? now(), 'updated_at' => now(),
        ]);
    }

    // ------------------------------------------------------------------ authentication

    public function test_guests_are_refused_everywhere(): void
    {
        $this->getJson('/api/conversations')->assertUnauthorized();
        $this->getJson('/api/receivemessages/1')->assertUnauthorized();
        $this->postJson('/api/send-message', ['receiver_id' => 1, 'message' => 'x'])->assertUnauthorized();
        $this->postJson('/api/messages/read', ['id' => 1])->assertUnauthorized();
        $this->getJson('/api/getActiveUsers')->assertUnauthorized();
        $this->getJson('/api/update_count')->assertUnauthorized();
    }

    // ------------------------------------------------------------------ conversation list

    public function test_conversations_list_the_last_message_unread_count_and_person(): void
    {
        $this->say($this->carol, $this->alice, 'older chat', '2026-01-01 09:00:00', true);
        $this->say($this->bob, $this->alice, 'hi alice', '2026-01-01 10:00:00');
        $this->say($this->alice, $this->bob, 'hi bob', '2026-01-01 10:01:00', true);
        $this->say($this->bob, $this->alice, 'are you there?', '2026-01-01 10:02:00');

        $this->as($this->alice);
        $res = $this->getJson('/api/conversations')->assertOk()->assertJsonPath('success', true);
        $rows = $res->json('data');

        $this->assertCount(2, $rows, 'one row per person, never one per message');
        $this->assertSame($this->bob->id, $rows[0]['user_id'], 'most recent conversation first');
        $this->assertSame('Bob Tester', $rows[0]['fullname']);
        $this->assertSame('are you there?', $rows[0]['last_message']['message']);
        $this->assertSame(2, $rows[0]['unread_count']);
        $this->assertTrue($rows[0]['is_online']);
        $this->assertSame('http://localhost/storage/uploads/bob.png', $rows[0]['photo_pic']);
        $this->assertSame(0, $rows[1]['unread_count']);
        $this->assertNull($rows[1]['photo_pic']);
        $this->assertSame(2, $res->json('unread_total'));
    }

    public function test_conversations_only_include_my_own_chats(): void
    {
        $this->say($this->bob, $this->carol, 'secret between bob and carol');

        $this->as($this->alice);
        $this->assertSame([], $this->getJson('/api/conversations')->assertOk()->json('data'));
        $this->as($this->dave);
        $this->assertSame([], $this->getJson('/api/conversations')->assertOk()->json('data'));
    }

    public function test_an_empty_inbox_is_an_empty_list_not_an_error(): void
    {
        $this->as($this->dave);
        $this->getJson('/api/conversations')->assertOk()->assertJsonPath('data', [])->assertJsonPath('unread_total', 0);
    }

    // ------------------------------------------------------------------ history

    public function test_history_is_oldest_first_and_only_between_the_two_people(): void
    {
        $this->say($this->alice, $this->bob, 'one', '2026-01-01 10:00:00');
        $this->say($this->bob, $this->alice, 'two', '2026-01-01 10:01:00');
        $this->say($this->alice, $this->carol, 'not for bob', '2026-01-01 10:02:00');
        $this->say($this->carol, $this->bob, 'not for alice', '2026-01-01 10:03:00');

        $this->as($this->alice);
        $texts = collect($this->getJson('/api/receivemessages/' . $this->bob->id)->assertOk()->json())->pluck('message')->all();
        $this->assertSame(['one', 'two'], $texts);

        // a third person cannot read it by asking for the same ids: the server always scopes to the caller
        $this->as($this->dave);
        $this->assertSame([], $this->getJson('/api/receivemessages/' . $this->bob->id)->assertOk()->json());
    }

    // ------------------------------------------------------------------ send

    public function test_sending_saves_the_message_for_the_right_people(): void
    {
        $this->as($this->alice);
        $res = $this->postJson('/api/send-message', ['receiver_id' => $this->bob->id, 'message' => 'Hello Bob'])->assertOk();

        $res->assertJsonPath('success', true)->assertJsonPath('data.message', 'Hello Bob');
        $row = DB::table('messages')->where('id', $res->json('data.id'))->first();
        $this->assertSame($this->alice->id, (int) $row->sender_id);
        $this->assertSame($this->bob->id, (int) $row->receiver_id);
        $this->assertSame(0, (int) $row->is_read, 'a new message is unread for the receiver');

        // and the receiver sees it, with the unread count going up
        $this->as($this->bob);
        $this->assertSame(1, $this->getJson('/api/update_count')->assertOk()->json('unreadCount'));
        $this->assertSame('Hello Bob', $this->getJson('/api/conversations')->json('data.0.last_message.message'));
    }

    public function test_sending_validates_the_message_and_the_receiver(): void
    {
        $this->as($this->alice);
        $this->postJson('/api/send-message', ['receiver_id' => $this->bob->id, 'message' => ''])->assertStatus(422);
        $this->postJson('/api/send-message', ['receiver_id' => $this->bob->id])->assertStatus(422);
        $this->postJson('/api/send-message', ['message' => 'no receiver'])->assertStatus(422);
        $this->postJson('/api/send-message', ['receiver_id' => 99999999, 'message' => 'ghost'])->assertStatus(422)
            ->assertJsonValidationErrors('receiver_id');
        $this->postJson('/api/send-message', ['receiver_id' => $this->alice->id, 'message' => 'to myself'])->assertStatus(422)
            ->assertJsonPath('errors.receiver_id.0', 'You cannot message yourself.');
        $this->postJson('/api/send-message', ['receiver_id' => $this->bob->id, 'message' => str_repeat('x', 5001)])->assertStatus(422);

        $this->assertSame(0, DB::table('messages')->where('sender_id', $this->alice->id)->count(), 'nothing was saved by the rejected requests');
    }

    public function test_the_longest_allowed_message_and_emoji_are_stored_intact(): void
    {
        $this->as($this->alice);
        $text = "Line one\nLine two 😀👍 " . str_repeat('x', 4900);
        $id = $this->postJson('/api/send-message', ['receiver_id' => $this->bob->id, 'message' => $text])->assertOk()->json('data.id');
        $this->assertSame($text, DB::table('messages')->where('id', $id)->value('message'));
    }

    public function test_a_failing_broadcaster_never_turns_a_saved_message_into_an_error(): void
    {
        // point the real Pusher driver at a closed local port: the broadcast fails, the send must still succeed
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'k', 'broadcasting.connections.pusher.secret' => 's', 'broadcasting.connections.pusher.app_id' => '1',
            'broadcasting.connections.pusher.options' => ['host' => '127.0.0.1', 'port' => 9, 'scheme' => 'http', 'useTLS' => false, 'timeout' => 1],
        ]);
        app(BroadcastManager::class)->purge('pusher');

        $this->as($this->alice);
        $this->postJson('/api/send-message', ['receiver_id' => $this->bob->id, 'message' => 'still delivered'])->assertOk()->assertJsonPath('success', true);
        $this->assertSame(1, DB::table('messages')->where('message', 'still delivered')->count());
    }

    // ------------------------------------------------------------------ read state and unread counts

    public function test_opening_a_conversation_marks_only_that_persons_messages_read(): void
    {
        $this->say($this->bob, $this->alice, 'b1');
        $this->say($this->bob, $this->alice, 'b2');
        $this->say($this->carol, $this->alice, 'c1');
        $mine = $this->say($this->alice, $this->bob, 'my own message');

        $this->as($this->alice);
        $this->postJson('/api/messages/read', ['sender_id' => $this->bob->id])->assertOk()->assertJsonPath('updated', 2);

        $this->assertSame(0, DB::table('messages')->where('sender_id', $this->bob->id)->where('receiver_id', $this->alice->id)->where('is_read', 0)->count());
        $this->assertSame(1, DB::table('messages')->where('sender_id', $this->carol->id)->where('is_read', 0)->count(), "Carol's stays unread");
        $this->assertSame(0, (int) DB::table('messages')->where('id', $mine)->value('is_read'), "my own message to Bob is Bob's to read");
        $this->assertSame(1, $this->getJson('/api/update_count')->json('unreadCount'));
    }

    public function test_one_message_can_still_be_marked_read_by_id(): void
    {
        $a = $this->say($this->bob, $this->alice, 'one');
        $b = $this->say($this->bob, $this->alice, 'two');

        $this->as($this->alice);
        $this->postJson('/api/messages/read', ['id' => $a])->assertOk();
        $this->assertSame(1, (int) DB::table('messages')->where('id', $a)->value('is_read'));
        $this->assertSame(0, (int) DB::table('messages')->where('id', $b)->value('is_read'));
    }

    public function test_you_cannot_mark_someone_elses_messages_as_read(): void
    {
        $toBob = $this->say($this->carol, $this->bob, 'for bob only');

        $this->as($this->alice);
        $this->postJson('/api/messages/read', ['id' => $toBob])->assertOk();
        $this->postJson('/api/messages/read', ['sender_id' => $this->carol->id])->assertOk();
        $this->assertSame(0, (int) DB::table('messages')->where('id', $toBob)->value('is_read'));
    }

    public function test_the_unread_lists_and_counts_match_the_conversation_list(): void
    {
        DB::table('userprofiles')->insert([
            'code' => $this->carol->code, 'transNo' => 990002, 'photo_pic' => null,
            'email' => 'carolprofile' . $this->carol->code . '@example.test', 'created_at' => now(), 'updated_at' => now(),
        ]);   // (getMessagesAll inner-joins userprofiles: it only lists senders that have a profile row)
        $this->say($this->bob, $this->alice, 'unread 1');
        $this->say($this->carol, $this->alice, 'unread 2');
        $this->say($this->carol, $this->alice, 'already read', null, true);

        $this->as($this->alice);
        $this->assertSame(2, $this->getJson('/api/update_count')->json('unreadCount'));
        $this->assertSame(2, $this->getJson('/api/conversations')->json('unread_total'));
        $this->assertCount(2, $this->getJson('/api/getMessagesAll')->json('notifications'));
    }

    public function test_mark_all_read_clears_everything_for_me_only(): void
    {
        $this->say($this->bob, $this->alice, 'a');
        $this->say($this->carol, $this->alice, 'b');
        $this->say($this->alice, $this->carol, 'c');

        $this->as($this->alice);
        $this->postJson('/api/messages/mark_allAsread')->assertOk();
        $this->assertSame(0, $this->getJson('/api/update_count')->json('unreadCount'));
        $this->as($this->carol);
        $this->assertSame(1, $this->getJson('/api/update_count')->json('unreadCount'), "Carol's unread message from Alice is untouched");
    }

    // ------------------------------------------------------------------ people and search

    public function test_active_users_lists_people_with_photo_and_online_state(): void
    {
        $this->as($this->alice);
        $users = collect($this->getJson('/api/getActiveUsers')->assertOk()->json());
        $bob = $users->firstWhere('id', $this->bob->id);

        $this->assertNotNull($bob);
        $this->assertSame('Bob Tester', $bob['fullname']);
        $this->assertSame('http://localhost/storage/uploads/bob.png', $bob['photo_pic']);
        $this->assertEquals(1, $bob['is_online']);
        $this->assertArrayNotHasKey('password', $bob);
        $this->assertArrayNotHasKey('email', $bob);
    }

    public function test_searching_people_by_name(): void
    {
        $this->as($this->alice);
        $res = $this->getJson('/api/searchUsers?search=Carol')->assertOk()->assertJsonPath('success', true);
        $names = collect($res->json('online'))->merge($res->json('offline'))->pluck('fullname')->all();
        $this->assertContains('Carol Tester', $names);
        $this->assertNotContains('Bob Tester', $names, 'only people matching the search');
    }
}
