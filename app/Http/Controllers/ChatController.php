<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageSent;
use App\Models\Message;
use App\Events\NotificationCountUpdated;
use App\Http\Controllers\Jobs\AppliedJobController;

class ChatController extends Controller
{
    
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'receiver_id' => ['required', 'integer', 'exists:users,id', \Illuminate\Validation\Rule::notIn([Auth::id()])],
        ], [
            'receiver_id.exists' => 'That person could not be found.',
            'receiver_id.not_in' => 'You cannot message yourself.',
        ]);
        // blocked accounts cannot message each other (either direction); old messages are kept
        $receiverCode = DB::table('users')->where('id', $request->receiver_id)->value('code');
        if (\App\Services\BlockService::isBlocked(Auth::user()->code, $receiverCode)) {
            return \App\Services\BlockService::denied();
        }

        $codeuser = Auth::user()->code;
        $message = Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'created_at' => now(),
            'code' => $codeuser,
            'is_read' => false
        ]);

          // Get unread count for the receiver
            $unreadCount = Message::where('receiver_id', $request->receiver_id)
            ->where('is_read', false)
            ->count();
        
            // The receiver's notification preferences decide whether anything is pushed to their browser at all:
            // it is needed for new-message notifications and for the unread badge. If both are off, nothing is sent.
            // (The message itself is already saved, and is always in their inbox.)
            if (\App\Helpers\NotificationPrefs::wantsMessagePush((int) $request->receiver_id)) {
                // best effort: no internet / Pusher unreachable must not turn a successful send into an error
                try {
                    broadcast(new NotificationCountUpdated($request->receiver_id, $unreadCount))->toOthers();
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Message broadcast failed: ' . $e->getMessage());
                }
            }
        
        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully!',
            'data' => $message
        ], 200);
    }


  // ✅ Fetch chat messages
  public function fetchMessages($receiverId) {
        $userId = Auth::id();
        $messages = Message::where(function($query) use ($userId, $receiverId) {
                $query->where('sender_id', $userId)->where('receiver_id', $receiverId);
            })->orWhere(function($query) use ($userId, $receiverId) {
                $query->where('sender_id', $receiverId)->where('receiver_id', $userId);
            })->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }



    public function fetchMessagesx($receiverId)
    {
        $messages = DB::table('messages')
            ->where(function ($query) use ($receiverId) {
                $query->where('sender_id', Auth::id())
                      ->where('receiver_id', $receiverId);
            })
            ->orWhere(function ($query) use ($receiverId) {
                $query->where('sender_id', $receiverId)
                      ->where('receiver_id', Auth::id());
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    //DISPLAY CHAT ONLINE MESSAGES
    public function getActiveUsers()
    {
        $users = DB::table('users')
            ->leftJoin('userprofiles', 'userprofiles.code', '=', 'users.code')
            ->select('users.id', 'users.code', 'users.status', 'users.fullname', 'userprofiles.photo_pic','users.is_online')
            ->where('users.status', 'A')
            ->whereNotIn('users.code', \App\Services\BlockService::relatedCodes(Auth::user()->code))
            ->get();

        return response()->json($users);
    }

    
    public function testBroadcast()
    {
        event(new NotificationCountUpdated(auth()->id(), 5));
        return response()->json(['message' => 'Broadcast sent']);
    }
    
    public function updateNotificationCount()
    {
        $userId = Auth::id();
    
        $unreadCount = Message::where('receiver_id', $userId)
            ->where('is_read', false)
            ->count();  
    
        // The count is already known; pushing it to other tabs/devices is best-effort.
        // If the broadcaster is unreachable (no internet, bad SSL/CA bundle, wrong keys) the
        // request must still succeed instead of failing with a 500.
        try {
            broadcast(new NotificationCountUpdated($userId, $unreadCount));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Notification count broadcast failed: ' . $e->getMessage());
        }
    
        return response()->json([
            'unreadCount' => $unreadCount
        ]);
    }


    public function updateNotificationCountxx()
    {
        $userId = Auth::id();
        $unreadCount = Message::where('receiver_id', $userId)
            ->where('is_read', false)
            ->count();  

        // Assuming the latest message for this user (adjust as necessary)
            // $message = Message::where('receiver_id', $userId)
            // ->latest()
            // ->first();

         broadcast(new NotificationCountUpdated($unreadCount));
        return response()->json([
            'unreadCount' => $unreadCount
        ]);
    }

    public function getNotificationsIsRead()
    {
        $userId = Auth::id();
            $notifications = DB::table('messages')
            ->join('users', 'messages.sender_id', '=', 'users.id')
            ->join('userprofiles', 'users.code', '=', 'userprofiles.code')
            ->where('messages.receiver_id', $userId)
            ->where('messages.is_read', true)
            ->orderByDesc('messages.created_at')
            ->select('messages.*', 'userprofiles.photo_pic','users.fullname')
            ->get();
    
        return response()->json([
            'notifications' => $notifications
        ]);
    }
    
    public function getNotificationsIsUnRead()
    {
        $userId = Auth::id();
            $notifications = DB::table('messages')
            ->join('users', 'messages.sender_id', '=', 'users.id')
            ->join('userprofiles', 'users.code', '=', 'userprofiles.code')
            ->where('messages.receiver_id', $userId)
            ->where('messages.is_read', false)
            ->orderByDesc('messages.created_at')
            ->select('messages.*', 'userprofiles.photo_pic','users.fullname')
            ->get();

    
        return response()->json([
            'notifications' => $notifications
        ]);
    }


      // ✅ Mark messages as read
      public function markAsRead(Request $request) {
        $query = Message::where('receiver_id', Auth::id());      // only ever the caller's own incoming messages

        if ($request->filled('sender_id')) {
            $query->where('sender_id', (int) $request->sender_id)->where('is_read', false);   // the whole conversation
        } else {
            $query->where('id', $request->id);                                                   // one message (as before)
        }
        $updated = $query->update(['is_read' => true]);

        return response()->json(['message' => 'Messages marked as read', 'updated' => $updated]);
    }

    /**
     * GET conversations: everyone the signed-in user has exchanged messages with, most recent first.
     * Each item carries the other person, the last message, the unread count and their online state.
     * Read-only; built from the existing messages / users / userprofiles tables.
     */
    public function conversations()
    {
        $me = Auth::id();

        $rows = DB::select("
            SELECT t.peer_id, t.last_id, t.unread
            FROM (
                SELECT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS peer_id,
                       MAX(id) AS last_id,
                       SUM(CASE WHEN receiver_id = ? AND is_read = 0 THEN 1 ELSE 0 END) AS unread
                FROM messages
                WHERE sender_id = ? OR receiver_id = ?
                GROUP BY peer_id
            ) t
            WHERE t.peer_id <> ?
            ORDER BY t.last_id DESC
        ", [$me, $me, $me, $me, $me]);

        $peerIds = array_map(fn ($r) => (int) $r->peer_id, $rows);
        $lastIds = array_map(fn ($r) => (int) $r->last_id, $rows);

        $messages = $lastIds ? DB::table('messages')->whereIn('id', $lastIds)->get(['id', 'sender_id', 'receiver_id', 'message', 'is_read', 'created_at'])->keyBy('id') : collect();
        $users = $peerIds ? DB::table('users')->whereIn('id', $peerIds)
            ->select('id', 'code', 'fullname', 'is_online', 'status',
                DB::raw('(SELECT photo_pic FROM userprofiles WHERE userprofiles.code = users.code LIMIT 1) AS photo_pic'))
            ->get()->keyBy('id') : collect();

        $data = [];
        $unreadTotal = 0;
        foreach ($rows as $r) {
            $u = $users[(int) $r->peer_id] ?? null;
            $m = $messages[(int) $r->last_id] ?? null;
            if (!$u || !$m) { continue; }
            $unreadTotal += (int) $r->unread;
            $data[] = [
                'user_id' => (int) $u->id,
                'blocked' => \App\Services\BlockService::isBlocked(Auth::user()->code, $u->code),   // UI: history stays, sending is off
                'code' => $u->code,
                'fullname' => $u->fullname,
                'photo_pic' => $u->photo_pic,
                'is_online' => (bool) $u->is_online,
                'unread_count' => (int) $r->unread,
                'last_message' => [
                    'id' => (int) $m->id,
                    'message' => $m->message,
                    'sender_id' => (int) $m->sender_id,
                    'is_read' => (bool) $m->is_read,
                    'created_at' => $m->created_at,
                ],
            ];
        }

        return response()->json(['success' => true, 'data' => $data, 'unread_total' => $unreadTotal]);
    }

    public function markAsReadMessage() {
        $codeuser = Auth::user()->code;

        Message::where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Messages marked as read']);
    }


    public function markAllAsRead(Request $request)
    {
        Message::where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'All messages marked as read']);
    }

    //meesages receive
    public function messages_receive($receiverId) 
    {
        $userId = Auth::id();

        $messages = DB::table('messages')
            ->select('messages.*')
            ->where(function($query) use ($userId, $receiverId) {
                $query->where('sender_id', $userId)
                    ->where('receiver_id', $receiverId);
            })
            ->orWhere(function($query) use ($userId, $receiverId) {
                $query->where('sender_id', $receiverId)
                    ->where('receiver_id', $userId);
            })
            ->orderBy('created_at', 'desc') // latest first
            ->groupBy('sender_id') // group by sender_id
            ->get();

        return response()->json($messages);
    }



    //mesages

    public function getMessagesAll()
    {
        $userId = Auth::id();
    
        $notifications = DB::table('messages')
            ->join('users', 'messages.sender_id', '=', 'users.id')
            ->join('userprofiles', 'users.code', '=', 'userprofiles.code')
            ->where('messages.receiver_id', $userId)
            ->where('messages.is_read', false)
            ->select(
                'messages.sender_id',
                DB::raw('MAX(messages.id) as latest_message_id'),
                DB::raw('MAX(messages.message) as message'),
                DB::raw('MAX(messages.created_at) as created_at'),
                'userprofiles.photo_pic',
                'users.fullname'
            )
            ->groupBy('messages.sender_id', 'userprofiles.photo_pic', 'users.fullname')
            ->orderByDesc('created_at')
            ->get();
    
        return response()->json([
            'notifications' => $notifications
        ]);
    }
    
    
}
