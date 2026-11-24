<?php

namespace App\Http\Controllers;

use App\Helpers\ReactionSummary;
use App\Services\AnnouncementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Announcements API.
 *   GET    announcements                 the live ones, for everybody signed in (the Home feed)
 *   GET    announcements/manage          administrators: management list (+ counts)
 *   GET    announcements/{id}            one announcement the caller may see
 *   POST   announcements                 administrators: create (draft or published), multipart
 *   POST   announcements/{id}/update     the author: edit, multipart
 *   POST   announcements/{id}/status     author or master admin: publish / archive / restore
 *   DELETE announcements/{id}            author or master admin
 *   POST   announcements/{id}/react      DELETE announcements/{id}/react   reactions
 * Writing is limited to DEF-ADMIN and DEF-MASTERADMIN by the `manage-announcements` gate; reading is limited by
 * AnnouncementService::canView. Neither depends on the Angular app.
 */
class AnnouncementController extends Controller
{
    public function __construct(private AnnouncementService $svc) {}

    private function fail(string $message, int $status = 422, array $errors = [])
    {
        return response()->json(array_filter(['success' => false, 'message' => $message, 'errors' => $errors ?: null]), $status);
    }

    private function rules(bool $creating): array
    {
        return [
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:200'],
            'content' => [$creating ? 'required' : 'sometimes', 'string', 'max:10000'],
            'priority' => ['sometimes', Rule::in(AnnouncementService::PRIORITIES)],
            'is_pinned' => ['sometimes', 'boolean'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
            'status' => $creating ? ['sometimes', Rule::in([AnnouncementService::DRAFT, AnnouncementService::PUBLISHED])] : ['prohibited'],
            'images' => ['sometimes', 'array', 'max:10'],
            'images.*' => ['file', 'mimes:jpeg,png,jpg,gif,webp', 'max:3000'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip', 'max:10240'],
            'remove_attachments' => ['sometimes', 'array', 'max:50'],
            'remove_attachments.*' => ['integer'],
        ];
    }

    private const MESSAGES = [
        'images.*.mimes' => 'Images must be JPG, PNG, GIF or WEBP.', 'images.*.max' => 'Each image must be 3 MB or smaller.',
        'attachments.*.mimes' => 'Attachments must be PDF, Word, Excel, PowerPoint, TXT, CSV or ZIP files.', 'attachments.*.max' => 'Each attachment must be 10 MB or smaller.',
        'images.max' => 'You can attach up to 10 images.', 'attachments.max' => 'You can attach up to 5 files.',
    ];

    // ------------------------------------------------------------------ reading

    public function feed()
    {
        return response()->json(['success' => true, 'data' => $this->svc->feed(Auth::user())]);
    }

    public function manage(Request $request)
    {
        Gate::authorize('manage-announcements');

        return response()->json(['success' => true, 'data' => $this->svc->manage(Auth::user(), $request->query('status')), 'counts' => $this->svc->counts(Auth::user())]);
    }

    public function show(int $id)
    {
        $a = $this->svc->find($id);
        // a draft or archived one answers 404, not 403, so its existence is not revealed
        if (!$a || !$this->svc->canView($a, Auth::user())) { return $this->fail('Announcement not found.', 404); }

        return response()->json(['success' => true, 'data' => $this->svc->card($a, Auth::user())]);
    }

    // ------------------------------------------------------------------ writing

    public function store(Request $request)
    {
        Gate::authorize('manage-announcements');
        $v = Validator::make($request->all(), $this->rules(true), self::MESSAGES);
        if ($v->fails()) { return $this->fail($v->errors()->first(), 422, $v->errors()->toArray()); }

        try {
            $a = $this->svc->create(Auth::user(), $v->validated() + ['is_pinned' => $request->boolean('is_pinned')], $request->file('images', []), $request->file('attachments', []));
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage(), 422, ['expires_at' => [$e->getMessage()]]);
        }

        return response()->json(['success' => true, 'message' => $a->status === AnnouncementService::PUBLISHED ? 'Announcement published.' : 'Draft saved.', 'data' => $this->svc->card($a, Auth::user())], 201);
    }

    public function update(Request $request, int $id)
    {
        Gate::authorize('manage-announcements');
        $a = $this->svc->find($id);
        if (!$a || !$this->svc->canView($a, Auth::user())) { return $this->fail('Announcement not found.', 404); }
        if (!$this->svc->canEdit($a, Auth::user())) { return $this->fail('You can only edit your own announcements.', 403); }

        $v = Validator::make($request->all(), $this->rules(false), self::MESSAGES);
        if ($v->fails()) { return $this->fail($v->errors()->first(), 422, $v->errors()->toArray()); }
        $data = $v->validated();
        if ($request->has('is_pinned')) { $data['is_pinned'] = $request->boolean('is_pinned'); }

        try {
            $a = $this->svc->update(Auth::user(), $a, $data, $request->file('images', []), $request->file('attachments', []), $data['remove_attachments'] ?? []);
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage(), 422, ['expires_at' => [$e->getMessage()]]);
        }

        return response()->json(['success' => true, 'message' => 'Announcement updated.', 'data' => $this->svc->card($a, Auth::user())]);
    }

    public function setStatus(Request $request, int $id)
    {
        Gate::authorize('manage-announcements');
        $request->validate(['status' => ['required', Rule::in([AnnouncementService::DRAFT, AnnouncementService::PUBLISHED, AnnouncementService::ARCHIVED])]]);
        $a = $this->svc->find($id);
        if (!$a || !$this->svc->canView($a, Auth::user())) { return $this->fail('Announcement not found.', 404); }

        $to = $request->input('status');
        // publishing and restoring are the author's; archiving is also the master admin's (moderation)
        $allowed = $to === AnnouncementService::ARCHIVED ? $this->svc->canModerate($a, Auth::user()) : $this->svc->canEdit($a, Auth::user());
        if (!$allowed) { return $this->fail($to === AnnouncementService::ARCHIVED ? 'You can only archive your own announcements.' : 'You can only publish or restore your own announcements.', 403); }

        try {
            $a = $this->svc->setStatus(Auth::user(), $a, $to);
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage(), 422);
        }
        $word = ['published' => 'published', 'archived' => 'archived', 'draft' => 'moved back to drafts'][$to];

        return response()->json(['success' => true, 'message' => "Announcement {$word}.", 'data' => $this->svc->card($a, Auth::user())]);
    }

    public function destroy(int $id)
    {
        Gate::authorize('manage-announcements');
        $a = $this->svc->find($id);
        if (!$a || !$this->svc->canView($a, Auth::user())) { return $this->fail('Announcement not found.', 404); }
        if (!$this->svc->canModerate($a, Auth::user())) { return $this->fail('You can only delete your own announcements.', 403); }
        $this->svc->delete($a);

        return response()->json(['success' => true, 'message' => 'Announcement deleted.']);
    }

    // ------------------------------------------------------------------ reactions

    public function react(Request $request, int $id)
    {
        $request->validate(['reaction' => ['required', 'string', Rule::in(ReactionSummary::ALLOWED)]]);
        $a = $this->svc->find($id);
        if (!$a || !$this->svc->canView($a, Auth::user())) { return $this->fail('Announcement not found.', 404); }

        return response()->json(['success' => true, 'reaction_summary' => $this->svc->react($a, Auth::user(), $request->input('reaction'))]);
    }

    public function unreact(int $id)
    {
        $a = $this->svc->find($id);
        if (!$a || !$this->svc->canView($a, Auth::user())) { return $this->fail('Announcement not found.', 404); }

        return response()->json(['success' => true, 'reaction_summary' => $this->svc->unreact($a, Auth::user())]);
    }
}
