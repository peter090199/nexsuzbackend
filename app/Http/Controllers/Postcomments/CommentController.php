<?php

namespace App\Http\Controllers\Postcomments;

use App\Helpers\PostVisibility;
use App\Helpers\MediaUrl;
use App\Helpers\ReactionSummary;
use App\Http\Controllers\Controller;
use App\Models\CommentPost;
use App\Models\CommentReply;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Comments, replies (one level, like Facebook), comment images and comment reactions.
 *
 * Routes (unchanged): resource "comment" (index/store/update/destroy), POST commentreply.
 * Added: POST reactToComment, POST removeCommentReaction, GET commentReactors.
 *
 * A "post" here is identified by post_uuidOrUind: a posts.posts_uuid or an
 * attachmentposts.posts_uuind, exactly as before.
 */
class CommentController extends Controller
{
    private const DEFAULT_PIC = 'https://exploredition.com/storage/app/public/uploads/DEFAULTPROFILE/DEFAULTPROFILE.png';
    private const DUPLICATE_WINDOW_SECONDS = 5;

    // ------------------------------------------------------------------ helpers

    private function messages(): array
    {
        return [
            'comment.max'      => 'A comment can be up to 2000 characters.',
            'image.mimes'      => 'The image must be a JPG, PNG, GIF or WEBP file.',
            'image.max'        => 'The image must be 3 MB or smaller.',
            'image.uploaded'   => 'The image could not be uploaded. It may be larger than the server allows.',
            'reaction.in'      => 'Unknown reaction.',
            'target_type.in'   => 'Unknown target.',
        ];
    }

    private function fail(string $message, int $status, $errors = null)
    {
        return response()->json(array_filter([
            'success' => false, 'message' => $message, 'errors' => $errors,
        ], fn ($v) => $v !== null), $status);
    }

    /** users.code of the author of the post a comment thread belongs to (post or attachment uuid). */
    private function postOwnerCode(string $uuidOrUind): ?string
    {
        $code = DB::table('posts')->where('posts_uuid', $uuidOrUind)->value('code');
        if ($code === null) {
            $code = DB::table('attachmentposts')->where('posts_uuind', $uuidOrUind)->value('code');
        }

        return $code === null ? null : (string) $code;
    }

    /** [code => ['name' => , 'pic' => ]] for many users in two queries. */
    private function people(array $codes): array
    {
        $codes = array_values(array_unique(array_filter($codes, fn ($c) => $c !== null)));
        if (!$codes) {
            return [];
        }
        $users = User::whereIn('code', $codes)->get(['code', 'fname', 'lname'])->keyBy('code');
        $pics = Userprofile::whereIn('code', $codes)->pluck('photo_pic', 'code');

        $out = [];
        foreach ($codes as $c) {
            $u = $users->get($c);
            $out[$c] = [
                'name' => $u ? trim($u->fname . ' ' . $u->lname) : 'Unknown user',
                'pic'  => $pics->get($c) ?: self::DEFAULT_PIC,
            ];
        }

        return $out;
    }

    private function storeImage($file, $code): array
    {
        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        $dir = "uploads/comments/{$code}";
        $name = Str::uuid() . '.' . $ext;
        $file->storeAs($dir, $name, 'public');

        return ["{$dir}/{$name}", url("storage/{$dir}/{$name}")];
    }

    private function deleteImage(?string $stored): void
    {
        $relative = MediaUrl::relativePath($stored);
        if ($relative && !DB::table('commentposts')->where('attachment_url', 'like', '%' . $relative)->exists()
            && !DB::table('commentreplies')->where('attachment_url', 'like', '%' . $relative)->exists()) {
            Storage::disk('public')->delete($relative);
        }
    }

    private function shapeReply($r, array $people, array $reactions, $me, ?string $postOwner): array
    {
        $p = $people[$r->code] ?? ['name' => 'Unknown user', 'pic' => self::DEFAULT_PIC];
        $isOwner = (string) $r->code === (string) $me;

        return [
            'id'             => $r->id,
            'type'           => 'reply',
            'comment_uuid'   => $r->comment_uuid,
            'status'         => $r->status,
            'code'           => $r->code,
            'fullname'       => $p['name'],
            'profile_pic'    => $p['pic'],
            'comment'        => $r->comment,
            'attachment_url' => MediaUrl::normalize($r->attachment_url),
            'date_comment'   => $r->date_comment,
            'created_by'     => $r->created_by,
            'updated_by'     => $r->updated_by,
            'created_at'     => $r->created_at,
            'updated_at'     => $r->updated_at,
            'edited'         => !empty($r->updated_by),
            'is_owner'       => $isOwner,
            'can_delete'     => $isOwner || ($postOwner !== null && $postOwner === (string) $me),
            'reactions'      => $reactions[(string) $r->id] ?? ReactionSummary::build([], $me),
        ];
    }

    private function shapeComment($c, array $replies, array $people, array $reactions, array $replyReactions, $me, ?string $postOwner): array
    {
        $p = $people[$c->code] ?? ['name' => 'Unknown user', 'pic' => self::DEFAULT_PIC];
        $isOwner = (string) $c->code === (string) $me;

        return [
            'id'              => $c->id,
            'type'            => 'comment',
            'comment_uuid'    => $c->comment_uuid,
            'post_uuidOrUind' => $c->post_uuidOrUind,
            'status'          => $c->status,
            'code'            => $c->code,
            'fullname'        => $p['name'],
            'profile_pic'     => $p['pic'],
            'comment'         => $c->comment,
            'attachment_url'  => MediaUrl::normalize($c->attachment_url),
            'date_comment'    => $c->date_comment,
            'created_at'      => $c->created_at,
            'updated_at'      => $c->updated_at,
            'edited'          => !empty($c->updated_by),
            'is_owner'        => $isOwner,
            'can_delete'      => $isOwner || ($postOwner !== null && $postOwner === (string) $me),
            'reactions'       => $reactions[$c->comment_uuid] ?? ReactionSummary::build([], $me),
            'replies'         => array_map(fn ($r) => $this->shapeReply($r, $people, $replyReactions, $me, $postOwner), $replies),
            'replies_count'   => count($replies),
        ];
    }

    /** Shape a collection of comments with their replies, authors and reactions (a fixed number of queries). */
    private function thread($comments, $me, ?string $postOwner): array
    {
        $hidden = \App\Services\BlockService::relatedCodes($me);
        if ($hidden) {
            $comments = $comments->reject(fn ($c) => in_array((int) $c->code, $hidden, true));
        }

        $uuids = $comments->pluck('comment_uuid')->all();
        $replies = $uuids ? \App\Services\BlockService::exclude(CommentReply::whereIn('comment_uuid', $uuids), 'code', $me)->orderBy('id')->get()->groupBy('comment_uuid') : collect();
        $allReplies = $replies->flatten();

        $people = $this->people(array_merge($comments->pluck('code')->all(), $allReplies->pluck('code')->all()));
        $commentReactions = ReactionSummary::forMany('comment', $uuids, $me);
        $replyReactions = ReactionSummary::forMany('reply', $allReplies->pluck('id')->all(), $me);

        return $comments->map(fn ($c) => $this->shapeComment(
            $c, $replies->get($c->comment_uuid, collect())->all(), $people, $commentReactions, $replyReactions, $me, $postOwner
        ))->values()->all();
    }

    private function singleComment(CommentPost $comment, $me): array
    {
        return $this->thread(collect([$comment]), $me, $this->postOwnerCode($comment->post_uuidOrUind))[0];
    }

    private function singleReply(CommentReply $reply, $me): array
    {
        $parent = CommentPost::where('comment_uuid', $reply->comment_uuid)->first();
        $owner = $parent ? $this->postOwnerCode($parent->post_uuidOrUind) : null;
        $people = $this->people([$reply->code]);
        $reactions = ReactionSummary::forMany('reply', [$reply->id], $me);

        return $this->shapeReply($reply, $people, $reactions, $me, $owner);
    }

    // ------------------------------------------------------------------ list

    /**
     * GET comment?post_uuidOrUind=...
     *
     *  - without per_page: the legacy plain array (PostController relies on it)
     *  - with per_page (max 50) and optional before=<comment id>: newest first, cursor paginated:
     *    { success, data[], total, total_with_replies, has_more, next_before }
     */
    public function index(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $uuid = (string) $request->input('post_uuidOrUind');
        $me = Auth::user()->code;
        if (!PostVisibility::canViewUuid($uuid, $me)) {
            return $this->deny();
        }
        $perPage = (int) $request->input('per_page', 0);
        $paginated = $perPage > 0;

        $query = CommentPost::where('post_uuidOrUind', $uuid);
        $total = (clone $query)->count();

        if ($paginated) {
            if ($request->filled('before')) {
                $query->where('id', '<', (int) $request->input('before'));
            }
            $comments = $query->orderByDesc('id')->limit(min($perPage, 50))->get();
        } else {
            $comments = $query->orderBy('id')->get();
        }

        $payload = $this->thread($comments, $me, $this->postOwnerCode($uuid));

        if (!$paginated) {
            return response()->json($payload);
        }

        $lastId = $comments->last()?->id;
        $hasMore = $lastId ? CommentPost::where('post_uuidOrUind', $uuid)->where('id', '<', $lastId)->exists() : false;
        $replyTotal = CommentReply::whereIn('comment_uuid', CommentPost::where('post_uuidOrUind', $uuid)->select('comment_uuid'))->count();

        return response()->json([
            'success'            => true,
            'data'               => $payload,
            'total'              => $total,
            'total_with_replies' => $total + $replyTotal,
            'has_more'           => $hasMore,
            'next_before'        => $lastId,
        ]);
    }

    public function create()
    {
        //
    }

    // ------------------------------------------------------------------ create

    /**
     * POST comment   { post_uuid | post_uuidOrUind, comment?, image?, status? }
     * (post_uuid may also be sent as a query parameter, as the existing client does)
     */
    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $uuid = (string) ($request->input('post_uuid') ?: $request->input('post_uuidOrUind'));

        $validator = Validator::make(array_merge($request->all(), ['post_uuid' => $uuid]), [
            'post_uuid' => 'required|string|max:64',
            'comment'   => 'nullable|string|max:2000',
            'status'    => 'nullable|integer',
            'image'     => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:3000',
        ], $this->messages());

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors());
        }

        $text = trim((string) $request->input('comment', ''));
        if ($text === '' && !$request->hasFile('image')) {
            return $this->fail('Write a comment or attach an image.', 422);
        }
        if ($this->postOwnerCode($uuid) === null) {
            return $this->fail('Post not found.', 404);
        }
        if (!PostVisibility::canViewUuid($uuid, Auth::user()->code)) {
            return $this->deny();
        }

        $user = Auth::user();

        // a double click must not post the same comment twice
        if ($text !== '' && !$request->hasFile('image')) {
            $duplicate = CommentPost::where('post_uuidOrUind', $uuid)->where('code', $user->code)->where('comment', $text)
                ->where('created_at', '>=', now()->subSeconds(self::DUPLICATE_WINDOW_SECONDS))->first();
            if ($duplicate) {
                return response()->json([
                    'success' => true, 'message' => 'Comment added successfully', 'duplicate' => true,
                    'data' => $this->singleComment($duplicate, $user->code),
                ], 200);
            }
        }

        $stored = null;
        try {
            $stored = $request->hasFile('image') ? $this->storeImage($request->file('image'), $user->code) : null;

            $comment = CommentPost::create([
                'comment_uuid'    => (string) Str::uuid(),
                'post_uuidOrUind' => $uuid,
                'status'          => $request->input('status', 0) ?? 0,
                'code'            => $user->code,
                'comment'         => $text,
                'attachment_url'  => $stored[1] ?? null,
                'date_comment'    => now(),
                'created_by'      => $user->fullname,
                'updated_by'      => null,
            ]);
        } catch (\Throwable $e) {
            if ($stored) {
                Storage::disk('public')->delete($stored[0]);
            }
            report($e);

            return $this->fail('Your comment could not be saved. Please try again.', 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data'    => $this->singleComment($comment->fresh(), $user->code),
        ], 201);
    }

    /**
     * POST commentreply   { comment_uuid, comment?, image?, status? }
     * Replies hang under a top-level comment (a reply to a reply is a sibling reply that @mentions its target).
     */
    public function commentreply(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $validator = Validator::make($request->all(), [
            'comment_uuid' => 'required|string|max:64',
            'comment'      => 'nullable|string|max:2000',
            'status'       => 'nullable|integer',
            'image'        => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:3000',
        ], $this->messages());

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors());
        }

        $text = trim((string) $request->input('comment', ''));
        if ($text === '' && !$request->hasFile('image')) {
            return $this->fail('Write a reply or attach an image.', 422);
        }
        if (!CommentPost::where('comment_uuid', $request->comment_uuid)->exists()) {
            return $this->fail('Comment not found.', 404);
        }
        if (!PostVisibility::canViewUuid(CommentPost::where('comment_uuid', $request->comment_uuid)->value('post_uuidOrUind'), Auth::user()->code)) {
            return $this->deny();
        }

        $user = Auth::user();

        $commentAuthor = CommentPost::where('comment_uuid', $request->comment_uuid)->value('code');
        if (\App\Services\BlockService::isBlocked($user->code, $commentAuthor)) {
            return $this->fail(\App\Services\BlockService::MESSAGE, 403);
        }

        if ($text !== '' && !$request->hasFile('image')) {
            $duplicate = CommentReply::where('comment_uuid', $request->comment_uuid)->where('code', $user->code)->where('comment', $text)
                ->where('created_at', '>=', now()->subSeconds(self::DUPLICATE_WINDOW_SECONDS))->first();
            if ($duplicate) {
                return response()->json([
                    'success' => true, 'message' => 'Reply added successfully', 'duplicate' => true,
                    'data' => $this->singleReply($duplicate, $user->code),
                ], 200);
            }
        }

        $stored = null;
        try {
            $stored = $request->hasFile('image') ? $this->storeImage($request->file('image'), $user->code) : null;

            $reply = CommentReply::create([
                'comment_uuid'   => $request->comment_uuid,
                'status'         => $request->input('status', 0) ?? 0,
                'code'           => $user->code,
                'comment'        => $text,
                'attachment_url' => $stored[1] ?? null,
                'date_comment'   => now(),
                'created_by'     => $user->fullname,
                'updated_by'     => null,
            ]);
        } catch (\Throwable $e) {
            if ($stored) {
                Storage::disk('public')->delete($stored[0]);
            }
            report($e);

            return $this->fail('Your reply could not be saved. Please try again.', 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reply added successfully',
            'data'    => $this->singleReply($reply->fresh(), $user->code),
        ], 201);
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    // ------------------------------------------------------------------ edit

    /**
     * PUT comment/{comment_uuid | reply id}
     * { comment_settings: com_headers|com_replies, comment?, image?, remove_image?, status? }
     * Only the author may edit.
     */
    public function update(Request $request, string $id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $validator = Validator::make($request->all(), [
            'comment'          => 'nullable|string|max:2000',
            'status'           => 'nullable|integer',
            'comment_settings' => ['required', Rule::in(['com_headers', 'com_replies'])],
            'image'            => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:3000',
        ], $this->messages());

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors());
        }

        $user = Auth::user();
        $isReply = $request->comment_settings === 'com_replies';
        $model = $isReply ? CommentReply::find($id) : CommentPost::where('comment_uuid', $id)->first();

        if (!$model) {
            return $this->fail($isReply ? 'Reply not found' : 'Comment not found', 404);
        }
        if ((string) $model->code !== (string) $user->code) {
            return $this->fail('You can only edit your own ' . ($isReply ? 'replies.' : 'comments.'), 403);
        }

        $text = $request->exists('comment') ? trim((string) $request->input('comment')) : (string) $model->comment;
        $keepsImage = $model->attachment_url && !$request->boolean('remove_image');
        if ($text === '' && !$keepsImage && !$request->hasFile('image')) {
            return $this->fail('A comment needs some text or an image.', 422);
        }

        $oldImage = $model->attachment_url;
        $stored = null;

        try {
            if ($request->hasFile('image')) {
                $stored = $this->storeImage($request->file('image'), $user->code);
                $model->attachment_url = $stored[1];
            } elseif ($request->boolean('remove_image')) {
                $model->attachment_url = null;
            }

            $model->comment = $text;
            $model->status = $request->input('status') ?? $model->status;
            $model->updated_by = $user->fullname;
            $model->updated_at = now();
            $model->save();
        } catch (\Throwable $e) {
            if ($stored) {
                Storage::disk('public')->delete($stored[0]);
            }
            report($e);

            return $this->fail('The comment could not be updated. Please try again.', 500);
        }

        if ($oldImage && $oldImage !== $model->attachment_url) {
            $this->deleteImage($oldImage);
        }

        return response()->json([
            'success' => true,
            'message' => ($isReply ? 'Reply' : 'Comment') . ' updated successfully',
            'data'    => $isReply ? $this->singleReply($model->fresh(), $user->code) : $this->singleComment($model->fresh(), $user->code),
        ]);
    }

    // ------------------------------------------------------------------ delete

    /**
     * DELETE comment/{comment_uuid | reply id}?comment_settings=com_headers|com_replies
     * Allowed for the author, or for the author of the post being discussed.
     * Deleting a comment removes its replies, their reactions and images.
     */
    public function destroy(Request $request, string $id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $isReply = $request->input('comment_settings', 'com_headers') === 'com_replies';
        $user = Auth::user();

        $model = $isReply ? CommentReply::find($id) : CommentPost::where('comment_uuid', $id)->first();
        if (!$model) {
            return $this->fail($isReply ? 'Reply not found' : 'Comment not found', 404);
        }

        $postUuid = $isReply
            ? CommentPost::where('comment_uuid', $model->comment_uuid)->value('post_uuidOrUind')
            : $model->post_uuidOrUind;
        $postOwner = $postUuid ? $this->postOwnerCode($postUuid) : null;

        $allowed = (string) $model->code === (string) $user->code
            || ($postOwner !== null && $postOwner === (string) $user->code);
        if (!$allowed) {
            return $this->fail('You can only delete your own comments, or comments on your own posts.', 403);
        }

        $images = [];
        DB::beginTransaction();
        try {
            if ($isReply) {
                $images[] = $model->attachment_url;
                DB::table('commentreactions')->where('target_type', 'reply')->where('target_ref', (string) $model->id)->delete();
                $model->delete();
            } else {
                $images[] = $model->attachment_url;
                $replies = CommentReply::where('comment_uuid', $model->comment_uuid)->get();
                foreach ($replies as $reply) {
                    $images[] = $reply->attachment_url;
                }
                DB::table('commentreactions')->where('target_type', 'reply')
                    ->whereIn('target_ref', $replies->pluck('id')->map(fn ($v) => (string) $v)->all())->delete();
                CommentReply::where('comment_uuid', $model->comment_uuid)->delete();
                DB::table('commentreactions')->where('target_type', 'comment')->where('target_ref', $model->comment_uuid)->delete();
                $model->delete();
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return $this->fail('The comment could not be deleted. Please try again.', 500);
        }

        foreach (array_filter($images) as $image) {
            $this->deleteImage($image);
        }

        return response()->json(['success' => true, 'message' => ($isReply ? 'Reply' : 'Comment') . ' deleted successfully']);
    }

    // ------------------------------------------------------------------ comment reactions

    /** The post a comment or reply belongs to (uuid), or null. */
    private function postUuidOfTarget(string $type, string $ref): ?string
    {
        if ($type === 'comment') {
            return CommentPost::where('comment_uuid', $ref)->value('post_uuidOrUind');
        }
        $parent = ctype_digit($ref) ? CommentReply::where('id', (int) $ref)->value('comment_uuid') : null;

        return $parent ? CommentPost::where('comment_uuid', $parent)->value('post_uuidOrUind') : null;
    }

    private function deny()
    {
        return $this->fail("You can't access this post.", 403);
    }

    private function targetExists(string $type, string $ref): bool
    {
        return $type === 'comment'
            ? CommentPost::where('comment_uuid', $ref)->exists()
            : (ctype_digit($ref) && CommentReply::where('id', (int) $ref)->exists());
    }

    /** POST reactToComment { target_type: comment|reply, target_ref, reaction } — one reaction per user, changeable. */
    public function reactToComment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target_type' => ['required', Rule::in(['comment', 'reply'])],
            'target_ref'  => 'required|string|max:64',
            'reaction'    => ['required', Rule::in(ReactionSummary::ALLOWED)],
        ], $this->messages());

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors());
        }
        if (!$this->targetExists($request->target_type, $request->target_ref)) {
            return $this->fail('Comment not found.', 404);
        }
        if (!PostVisibility::canViewUuid($this->postUuidOfTarget($request->target_type, $request->target_ref), Auth::user()->code)) {
            return $this->deny();
        }

        $me = Auth::user()->code;

        $targetAuthor = $request->target_type === 'reply'
            ? CommentReply::where('id', $request->target_ref)->value('code')
            : CommentPost::where('comment_uuid', $request->target_ref)->value('code');
        if (\App\Services\BlockService::isBlocked($me, $targetAuthor)) {
            return $this->fail(\App\Services\BlockService::MESSAGE, 403);
        }

        // the UNIQUE (target_type, target_ref, code) key makes this atomic: a repeat click changes, never duplicates
        DB::table('commentreactions')->upsert([[
            'target_type' => $request->target_type,
            'target_ref'  => $request->target_ref,
            'code'        => $me,
            'reaction'    => $request->reaction,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]], ['target_type', 'target_ref', 'code'], ['reaction', 'updated_at']);

        return response()->json([
            'success'   => true,
            'message'   => 'Reaction saved successfully',
            'reactions' => ReactionSummary::forComment($request->target_type, $request->target_ref, $me),
        ]);
    }

    /** POST removeCommentReaction { target_type, target_ref } */
    public function removeCommentReaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target_type' => ['required', Rule::in(['comment', 'reply'])],
            'target_ref'  => 'required|string|max:64',
        ], $this->messages());

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors());
        }

        $me = Auth::user()->code;
        DB::table('commentreactions')->where('target_type', $request->target_type)
            ->where('target_ref', $request->target_ref)->where('code', $me)->delete();

        return response()->json([
            'success'   => true,
            'message'   => 'Reaction removed.',
            'reactions' => ReactionSummary::forComment($request->target_type, $request->target_ref, $me),
        ]);
    }

    /** GET commentReactors?target_type=&target_ref= — who reacted, grouped by reaction (same shape as getReactionPost). */
    public function commentReactors(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target_type' => ['required', Rule::in(['comment', 'reply'])],
            'target_ref'  => 'required|string|max:64',
        ], $this->messages());

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors());
        }

        if (!PostVisibility::canViewUuid($this->postUuidOfTarget($request->target_type, $request->target_ref), Auth::user()->code)) {
            return $this->deny();
        }

        $rows = DB::table('commentreactions as r')
            ->leftJoin('users as u', 'u.code', '=', 'r.code')
            ->leftJoin('userprofiles as pf', 'pf.code', '=', 'r.code')
            ->where('r.target_type', $request->target_type)->where('r.target_ref', $request->target_ref)
            ->orderBy('r.id')
            ->get(['r.code', 'u.fname', 'u.lname', 'pf.photo_pic', 'r.reaction']);

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row->reaction] ??= ['reaction' => $row->reaction, 'count' => 0, 'person' => []];
            $grouped[$row->reaction]['count']++;
            $grouped[$row->reaction]['person'][] = [
                'code'      => $row->code,
                'fullname'  => trim($row->fname . ' ' . $row->lname) ?: 'Unknown user',
                'photo_pic' => $row->photo_pic ?: self::DEFAULT_PIC,
            ];
        }

        return response()->json([
            'success' => true,
            'count'   => count($rows),
            'react'   => array_values($grouped),
        ]);
    }
}
