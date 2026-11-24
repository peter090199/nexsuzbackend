<?php

namespace App\Services;

use App\Events\AnnouncementPublished;
use App\Helpers\MediaUrl;
use App\Helpers\ReactionSummary;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Global announcements (DEF-ADMIN / DEF-MASTERADMIN write them, everybody signed in reads the live ones).
 *
 *  - Lifecycle: draft -> published -> archived (and back: archived -> published or draft). Only published, not-yet-expired
 *    announcements are ever shown to job seekers and recruiters; drafts are for their author alone; archived and expired ones
 *    are for administrators.
 *  - Who may do what: the author edits; the author, and the master admin as moderator, archive or delete.
 *  - Comments live in commentposts (post_uuidOrUind = announcement_uuid), reactions in reactionpost (post_id = -announcements.id),
 *    so the existing comment endpoints and reaction summaries are reused. Nothing is shown to anybody the rules above do not allow:
 *    every check is made here, on the server.
 */
class AnnouncementService
{
    public const DRAFT = 'draft';
    public const PUBLISHED = 'published';
    public const ARCHIVED = 'archived';

    public const PRIORITIES = ['normal', 'important', 'urgent'];
    public const ADMIN_ROLES = ['DEF-ADMIN', 'DEF-MASTERADMIN'];
    public const MASTER = 'DEF-MASTERADMIN';

    /** from => the statuses it may move to */
    public const TRANSITIONS = [
        self::DRAFT => [self::PUBLISHED],
        self::PUBLISHED => [self::ARCHIVED],
        self::ARCHIVED => [self::PUBLISHED, self::DRAFT],
    ];

    // ------------------------------------------------------------------ who

    public static function isManager(?object $user): bool
    {
        return in_array($user->role_code ?? null, self::ADMIN_ROLES, true);
    }

    public static function isLive(object $a): bool
    {
        return $a->status === self::PUBLISHED
            && (!$a->published_at || Carbon::parse($a->published_at)->lte(now()))
            && (!$a->expires_at || Carbon::parse($a->expires_at)->gt(now()));
    }

    /** Can this viewer see it at all? Live: everybody. Otherwise: administrators only (a draft: its author only). */
    public function canView(object $a, ?object $viewer): bool
    {
        if (!$viewer) { return false; }
        if (self::isLive($a)) { return true; }
        if (!self::isManager($viewer)) { return false; }

        return $a->status === self::DRAFT ? (string) $a->code === (string) $viewer->code : true;
    }

    public function canEdit(object $a, ?object $viewer): bool
    {
        return self::isManager($viewer) && (string) $a->code === (string) $viewer->code;
    }

    /** archive / delete: the author, and the master admin as moderator (never for somebody else's draft) */
    public function canModerate(object $a, ?object $viewer): bool
    {
        if (!self::isManager($viewer)) { return false; }
        if ((string) $a->code === (string) $viewer->code) { return true; }

        return $viewer->role_code === self::MASTER && $a->status !== self::DRAFT;
    }

    // ------------------------------------------------------------------ queries

    public function find(int $id): ?object
    {
        return DB::table('announcements')->where('id', $id)->first();
    }

    public function findByUuid(string $uuid): ?object
    {
        return DB::table('announcements')->where('announcement_uuid', $uuid)->first();
    }

    /** published and not expired (the only announcements a job seeker or recruiter can ever get) */
    public static function liveQuery()
    {
        return DB::table('announcements')->where('status', self::PUBLISHED)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    private static function ordered($q)
    {
        return $q->orderByRaw("is_pinned desc, FIELD(priority, 'urgent', 'important', 'normal'), COALESCE(published_at, created_at) desc, id desc");
    }

    /** What the Home feed shows everybody: live announcements, pinned first, then by priority, then newest. */
    public function feed(object $viewer, int $limit = 30): array
    {
        return $this->cards(self::ordered(self::liveQuery())->limit($limit)->get(), $viewer);
    }

    /** Administrators' management list: other people's published / archived, and my own drafts. */
    public function manage(object $viewer, ?string $status = null): array
    {
        $q = DB::table('announcements')->where(fn ($w) => $w->where('status', '!=', self::DRAFT)->orWhere('code', $viewer->code));
        if (in_array($status, [self::DRAFT, self::PUBLISHED, self::ARCHIVED], true)) { $q->where('status', $status); }

        return $this->cards(self::ordered($q)->limit(200)->get(), $viewer);
    }

    public function counts(object $viewer): array
    {
        $rows = DB::table('announcements')->where(fn ($w) => $w->where('status', '!=', self::DRAFT)->orWhere('code', $viewer->code))
            ->select('status', DB::raw('count(*) as n'))->groupBy('status')->pluck('n', 'status');

        return ['draft' => (int) ($rows['draft'] ?? 0), 'published' => (int) ($rows['published'] ?? 0), 'archived' => (int) ($rows['archived'] ?? 0)];
    }

    // ------------------------------------------------------------------ serialising

    /** @return array<int,array<string,mixed>> */
    public function cards($rows, object $viewer): array
    {
        $rows = collect($rows)->values();
        if ($rows->isEmpty()) { return []; }
        $ids = $rows->pluck('id')->all();
        $uuids = $rows->pluck('announcement_uuid')->all();

        $files = DB::table('announcement_attachments')->whereIn('announcement_id', $ids)->orderBy('id')->get()->groupBy('announcement_id');
        $authors = DB::table('users')->whereIn('code', $rows->pluck('code')->unique()->all())
            ->select('code', 'fullname', 'role_code', DB::raw('(SELECT photo_pic FROM userprofiles WHERE userprofiles.code = users.code LIMIT 1) AS photo_pic'))
            ->get()->keyBy('code');
        $reactions = DB::table('reactionpost')->whereIn('post_id', array_map(fn ($i) => -$i, $ids))->get(['post_id', 'reaction', 'code'])->groupBy('post_id');
        $comments = DB::table('commentposts')->whereIn('post_uuidOrUind', $uuids)->select('post_uuidOrUind', DB::raw('count(*) as n'))->groupBy('post_uuidOrUind')->pluck('n', 'post_uuidOrUind');
        $replies = DB::table('commentreplies as r')->join('commentposts as c', 'c.comment_uuid', '=', 'r.comment_uuid')->whereIn('c.post_uuidOrUind', $uuids)
            ->select('c.post_uuidOrUind', DB::raw('count(*) as n'))->groupBy('c.post_uuidOrUind')->pluck('n', 'post_uuidOrUind');

        return $rows->map(function ($a) use ($files, $authors, $reactions, $comments, $replies, $viewer) {
            $author = $authors[$a->code] ?? null;
            $mine = $files[$a->id] ?? collect();
            $shape = fn ($f) => ['id' => $f->id, 'url' => MediaUrl::normalize($f->path_url), 'name' => $f->original_name, 'mime' => $f->mime, 'size' => (int) $f->size];
            $summary = ReactionSummary::build($reactions[-$a->id] ?? [], $viewer->code);
            $expired = $a->status === self::PUBLISHED && $a->expires_at && Carbon::parse($a->expires_at)->lte(now());

            return [
                'id' => $a->id, 'announcement_uuid' => $a->announcement_uuid, 'title' => $a->title, 'content' => $a->content,
                'priority' => $a->priority, 'is_pinned' => (bool) $a->is_pinned, 'status' => $a->status, 'is_expired' => (bool) $expired,
                'published_at' => $a->published_at, 'expires_at' => $a->expires_at, 'created_at' => $a->created_at, 'updated_at' => $a->updated_at,
                'author' => ['code' => $a->code, 'fullname' => $author->fullname ?? $a->created_by, 'role_code' => $author->role_code ?? null, 'photo_pic' => $author->photo_pic ?? null],
                'images' => $mine->where('kind', 'image')->map($shape)->values()->all(),
                'attachments' => $mine->where('kind', 'file')->map($shape)->values()->all(),
                'reaction_summary' => $summary, 'my_reaction' => $summary['mine'],
                'comments_count' => (int) ($comments[$a->announcement_uuid] ?? 0) + (int) ($replies[$a->announcement_uuid] ?? 0),
                'is_owner' => (string) $a->code === (string) $viewer->code,
                'can_edit' => $this->canEdit($a, $viewer),
                'can_moderate' => $this->canModerate($a, $viewer),
            ];
        })->all();
    }

    public function card(object $a, object $viewer): array
    {
        return $this->cards([$a], $viewer)[0];
    }

    // ------------------------------------------------------------------ writing

    /**
     * @param array{title:string,content:string,priority?:string,is_pinned?:bool,status?:string,expires_at?:?string} $data
     * @param UploadedFile[] $images
     * @param UploadedFile[] $files
     * @throws \DomainException
     */
    public function create(object $actor, array $data, array $images = [], array $files = []): object
    {
        $status = $data['status'] ?? self::DRAFT;
        $expires = $this->expiry($data['expires_at'] ?? null, $status === self::PUBLISHED);
        $uuid = (string) Str::uuid();
        $stored = [];

        try {
            $id = DB::transaction(function () use ($actor, $data, $status, $expires, $uuid, $images, $files, &$stored) {
                $id = DB::table('announcements')->insertGetId([
                    'announcement_uuid' => $uuid, 'code' => $actor->code, 'title' => trim($data['title']), 'content' => trim($data['content']),
                    'priority' => $data['priority'] ?? 'normal', 'is_pinned' => (bool) ($data['is_pinned'] ?? false), 'status' => $status,
                    'published_at' => $status === self::PUBLISHED ? now() : null, 'expires_at' => $expires,
                    'created_by' => $actor->fullname, 'updated_by' => '', 'created_at' => now(), 'updated_at' => now(),
                ]);
                $this->storeFiles($id, $actor->code, $uuid, $images, 'image', $stored);
                $this->storeFiles($id, $actor->code, $uuid, $files, 'file', $stored);

                return $id;
            });
        } catch (\Throwable $e) {
            foreach ($stored as $relative) { Storage::disk('public')->delete($relative); }   // no orphan uploads
            throw $e;
        }

        $a = $this->find($id);
        if ($status === self::PUBLISHED) { $this->announce($a); }

        return $a;
    }

    /** Edit the content of an announcement (author only). Status changes go through setStatus(). */
    public function update(object $actor, object $a, array $data, array $images = [], array $files = [], array $removeIds = []): object
    {
        $expires = array_key_exists('expires_at', $data) ? $this->expiry($data['expires_at'], $a->status === self::PUBLISHED) : $a->expires_at;
        $stored = [];

        try {
            DB::transaction(function () use ($actor, $a, $data, $expires, $images, $files, $removeIds, &$stored) {
                DB::table('announcements')->where('id', $a->id)->update([
                    'title' => trim($data['title'] ?? $a->title), 'content' => trim($data['content'] ?? $a->content),
                    'priority' => $data['priority'] ?? $a->priority, 'is_pinned' => array_key_exists('is_pinned', $data) ? (bool) $data['is_pinned'] : (bool) $a->is_pinned,
                    'expires_at' => $expires, 'updated_by' => $actor->fullname, 'updated_at' => now(),
                ]);
                // only this announcement's own attachments can be removed
                $gone = DB::table('announcement_attachments')->where('announcement_id', $a->id)->whereIn('id', $removeIds ?: [0])->get();
                DB::table('announcement_attachments')->whereIn('id', $gone->pluck('id'))->delete();
                foreach ($gone as $g) { $this->deleteFile($g->path_url); }
                $this->storeFiles($a->id, $a->code, $a->announcement_uuid, $images, 'image', $stored);
                $this->storeFiles($a->id, $a->code, $a->announcement_uuid, $files, 'file', $stored);
            });
        } catch (\Throwable $e) {
            foreach ($stored as $relative) { Storage::disk('public')->delete($relative); }
            throw $e;
        }

        return $this->find($a->id);
    }

    /** @throws \DomainException */
    public function setStatus(object $actor, object $a, string $to): object
    {
        if (!in_array($to, self::TRANSITIONS[$a->status] ?? [], true)) {
            throw new \DomainException("A {$a->status} announcement can't become {$to}.");
        }
        $update = ['status' => $to, 'updated_by' => $actor->fullname, 'updated_at' => now()];
        if ($to === self::PUBLISHED) {
            $this->expiry($a->expires_at, true);                       // an announcement that has already expired must get a new date first
            $update['published_at'] = now();
        }
        DB::table('announcements')->where('id', $a->id)->update($update);
        $fresh = $this->find($a->id);
        if ($to === self::PUBLISHED) { $this->announce($fresh); }

        return $fresh;
    }

    public function delete(object $a): void
    {
        DB::transaction(function () use ($a) {
            $commentIds = DB::table('commentposts')->where('post_uuidOrUind', $a->announcement_uuid)->pluck('comment_uuid');
            DB::table('commentreactions')->where('target_type', 'comment')->whereIn('target_ref', $commentIds)->delete();
            DB::table('commentreplies')->whereIn('comment_uuid', $commentIds)->delete();
            DB::table('commentposts')->where('post_uuidOrUind', $a->announcement_uuid)->delete();
            DB::table('reactionpost')->where('post_id', -$a->id)->delete();
            DB::table('announcement_attachments')->where('announcement_id', $a->id)->delete();
            DB::table('announcements')->where('id', $a->id)->delete();
        });
        Storage::disk('public')->deleteDirectory("uploads/announcements/{$a->code}/{$a->announcement_uuid}");
    }

    // ------------------------------------------------------------------ reactions

    public function react(object $a, object $viewer, string $reaction): array
    {
        DB::table('reactionpost')->updateOrInsert(
            ['post_id' => -$a->id, 'code' => (string) $viewer->code],
            ['reaction' => $reaction, 'updated_at' => now(), 'create_at' => now()]
        );

        return ReactionSummary::forPost(-$a->id, $viewer->code);
    }

    public function unreact(object $a, object $viewer): array
    {
        DB::table('reactionpost')->where('post_id', -$a->id)->where('code', (string) $viewer->code)->delete();

        return ReactionSummary::forPost(-$a->id, $viewer->code);
    }

    // ------------------------------------------------------------------ internals

    /** @throws \DomainException a date that is not in the future cannot be used by a published announcement */
    private function expiry($value, bool $publishing): ?Carbon
    {
        if ($value === null || $value === '') { return null; }
        $at = Carbon::parse($value);
        if ($publishing && $at->lte(now())) {
            throw new \DomainException('The expiration date must be in the future.');
        }

        return $at;
    }

    /** @param UploadedFile[] $uploads @param string[] $stored collects the written paths so a failure can clean up */
    private function storeFiles(int $id, $authorCode, string $uuid, array $uploads, string $kind, array &$stored): void
    {
        foreach ($uploads as $file) {
            if (!$file instanceof UploadedFile) { continue; }
            $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
            $dir = "uploads/announcements/{$authorCode}/{$uuid}";
            $name = Str::uuid() . ($ext ? ".{$ext}" : '');
            $file->storeAs($dir, $name, 'public');
            $stored[] = "{$dir}/{$name}";
            DB::table('announcement_attachments')->insert([
                'announcement_id' => $id, 'kind' => $kind, 'path_url' => url("storage/{$dir}/{$name}"),
                'original_name' => mb_substr(basename((string) $file->getClientOriginalName()), 0, 200), 'mime' => $file->getClientMimeType(),
                'size' => (int) $file->getSize(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function deleteFile(?string $pathUrl): void
    {
        $relative = MediaUrl::relativePath($pathUrl);
        if ($relative) { Storage::disk('public')->delete($relative); }
    }

    /** The live signal: best effort (an unreachable websocket must never fail a publish) and without any content. */
    private function announce(object $a): void
    {
        try {
            event(new AnnouncementPublished(['id' => (int) $a->id, 'priority' => $a->priority]));
        } catch (\Throwable $e) {
            Log::warning('AnnouncementPublished broadcast failed: ' . $e->getMessage());
        }
    }
}
