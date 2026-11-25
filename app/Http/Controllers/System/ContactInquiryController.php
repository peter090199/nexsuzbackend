<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\ContactInquiry;
use App\Models\ContactInquiryMessage;
use App\Models\User;
use App\Services\ContactMailService;
use App\Support\HtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Contact Inquiries: read visitor messages from the Contact Us form, reply by email and keep the conversation.
 * Routes sit behind menuaccess:contactinquiries (Master Admin, or Admin when the menu is assigned to the role).
 * Replies always go to the address the visitor wrote from; the client can never choose another recipient.
 */
class ContactInquiryController extends Controller
{
    public const ATTACH_DISK = 'local';                       // private: never served from /storage
    private const MAX_FILES = 5;
    private const MAX_FILE_KB = 5120;
    private const MAX_TOTAL_BYTES = 15 * 1024 * 1024;
    private const EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'txt', 'csv'];

    public function __construct(private ContactMailService $mail) { }

    private function me(): ?string { return Auth::user()->code ?? null; }
    private function isMaster(): bool { return (Auth::user()->role_code ?? '') === 'DEF-MASTERADMIN'; }
    private function audit(string $action, ContactInquiry $i, array $extra = []): void
    {
        Log::info("contact-inquiry: {$action}", ['by' => $this->me(), 'inquiry' => $i->inquiry_no] + $extra);
    }

    private function unread(): int
    {
        return ContactInquiry::whereNull('read_at')->whereNull('archived_at')->count();
    }

    private function row(ContactInquiry $i, array $names = []): array
    {
        return [
            'id' => $i->id, 'number' => $i->inquiry_no, 'name' => $i->name, 'email' => $i->email, 'phone' => $i->phone, 'subject' => $i->subject,
            'preview' => Str::limit(preg_replace('/\s+/', ' ', $i->message), 120), 'status' => $i->status, 'unread' => $i->read_at === null,
            'assigned_code' => $i->assigned_code, 'assigned_name' => $i->assigned_code ? ($names[$i->assigned_code] ?? $i->assigned_code) : null,
            'archived' => $i->archived_at !== null, 'created_at' => $i->created_at?->toIso8601String(), 'last_activity_at' => ($i->last_activity_at ?? $i->created_at)?->toIso8601String(),
            'notified' => $i->notified_at !== null, 'notify_error' => $i->notify_error,
        ];
    }

    private function names(array $codes): array
    {
        $codes = array_values(array_filter(array_unique($codes)));

        return $codes ? User::whereIn('code', $codes)->pluck('fullname', 'code')->all() : [];
    }

    private function message(ContactInquiryMessage $m, array $names = []): array
    {
        return [
            'id' => $m->id, 'direction' => $m->direction, 'from_email' => $m->from_email, 'from_name' => $m->from_name, 'to_email' => $m->to_email, 'subject' => $m->subject,
            'body' => $m->body, 'is_html' => $m->is_html, 'status' => $m->status, 'error' => $m->error, 'created_at' => $m->created_at?->toIso8601String(),
            'sent_by' => $m->sent_by_code ? ($names[$m->sent_by_code] ?? $m->sent_by_code) : null,
            'attachments' => collect($m->attachments ?? [])->values()->map(fn ($a, $n) => ['index' => $n, 'name' => $a['name'], 'size' => $a['size'], 'mime' => $a['mime']])->all(),
        ];
    }

    private function detail(ContactInquiry $i): array
    {
        $i->load('messages');
        $names = $this->names([$i->assigned_code, ...$i->messages->pluck('sent_by_code')->all()]);

        return $this->row($i, $names) + ['message' => $i->message, 'messages' => $i->messages->map(fn ($m) => $this->message($m, $names))->all()];
    }

    /** GET contact-inquiries?q=&status=all|unread|new|read|replied|closed&archived=0|1&mine=0|1&page= */
    public function index(Request $request): JsonResponse
    {
        $v = Validator::make($request->query(), ['q' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:all,unread,new,read,replied,closed'],
            'archived' => ['nullable', 'boolean'], 'mine' => ['nullable', 'boolean'], 'per_page' => ['nullable', 'integer', 'between:5,50']]);
        if ($v->fails()) { return response()->json(['success' => false, 'message' => $v->errors()->first()], 422); }

        $q = ContactInquiry::query()->orderByDesc('last_activity_at')->orderByDesc('id');
        $request->boolean('archived') ? $q->whereNotNull('archived_at') : $q->whereNull('archived_at');
        $status = (string) $request->query('status', 'all');
        if ($status === 'unread') { $q->whereNull('read_at'); } elseif ($status !== 'all') { $q->where('status', $status); }
        if ($request->boolean('mine')) { $q->where('assigned_code', $this->me()); }
        if ($term = trim((string) $request->query('q', ''))) {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('subject', 'like', $like)
                ->orWhere('message', 'like', $like)->orWhere('inquiry_no', 'like', $like)->orWhere('phone', 'like', $like));
        }
        $page = $q->paginate((int) $request->query('per_page', 15));
        $names = $this->names($page->pluck('assigned_code')->all());

        return response()->json(['success' => true, 'data' => $page->getCollection()->map(fn ($i) => $this->row($i, $names))->all(),
            'meta' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()], 'unread' => $this->unread(),
            'counts' => ContactInquiry::whereNull('archived_at')->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status')->all()]);
    }

    /** GET contact-inquiries/unread-count : the sidebar badge */
    public function unreadCount(): JsonResponse
    {
        return response()->json(['success' => true, 'unread' => $this->unread()]);
    }

    /** GET contact-inquiries/{id} : opening an inquiry marks it read */
    public function show(int $id): JsonResponse
    {
        $i = ContactInquiry::findOrFail($id);
        if ($i->read_at === null) {
            $i->read_at = now();
            if ($i->status === 'new') { $i->status = 'read'; }
            $i->save();
        }

        return response()->json(['success' => true, 'data' => $this->detail($i), 'unread' => $this->unread()]);
    }

    /** POST contact-inquiries/{id}/read {read: bool} */
    public function setRead(Request $request, int $id): JsonResponse
    {
        $request->validate(['read' => ['required', 'boolean']]);
        $i = ContactInquiry::findOrFail($id);
        if ($request->boolean('read')) {
            $i->read_at = $i->read_at ?? now();
            if ($i->status === 'new') { $i->status = 'read'; }
        } else {
            $i->read_at = null;
            if ($i->status === 'read') { $i->status = 'new'; }
        }
        $i->save();

        return response()->json(['success' => true, 'data' => $this->detail($i), 'unread' => $this->unread()]);
    }

    /** POST contact-inquiries/{id}/status {action: close|reopen} */
    public function setStatus(Request $request, int $id): JsonResponse
    {
        $request->validate(['action' => ['required', 'in:close,reopen']]);
        $i = ContactInquiry::findOrFail($id);
        if ($request->input('action') === 'close') {
            $i->status = 'closed';
        } else {
            $i->status = $i->messages()->where('direction', 'out')->where('status', 'sent')->exists() ? 'replied' : ($i->read_at ? 'read' : 'new');
        }
        $i->save();
        $this->audit($request->input('action'), $i);

        return response()->json(['success' => true, 'message' => $request->input('action') === 'close' ? 'Inquiry closed.' : 'Inquiry reopened.', 'data' => $this->detail($i), 'unread' => $this->unread()]);
    }

    /** POST contact-inquiries/{id}/assign {mine: bool} : take or release the inquiry */
    public function assign(Request $request, int $id): JsonResponse
    {
        $request->validate(['mine' => ['required', 'boolean']]);
        $i = ContactInquiry::findOrFail($id);
        $i->assigned_code = $request->boolean('mine') ? $this->me() : null;
        $i->save();
        $this->audit($request->boolean('mine') ? 'assigned' : 'unassigned', $i);

        return response()->json(['success' => true, 'data' => $this->detail($i), 'unread' => $this->unread()]);
    }

    /** POST contact-inquiries/{id}/reply (multipart: subject, body (rich text), attachments[]) */
    public function reply(Request $request, int $id): JsonResponse
    {
        $i = ContactInquiry::findOrFail($id);
        $v = Validator::make($request->all(), [
            'subject' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:20000'],
            'attachments' => ['nullable', 'array', 'max:' . self::MAX_FILES],
            'attachments.*' => ['file', 'max:' . self::MAX_FILE_KB, 'mimes:' . implode(',', self::EXT)],
        ], [
            'attachments.max' => 'You can attach up to ' . self::MAX_FILES . ' files.',
            'attachments.*.max' => 'Each attachment must be 5 MB or smaller.',
            'attachments.*.mimes' => 'Attachments can be PDF, Word, Excel, PowerPoint, images, TXT or CSV files.',
            'attachments.*.uploaded' => 'An attachment could not be uploaded. Check the file size (5 MB maximum).',
        ]);
        if ($v->fails()) { return response()->json(['success' => false, 'message' => $v->errors()->first(), 'errors' => $v->errors()], 422); }

        $html = HtmlSanitizer::clean((string) $request->input('body'));
        if (trim(HtmlSanitizer::toText($html)) === '') {
            return response()->json(['success' => false, 'message' => 'Write a message before sending.', 'errors' => ['body' => ['empty']]], 422);
        }
        if (!filter_var($i->email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'message' => 'The visitor\'s email address is not valid, so a reply cannot be sent.'], 422);
        }
        $files = $request->file('attachments', []);
        if (array_sum(array_map(fn ($f) => $f->getSize(), $files)) > self::MAX_TOTAL_BYTES) {
            return response()->json(['success' => false, 'message' => 'The attachments together must be 15 MB or smaller.'], 422);
        }

        // keep the files on the private disk, one folder per message
        $folder = "contact-inquiries/{$i->id}/" . Str::uuid();
        $stored = [];
        foreach ($files as $f) {
            $name = Str::limit(preg_replace('/[^A-Za-z0-9._ \-]/', '_', pathinfo($f->getClientOriginalName(), PATHINFO_FILENAME)), 80, '') . '.' . strtolower($f->getClientOriginalExtension());
            $path = $f->storeAs($folder, Str::random(8) . '-' . str_replace(' ', '_', $name), self::ATTACH_DISK);
            $stored[] = ['name' => $name, 'size' => $f->getSize(), 'mime' => $f->getMimeType() ?: 'application/octet-stream', 'path' => $path];
        }

        $subject = trim((string) $request->input('subject'));
        $result = $this->mail->deliverReply($i->email, $i->name, $subject, $html, $stored);

        $msg = ContactInquiryMessage::create([
            'inquiry_id' => $i->id, 'direction' => 'out', 'from_email' => (string) config('mail.contact_from.address'), 'from_name' => (string) config('mail.contact_from.name'),
            'to_email' => $i->email, 'subject' => $subject, 'body' => $html, 'is_html' => true, 'status' => $result['ok'] ? 'sent' : 'failed',
            'error' => $result['ok'] ? null : $result['message'], 'sent_by_code' => $this->me(), 'attachments' => $stored ?: null,
        ]);
        $i->last_activity_at = now();
        if ($result['ok']) {            // only a delivered reply moves the inquiry to Replied
            $i->read_at = $i->read_at ?? now();
            if ($i->status !== 'closed') { $i->status = 'replied'; }
        }
        $i->save();
        $this->audit($result['ok'] ? 'reply sent' : 'reply failed', $i, ['message' => $msg->id, 'attachments' => count($stored)]);

        return response()->json(['success' => $result['ok'], 'message' => $result['ok'] ? 'Reply sent to ' . $i->email . '.' : $result['message'],
            'data' => $this->detail($i), 'unread' => $this->unread()], $result['ok'] ? 200 : 502);
    }

    /** GET contact-inquiries/{id}/messages/{messageId}/attachments/{index} : authorised download from the private disk */
    public function attachment(int $id, int $messageId, int $index)
    {
        $m = ContactInquiryMessage::where('inquiry_id', $id)->findOrFail($messageId);
        $a = ($m->attachments ?? [])[$index] ?? null;
        if (!$a || !Storage::disk(self::ATTACH_DISK)->exists($a['path'])) {
            return response()->json(['success' => false, 'message' => 'The attachment is no longer available.'], 404);
        }

        return Storage::disk(self::ATTACH_DISK)->download($a['path'], $a['name']);
    }

    /** DELETE contact-inquiries/{id} : archive (reversible). With ?permanent=1 (Master Admin, archived only) it is removed for good. */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $i = ContactInquiry::findOrFail($id);
        if ($request->boolean('permanent')) {
            if (!$this->isMaster()) { return response()->json(['success' => false, 'message' => 'Only a Master Admin can delete an inquiry permanently.'], 403); }
            if ($i->archived_at === null) { return response()->json(['success' => false, 'message' => 'Archive the inquiry first, then delete it permanently.'], 422); }
            foreach ($i->messages as $m) {
                foreach (($m->attachments ?? []) as $a) { Storage::disk(self::ATTACH_DISK)->delete($a['path']); }
            }
            Storage::disk(self::ATTACH_DISK)->deleteDirectory("contact-inquiries/{$i->id}");
            $this->audit('deleted permanently', $i, ['from' => $i->email]);
            $i->delete();

            return response()->json(['success' => true, 'message' => 'Inquiry deleted permanently.', 'unread' => $this->unread()]);
        }
        $i->archived_at = now();
        $i->archived_by = $this->me();
        $i->save();
        $this->audit('archived', $i);

        return response()->json(['success' => true, 'message' => 'Inquiry archived.', 'data' => $this->detail($i), 'unread' => $this->unread()]);
    }

    /** POST contact-inquiries/{id}/restore */
    public function restore(int $id): JsonResponse
    {
        $i = ContactInquiry::findOrFail($id);
        $i->archived_at = null;
        $i->archived_by = null;
        $i->save();
        $this->audit('restored', $i);

        return response()->json(['success' => true, 'message' => 'Inquiry restored.', 'data' => $this->detail($i), 'unread' => $this->unread()]);
    }
}
