<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use App\Helpers\MediaUrl;
use App\Helpers\PostVisibility;
use App\Models\Post;
use App\Models\Attachmentpost;
use App\Models\Resource;
use App\Models\Userprofile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use  App\Http\Controllers\Postcomments\CommentController;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    //test
    private function commentTotal(string $postUuid): int
    {
        $comments = DB::table('commentposts')->where('post_uuidOrUind', $postUuid);
        $replies = DB::table('commentreplies')->whereIn('comment_uuid', (clone $comments)->select('comment_uuid'))->count();

        return (int) $comments->count() + (int) $replies;
    }

    public function index(Request $request)
    {
        if (Auth::check()) {
            try {
                $requestedCode = $request->code;
                $authCode = Auth::user()->code;
                $fullname = Auth::user()->fname . ' ' . Auth::user()->lname;

                $result = [];

                if ($requestedCode) {
                    // Scenario 1: Viewing a specific code profile
                    if ($authCode == $requestedCode) {
                        // Show all posts (including private)
                        $posts = Post::where('code', $authCode)->get();
                    } else {
                        // Show only public posts
                        $posts = PostVisibility::limit(Post::where('code', $requestedCode), $requestedCode, $authCode)->get();
                    }

                    $commentController = new CommentController();

                    // The author is the profile owner, not whoever is looking at the page
                    $authorName = DB::table('users')->where('code', $requestedCode)->value('fullname')
                        ?: DB::table('resources')->where('code', $requestedCode)->value('fullname')
                        ?: $fullname;
                    $authorPhoto = DB::table('userprofiles')->where('code', $requestedCode)->value('photo_pic');

                    foreach ($posts as $post) {
                        // Fetch attachments
                        $attachmentQuery = Attachmentpost::where('posts_uuid', $post->posts_uuid);

                        

                        $attachments = $attachmentQuery->get();

                        // Group attachments
                        $images = $attachments->where('posts_type', 'image')->values();
                        $videos = $attachments->where('posts_type', 'video')->values();

                        // Call Postcomments@index with a fake request
                        $fakeRequest = new Request(['post_uuidOrUind' => $post->posts_uuid]);
                        $commentResponse = $commentController->index($fakeRequest);
                        $commentData = json_decode($commentResponse->getContent());

                        $result[] = [
                            "profile_pic" => $post->profile_pic ?: $authorPhoto,
                            "id"=>$post->id,
                            "transNo"=>$post->transNo,
                            "fullname" => $authorName,
                            "posts_uuid" => $post->posts_uuid,
                            "caption" => $post->caption,
                            "status" => $post->status,
                            "created_at" => $post->created_at,
                            "updated_at" => $post->updated_at,
                            "images" => $images,
                            "videos" => $videos,
                            "comments" => $commentData,
                            // comments + replies, the same number the Home feed shows
                            "comments_count" => $this->commentTotal($post->posts_uuid),
                            // reactions: totals, breakdown and what the viewer picked (one per user per post)
                            "reaction_summary" => \App\Helpers\ReactionSummary::forPost((int) $post->id, $authCode),
                            "my_reaction" => \App\Helpers\ReactionSummary::forPost((int) $post->id, $authCode)['mine'],
                            "is_owner" => (string) $post->code === (string) $authCode
                        ];
                    }
                }

                return response()->json([
                    'success' => true,
                    'data' => array_values($result)
                ]);
            } catch (\Throwable $th) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred: ' . $th->getMessage(),
                ]);
            }
        }

        // return view('testuploads');
    }

    public function index22(Request $request)
    {
        if (Auth::check()) {
            try {
                $requestedCode = $request->code;
                $authCode = Auth::user()->code;

                $result = [];

                if ($requestedCode) {
                    // Scenario 1: Viewing a specific code profile
                    if ($authCode == $requestedCode) {
                        // Show all posts (including private)
                        $posts = Post::where('code', $authCode)->get();
                    } else {
                        // Show only public posts
                        $posts = PostVisibility::limit(Post::where('code', $requestedCode), $requestedCode, $authCode)->get();
                    }

                    $commentController = new CommentController();
                    // Loop through posts and add attachments to result
                    foreach ($posts as $post) {
                        // Conditional attachments based on access
                        $attachmentQuery = Attachmentpost::where('posts_uuid', $post->posts_uuid);

                        

                        $attachments = $attachmentQuery->get();


                        // Create a new Request object with posts_uuid as query param
                        $fakeRequest = new Request(['post_uuidOrUind' => $post->posts_uuid]);

                        // Call Postcomments@index with the fake request
                        $commentResponse = $commentController->index($fakeRequest);
                        $commentData = json_decode($commentResponse->getContent());

                        $result[] = [
                            "Fullname" => $post->created_by,
                            "status" => $post->status,
                            "caption" => $post->caption,
                            "posts_uuid" => $post->posts_uuid,
                            "posts" => $attachments,
                            "comments" => $commentData
                        ];
                    }
                }
                return response()->json(array_values($result));
            } catch (\Throwable $th) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred: ' . $th->getMessage(),
                ]);
            }
        }

        // return view('testuploads');
    }


    /**
     * Edit a post (text, privacy, images, video).
     *
     *  - only the owner may edit (403 otherwise)
     *  - new images are ADDED; existing ones are kept unless listed in remove_attachments[]
     *    (posts_uuind values of THIS post; ids of other posts are ignored)
     *  - a new video replaces the current one
     *  - physical files are deleted only after the database change commits, and only when no
     *    other attachment row still references the same file
     *  - id, author, created_at, reactions and comments are never touched
     */
    public function updatePostByTransNo(Request $request, $transNo)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'caption'              => 'nullable|string|max:5000',
            'status'               => 'required|integer|in:0,1,2',
            'posts'                => 'nullable|array|max:10',
            'posts.*'              => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:3000',
            'video'                => 'nullable|mimetypes:video/mp4|max:50000',
            'remove_attachments'   => 'nullable|array|max:50',
            'remove_attachments.*' => 'string|max:64',
        ], $this->mediaValidationMessages());

        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }

        $user = Auth::user();

        // ---------- AUTHORISATION (enforced here, not only in the client) ----------
        $post = Post::where('transNo', $transNo)->first();

        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post not found.'], 404);
        }
        if ((string) $post->code !== (string) $user->code) {
            return response()->json(['success' => false, 'message' => 'You can only edit your own posts.'], 403);
        }

        $codeuser   = $user->code;
        $fullname   = $user->fullname;
        $folderuuid = $post->posts_uuid;
        $stored     = [];   // files written during this request (cleaned up if the save fails)

        try {
            DB::beginTransaction();

            // ---------- which attachments are being detached (this post only) ----------
            $removeIds = array_values(array_unique($data['remove_attachments'] ?? []));
            $toRemove = $removeIds
                ? DB::table('attachmentposts')->where('posts_uuid', $folderuuid)->whereIn('posts_uuind', $removeIds)->get()
                : collect();

            // a new video replaces the current one
            if ($request->hasFile('video')) {
                $toRemove = $toRemove->concat(
                    DB::table('attachmentposts')->where('posts_uuid', $folderuuid)->where('posts_type', 'video')->get()
                )->unique('id')->values();
            }
            $removeRowIds = $toRemove->pluck('id')->all();

            // ---------- limits and "must still have content" ----------
            $keptImages = DB::table('attachmentposts')
                ->where('posts_uuid', $folderuuid)->where('posts_type', 'image')
                ->whereNotIn('id', $removeRowIds)->count();
            $newImages = count($request->file('posts') ?? []);

            if ($keptImages + $newImages > 10) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'You can attach up to 10 images per post.'], 422);
            }

            $keptAny = DB::table('attachmentposts')->where('posts_uuid', $folderuuid)->whereNotIn('id', $removeRowIds)->count();
            $caption = $request->exists('caption') ? trim((string) $request->input('caption')) : (string) $post->caption;

            if ($caption === '' && $keptAny === 0 && !$request->hasFile('posts') && !$request->hasFile('video')) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'A post needs some text, an image or a video.'], 422);
            }

            // ---------- UPDATE THE POST (id, author and created_at stay as they are) ----------
            $post->update([
                'caption'    => $caption === '' ? null : $caption,
                'status'     => $data['status'],
                'updated_by' => $fullname,
                'updated_at' => now(),
            ]);

            // detach removed attachments
            if ($removeRowIds) {
                DB::table('attachmentposts')->whereIn('id', $removeRowIds)->delete();
            }

            // keep each attachment's visibility in step with the post
            DB::table('attachmentposts')->where('posts_uuid', $folderuuid)->update(['status' => $data['status']]);

            // ---------- ADD NEW IMAGES ----------
            if ($request->hasFile('posts')) {
                foreach ($request->file('posts') as $file) {
                    [$relative, $url] = $this->storeMedia($file, $codeuser, $folderuuid);
                    $stored[] = $relative;

                    DB::table('attachmentposts')->insert([
                        'code'        => $codeuser,
                        'transNo'     => $post->transNo,
                        'posts_uuid'  => $folderuuid,
                        'posts_uuind' => (string) Str::uuid(),
                        'status'      => $data['status'],
                        'path_url'    => $url,
                        'posts_type'  => 'image',
                        'created_by'  => $fullname,
                        'created_at'  => now(),
                    ]);
                }
            }

            // ---------- ADD NEW VIDEO ----------
            if ($request->hasFile('video')) {
                [$relative, $url] = $this->storeMedia($request->file('video'), $codeuser, $folderuuid);
                $stored[] = $relative;

                DB::table('attachmentposts')->insert([
                    'code'        => $codeuser,
                    'transNo'     => $post->transNo,
                    'posts_uuid'  => $folderuuid,
                    'posts_uuind' => (string) Str::uuid(),
                    'status'      => $data['status'],
                    'path_url'    => $url,
                    'posts_type'  => 'video',
                    'created_by'  => $fullname,
                    'created_at'  => now(),
                ]);
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            foreach ($stored as $relative) {
                Storage::disk('public')->delete($relative);   // do not leave orphan uploads behind
            }
            report($th);

            return response()->json([
                'success' => false,
                'message' => 'The post could not be updated. Please try again.',
            ], 500);
        }

        // ---------- delete physical files only now, and only if nothing else uses them ----------
        foreach ($toRemove as $a) {
            $relative = MediaUrl::relativePath($a->path_url);
            if ($relative && !DB::table('attachmentposts')->where('path_url', 'like', '%' . $relative)->exists()) {
                Storage::disk('public')->delete($relative);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully.',
            'transNo' => $post->transNo,
            'post'    => $this->postPayload($post->fresh()),
        ]);
    }

    /** The edited post in the same shape the home feed uses, so the client can swap it in place. */
    private function postPayload(Post $post): array
    {
        $attachments = Attachmentpost::where('posts_uuid', $post->posts_uuid)->orderBy('id')->get();

        return [
            'id'         => $post->id,
            'transNo'    => $post->transNo,
            'posts_uuid' => $post->posts_uuid,
            'caption'    => $post->caption,
            'status'     => $post->status,
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
            'images'     => $attachments->where('posts_type', 'image')->values(),
            'videos'     => $attachments->where('posts_type', 'video')->values(),
        ];
    }

    /**
     * Store an uploaded file on the public disk under a random, server-generated
     * name (the client's filename is never used) and return [relativePath, url].
     */
    private function storeMedia($file, $codeuser, $folderuuid): array
    {
        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        $dir = "uploads/posts/{$codeuser}/{$folderuuid}";
        $filename = Str::uuid() . '.' . $ext;
        $file->storeAs($dir, $filename, 'public');

        return ["{$dir}/{$filename}", url("storage/{$dir}/{$filename}")];
    }

    private function mediaValidationMessages(): array
    {
        return [
            'posts.max'       => 'You can attach up to 10 images per post.',
            'posts.*.file'    => 'Each attachment must be a valid file.',
            'posts.*.mimes'   => 'Images must be JPG, PNG, GIF or WEBP.',
            'posts.*.uploaded' => 'An image could not be uploaded. It may be larger than the server allows.',
            'posts.*.max'     => 'Each image must be 3 MB or smaller.',
            'video.mimetypes' => 'Video must be an MP4 file.',
            'video.max'       => 'Video must be 50 MB or smaller.',
            'caption.max'     => 'Post text can be up to 5000 characters.',
        ];
    }

    private function validationFailed($validator)
    {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors'  => $validator->errors(),
        ], 422);
    }

    public function savePost(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'posts'     => 'nullable|array|max:10',
            'posts.*'   => 'file|mimes:jpeg,png,jpg,gif,webp|max:3000', // only images here
            'caption'   => 'nullable|string|max:5000',
            'status'    => 'required|integer|in:0,1,2',
            'video'     => 'nullable|mimetypes:video/mp4|max:50000' // separate video
        ], $this->mediaValidationMessages());

        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }

        // a post needs some content: text, an image or a video
        if (!trim((string) ($data['caption'] ?? '')) && !$request->hasFile('posts') && !$request->hasFile('video')) {
            return response()->json(['success' => false, 'message' => 'Write something or attach an image or video.'], 422);
        }

        try {
            DB::beginTransaction(); // Start transaction

            $transNo   = Post::max('transNo');
            $newtrans  = empty($transNo) ? 1 : $transNo + 1;
            $folderuuid = Str::uuid();
            $codeuser   = Auth::user()->code;

            // Insert main post
            Post::create([
                'code'       => $codeuser,
                'posts_uuid' => $folderuuid,
                'transNo'    => $newtrans,
                'caption'    => $data['caption'] ?? null,
                'status'     => $data['status'],
                'created_by' => Auth::user()->fullname,
                'updated_by' => '',
                'created_at' => now()
            ]);

            // Save image files
            if ($request->hasFile('posts')) {
                foreach ($request->file('posts') as $file) {
                    $uuid = Str::uuid();
                    [$relative, $url] = $this->storeMedia($file, $codeuser, $folderuuid);

                    DB::table('attachmentposts')->insert([
                        'code'        => $codeuser,
                        'transNo'     => $newtrans,
                        'posts_uuid'  => $folderuuid,
                        'posts_uuind' => $uuid,
                        'status'      => $data['status'],
                        'path_url'    => $url,
                        'posts_type'  => 'image',
                        'created_by'  => Auth::user()->fullname,
                    ]);
                }
            }
            // Save video file
            if ($request->hasFile('video')) {
                $video    = $request->file('video');
                $uuid = Str::uuid();
                [$relative, $url] = $this->storeMedia($video, $codeuser, $folderuuid);

                DB::table('attachmentposts')->insert([
                    'code'        => $codeuser,
                    'transNo'     => $newtrans,
                    'posts_uuid'  => $folderuuid,
                    'posts_uuind' => $uuid,
                    'status'      => $data['status'],
                    'path_url'    => $url,
                    'posts_type'  => 'video',
                    'created_by'  => Auth::user()->fullname,
                ]);
            }

            DB::commit(); // Commit transaction

            // Live-update broadcast is best effort: the post is already saved, so an
            // unreachable Pusher (offline, bad certificate...) must not fail the request
            // (the user would otherwise retry and create a duplicate post).
            // The channel is public, so it only says "something was published": no caption or author, and a private post
            // is not announced at all. Every client then asks the API what ITS viewer may see.
            try {
                if ((int) $data['status'] !== PostVisibility::PRIVATE) {
                    event(new \App\Events\NewPostCreated([
                        'transNo' => $newtrans,
                        'code' => $codeuser,
                        'status' => (int) $data['status'],
                        'created_at' => now(),
                    ]));
                }
            } catch (\Throwable $e) {
                \Log::warning('NewPostCreated broadcast failed: ' . $e->getMessage());
            }


            return response()->json([
                'success' => true,
                'message' => 'Successfully uploaded.'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack(); // Rollback transaction on error

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $th->getMessage()
            ]);
        }
    }

  
    public function store(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'posts.*' => 'file|mimes:jpeg,png,jpg,gif,mp4,avi,mov|max:3000', // Validate multiple files
            'caption' => 'nullable|string',
            'status' => 'required|integer|in:0,1,2',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction(); // Start transaction

            $transNo = Post::max('transNo');
            $newtrans = empty($transNo) ? 1 : $transNo + 1;

            $uploadedFiles = [];
            $folderuuid = Str::uuid();
            $codeuser = Auth::user()->code;
            Post::insert([
                [
                    'code' => $codeuser,
                    'posts_uuid' => $folderuuid,
                    'transNo' => $newtrans,
                    'caption' =>  $data['caption'],
                    'status' => $data['status'],
                    'created_by' => Auth::user()->fullname,
                    'updated_by' => '',
                    'created_at' => now()
                ]
            ]);

            if ($request->hasFile('posts')) {
                foreach ($request->file('posts') as $file) {
                    $uuid = Str::uuid();
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $filePath = "uploads/posts/{$codeuser}/{$folderuuid}/{$filename}";
                    $file->storeAs("uploads/posts/{$codeuser}/{$folderuuid}", $filename, 'public');

                    // Determine file type
                    $mime = $file->getMimeType();
                    $postType = str_contains($mime, 'video') ? 'video' : (str_contains($mime, 'image') ? 'image' : 'other');

                    // Store full URL to the file
                    $storageUrl = asset('storage/' . $filePath);

                    // Store file record in DB
                    DB::table('attachmentposts')->insert([
                        'code' => $codeuser,
                        'transNo' => $newtrans,
                        'posts_uuid' => $folderuuid,
                        'posts_uuind' => $uuid,
                        'status' => $data['status'],
                        'path_url' => url('storage/uploads/posts/' . Auth::user()->code . '/' . $folderuuid . '/' . $filename),
                        'posts_type' => $postType,
                        'created_by' => Auth::user()->fullname,
                    ]);
                    // Optional array to return or track uploaded files
                    $uploadedFiles[] = [
                        'uuid' => $uuid,
                        'folderuuid' => $folderuuid,
                        'filename' => $filename,
                        'path' => $storageUrl,
                        'type' => $postType,
                    ];
                }
            } else {
                $uuid = Str::uuid(); // Ensure $uuid is always set
                DB::table('posts')->insert([
                    'posts_uuid' => $folderuuid,
                    'transNo' => $newtrans,
                    'posts_uuind' => $uuid,
                    'caption' => $data['caption'],
                    'status' => $data['status'],
                    'code' => $codeuser,
                    'created_by' => Auth::user()->fullname
                ]);
            }

            DB::commit(); // Commit transaction
            return response()->json([
                'success' => true,
                'message' => 'Successfully uploaded.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack(); // Rollback the transaction on error
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $th->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        //
        // return 'test';
        if (Auth::check()) {
            try {

                if (Auth::check()) {
                    try {
                        $requestedCode = $request->code;
                        $authCode = Auth::user()->code;

                        $result = [];

                        // Scenario 1: Viewing a specific code profile
                        if ($authCode == $requestedCode) {
                            // Show all posts (including private)
                            $posts = Post::where('code', $authCode)
                                ->where('posts_uuid', $id)
                                ->get();
                        } else {
                            // Show only public posts
                            $posts = PostVisibility::limit(Post::where('code', $requestedCode), $requestedCode, $authCode)
                                ->where('posts_uuid', $id)->get();
                        }

                        // Loop through posts and add attachments to result
                        foreach ($posts as $post) {
                            // Determine if full attachments should be shown
                            if ($authCode == $requestedCode) {
                                // Show all attachments
                                $attachments = Attachmentpost::where('posts_uuid', $post->posts_uuid)->get();
                            } else {
                                // Show only public attachments
                                $attachments = Attachmentpost::where('posts_uuid', $post->posts_uuid)->get();
                            }

                            $profilePic = Userprofile::select('photo_pic')->where('code', $requestedCode)->first();
                            $result[] = [
                                "Fullname" => $post->created_by,
                                "photo_pic" => $profilePic->photo_pic ?? Storage::disk('public')->url('uploads/DEFAULTPROFILE/DEFAULTPROFILE.png'),
                                "status" => $post->status,
                                "caption" => $post->caption,
                                "posts_uuid" => $post->posts_uuid,
                                "posts" => $attachments
                            ];
                        }
                        return response()->json(array_values($result));
                    } catch (\Throwable $th) {
                        return response()->json([
                            'success' => false,
                            'message' => 'An error occurred: ' . $th->getMessage(),
                        ]);
                    }
                }
            } catch (\Throwable $th) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred: ' . $th->getMessage(),
                ]);
            }
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {


        // return $id;
        $data = $request->all();

        // return $data;
        // Validate incoming data
        $validator = Validator::make($data, [
            'posts.*' => 'file|mimes:jpeg,png,jpg,gif,mp4,avi,mov|max:3000', // Validate multiple files
            'caption' => 'nullable|string',
            'status' => 'required|integer|in:0,1,2',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction(); // Start transaction

            // Find the post to be updated
            $post = Post::where('posts_uuid', $id)->first();

            // If post doesn't exist or user doesn't own the post, return error
            if (!$post || (string) $post->code !== (string) Auth::user()->code) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to update this post.',
                ], 403);
            }

            // Get the transaction number and folder UUID
            $transNo = $post->transNo;
            $folderuuid = $post->posts_uuid;
            $codeuser = Auth::user()->code;

            // Update the post table
            $post->caption = $data['caption'] ?? $post->caption; // Only update if provided
            $post->status = $data['status'] ?? $post->status;   // Only update if provided
            $post->updated_by = Auth::user()->fullname;
            $post->updated_at = now();
            $post->save(); // Save updated post data

            // Handle file uploads if any
            $uploadedFiles = [];
            if ($request->hasFile('posts')) {
                // Remove old attachments related to this post before updating
                DB::table('attachmentposts')->where('posts_uuid', $folderuuid)->delete();

                foreach ($request->file('posts') as $file) {
                    $uuid = Str::uuid();
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $filePath = "uploads/posts/{$codeuser}/{$folderuuid}/{$filename}";
                    $file->storeAs("uploads/posts/{$codeuser}/{$folderuuid}", $filename, 'public');

                    // Determine file type
                    $mime = $file->getMimeType();
                    $postType = str_contains($mime, 'video') ? 'video' : (str_contains($mime, 'image') ? 'image' : 'other');

                    // Store full URL to the file
                    $storageUrl = asset('storage/' . $filePath);

                    // Store file record in DB
                    DB::table('attachmentposts')->insert([
                        'code' => $codeuser,
                        'transNo' => $transNo,
                        'posts_uuid' => $folderuuid,
                        'posts_uuind' => $uuid,
                        'status' => $data['status'],
                        'path_url' => url('storage/uploads/posts/' . Auth::user()->code . '/' . $folderuuid . '/' . $filename),
                        'created_by' => Auth::user()->fullname,
                    ]);

                    // Optional array to return or track uploaded files
                    // $uploadedFiles[] = [
                    //     'uuid' => $uuid,
                    //     'folderuuid' => $folderuuid,
                    //     'filename' => $filename,
                    //     'path' => $storageUrl,
                    //     'type' => $postType,
                    // ];
                }
            }

            DB::commit(); // Commit transaction

            return response()->json([
                'success' => true,
                'message' => 'Successfully updated.',
                // 'uploadedFiles' => $uploadedFiles ?? [],
            ]);
        } catch (\Throwable $th) {
            DB::rollBack(); // Rollback the transaction on error
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $th->getMessage(),
            ]);
        }
    }





    /**
     * Remove the specified resource from storage.
     */
    // DELETE ALL POST TRANSACTIONS
    public function destroy(string $id)
    {
        //
        if (Auth::check()) {
            DB::beginTransaction();

            try {
                // Find the post that matches the ID and belongs to the authenticated user
                $post = Post::where('posts_uuid', $id)->where('code', Auth::user()->code)->first();

                if (!$post) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'You are not authorized to delete this post.',], 403);
                }

                $folderPath = "uploads/posts/$post->code/$post->posts_uuid";

                // Delete the entire folder and its contents
                Storage::disk('public')->deleteDirectory($folderPath);

                // Delete the post and its attachments
                Post::where('posts_uuid', $id)->delete();
                Attachmentpost::where('posts_uuid', $id)->delete();

                DB::commit();
                return response()->json(['success' => true, 'message' => 'Post and its attachments were successfully deleted.',], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong. Please try again.',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
    }

    // DELETE INDIVIDUAL ATTACHMENT 
    public function deleteIndividualPost(string $id)
    {
        if (Auth::check()) {
            DB::beginTransaction();

            try {
                // Find the post that matches the ID and belongs to the authenticated user
                $countAttach = Attachmentpost::where('posts_uuind', $id)->where('code', Auth::user()->code)->get();

                if (count($countAttach) > 0) {
                    $Attachment = Attachmentpost::where('posts_uuind', $id)->where('code', Auth::user()->code)->first();
                    $relative = MediaUrl::relativePath($Attachment->path_url);

                    Attachmentpost::where('posts_uuind', $id)->where('code', Auth::user()->code)->delete();
                    // remove the stored file itself
                    if ($relative) {
                        Storage::disk('public')->delete($relative);
                    }
                    DB::commit();
                    return response()->json(['success' => true, 'message' => 'Post and its attachments were successfully deleted.',], 200);
                }
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'You are not authorized to delete this post.',], 403);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong. Please try again.',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
    }

    public function authpostshow()
    {
        if (Auth::check()) {
        }
    }
    public function showForm()
    {
        return view('post');
    }


    public function getDataPost(Request $request)
    {
        if (!Auth::check()) return response()->json([]);

        try {
            $requestedCode = $request->code;
            $authCode = Auth::user()->code;

            $posts = DB::table('posts')
                ->where('code', $requestedCode ?: $authCode)
                ->whereIn('status', PostVisibility::statusesFor($requestedCode ?: $authCode, $authCode))
                ->get();

            $result = [];
            foreach ($posts as $post) {
                $result[$post->posts_uuid]['Fullname'] = $post->created_by;
                $result[$post->posts_uuid]['status'] = $post->status;
                $result[$post->posts_uuid]['caption'] = $post->caption;
                $result[$post->posts_uuid]['posts'][] = [
                    'posts_uuind' => $post->posts_uuind,
                    'post' => $post->post,
                    'transNo' => $post->transNo
                ];
            }

            return response()->json(array_values($result));
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }
}
