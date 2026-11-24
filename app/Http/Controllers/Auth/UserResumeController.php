<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Services\CvImport\CvDocumentException;
use App\Services\CvImport\CvImporter;
use App\Services\CvImport\CvParser;
use App\Services\CvImport\CvTextExtractor;

/**
 * Settings > Resume / CV. A job seeker keeps up to MAX resumes (PDF, DOC or DOCX) on their account and marks one as the default,
 * which the Apply Job page uses so they do not have to upload again for every application.
 *
 *  - routes sit behind the `cvwriter` middleware: DEF-USERS only
 *  - there is no user id in any route: every call reads and writes the caller's own rows, so nobody can list,
 *    open, change or delete another account's resume (a foreign id is a plain 404)
 *  - files live on the PRIVATE disk and are only served by download(), to their owner
 */
class UserResumeController extends Controller
{
    public const MAX = 5;

    private function mine()
    {
        return DB::table('user_resumes')->where('code', Auth::user()->code);
    }

    private function shape(object $r): array
    {
        return [
            'id' => (int) $r->id,
            'name' => $r->original_name,
            'size' => (int) $r->size,
            'is_default' => (bool) $r->is_default,
            'type' => CvTextExtractor::typeOf($r->path) ?? 'pdf',      // pdf | doc | docx (only a PDF can be sent along with a job application)
            'created_at' => $r->created_at,
        ];
    }

    private function listing(): array
    {
        return $this->mine()->orderByDesc('is_default')->orderByDesc('id')->get()->map(fn ($r) => $this->shape($r))->all();
    }

    public function index()
    {
        return response()->json(['success' => true, 'data' => $this->listing(), 'max' => self::MAX]);
    }

    public function store(Request $request)
    {
        $request->validate(
            ['resume' => ['required', 'file', 'max:5120', function ($attr, $file, $fail) {
                // the extension decides how it is read and must agree with what the file really is
                $ext = strtolower($file->getClientOriginalExtension());
                $kind = $file->guessExtension();
                $ok = match ($ext) {
                    'pdf' => $kind === 'pdf',
                    'docx' => in_array($kind, ['docx', 'zip'], true),
                    'doc' => in_array($kind, ['doc', 'bin'], true),
                    default => false,
                };
                if (!$ok) { $fail('The resume must be a PDF, DOC or DOCX file.'); }
            }]],
            ['resume.max' => 'The resume must be 5 MB or smaller.']
        );

        if ($this->mine()->count() >= self::MAX) {
            return response()->json([
                'success' => false,
                'message' => 'You can keep up to ' . self::MAX . ' resumes. Delete one to upload another.',
            ], 422);
        }

        $user = Auth::user();
        $file = $request->file('resume');
        $ext = strtolower($file->getClientOriginalExtension());       // validated above: pdf, doc or docx
        $path = $file->storeAs("uploads/{$user->code}/resumes", Str::uuid() . '.' . $ext, 'local');

        $name = trim(preg_replace('/[^\w\s.\-()]/u', '', $file->getClientOriginalName())) ?: 'resume.' . $ext;
        $isFirst = $this->mine()->count() === 0;     // the first resume is the default until the owner picks another

        $id = DB::table('user_resumes')->insertGetId([
            'code' => $user->code, 'original_name' => mb_substr($name, 0, 255), 'path' => $path,
            'size' => (int) $file->getSize(), 'is_default' => $isFirst, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $isFirst ? 'Resume uploaded and set as your default.' : 'Resume uploaded.',
            'data' => $this->listing(),
            'id' => $id,
        ], 201);
    }

    public function setDefault($id)
    {
        $row = $this->mine()->where('id', $id)->first();
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Resume not found.'], 404);
        }

        DB::transaction(function () use ($row) {
            $this->mine()->update(['is_default' => false]);
            $this->mine()->where('id', $row->id)->update(['is_default' => true, 'updated_at' => now()]);
        });

        return response()->json(['success' => true, 'message' => 'Default resume updated.', 'data' => $this->listing()]);
    }

    public function destroy($id)
    {
        $row = $this->mine()->where('id', $id)->first();
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Resume not found.'], 404);
        }

        DB::transaction(function () use ($row) {
            $this->mine()->where('id', $row->id)->delete();
            if ($row->is_default) {          // the newest one left becomes the default
                $next = $this->mine()->orderByDesc('id')->first();
                if ($next) {
                    $this->mine()->where('id', $next->id)->update(['is_default' => true, 'updated_at' => now()]);
                }
            }
        });
        // an application already sent with this CV keeps the file (the recruiter must still be able to read it)
        $sent = \App\Helpers\StoredCv::applicationsUsing($row->path);
        if (!$sent) { Storage::disk('local')->delete($row->path); }

        return response()->json([
            'success' => true,
            'message' => $sent ? "Resume deleted. It stays with the {$sent} application" . ($sent === 1 ? '' : 's') . ' you already sent with it.' : 'Resume deleted.',
            'data' => $this->listing(),
        ]);
    }

    /** the owner's own file: a PDF opens in the browser, a Word file downloads with its own name */
    public function download($id)
    {
        $row = $this->mine()->where('id', $id)->first();
        if (!$row || !Storage::disk('local')->exists($row->path)) {
            return response()->json(['success' => false, 'message' => 'Resume not found.'], 404);
        }

        $type = CvTextExtractor::typeOf($row->path) ?? 'pdf';
        $mime = ['pdf' => 'application/pdf', 'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'][$type];

        return response()->file(Storage::disk('local')->path($row->path), [
            'Content-Type' => $mime,
            'Content-Disposition' => ($type === 'pdf' ? 'inline' : 'attachment') . '; filename="' . addslashes($row->original_name) . '"',
        ]);
    }

    // ------------------------------------------------------------------ automatic import of the CV's data

    /**
     * POST user-resumes/{id}/extract : read the stored CV and return what it contains, for the import preview.
     * Nothing is saved. A file that cannot be read gives 422 with a reason the screen can show:
     * { success:false, code: unsupported|corrupt|scanned|empty, message }.
     */
    public function extract($id, CvTextExtractor $extractor, CvParser $parser, CvImporter $importer)
    {
        $row = $this->mine()->where('id', $id)->first();
        $type = $row ? CvTextExtractor::typeOf($row->path) : null;
        if (!$row || !$type || !Storage::disk('local')->exists($row->path)) {
            return response()->json(['success' => false, 'code' => 'missing', 'message' => 'Resume not found.'], 404);
        }

        @set_time_limit(60);
        try {
            $text = $extractor->extract(Storage::disk('local')->path($row->path), $type);
            $parsed = $parser->parse($text);
        } catch (CvDocumentException $e) {
            return response()->json(['success' => false, 'code' => $e->reason, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'code' => 'corrupt', 'message' => 'This file could not be read. Try saving it again as a PDF or DOCX.'], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $importer->annotate(Auth::user(), $parsed) + ['resume' => $this->shape($row), 'characters' => mb_strlen($text)],
        ]);
    }

    /**
     * POST user-resumes/{id}/import : save what the person approved in the preview (see CvImporter for the rules).
     * Body: { profile:{...}, education:[...], languages:[...], skills:[...], training:[...], seminar:[...],
     *         employment:[...], certificate:[...], set_default:bool }
     */
    public function import($id, Request $request, CvImporter $importer)
    {
        $row = $this->mine()->where('id', $id)->first();
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Resume not found.'], 404);
        }

        $v = Validator::make($request->all(), [
            'profile' => 'nullable|array', 'set_default' => 'nullable|boolean',
            'education' => 'nullable|array|max:50', 'languages' => 'nullable|array|max:50', 'skills' => 'nullable|array|max:100',
            'training' => 'nullable|array|max:50', 'seminar' => 'nullable|array|max:50', 'employment' => 'nullable|array|max:50',
            'certificate' => 'nullable|array|max:50',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first(), 'errors' => $v->errors()], 422);
        }

        $report = $importer->import(Auth::user(), $request->only(['profile', 'education', 'languages', 'skills', 'training', 'seminar', 'employment', 'certificate']));

        if ($request->boolean('set_default')) {
            DB::transaction(function () use ($row) {
                $this->mine()->update(['is_default' => false]);
                $this->mine()->where('id', $row->id)->update(['is_default' => true, 'updated_at' => now()]);
            });
        }
        $changed = count($report['profile_fields']) + $report['total_added'];

        return response()->json([
            'success' => true,
            'message' => $changed ? 'Your profile was updated from your CV.' : 'Nothing new to import: everything selected was already on your profile.',
            'data' => $report + ['resumes' => $this->listing()],
        ]);
    }

    /** the default resume of an account, or null (used by the Apply Job endpoint) */
    public static function defaultFor($code): ?object
    {
        return DB::table('user_resumes')->where('code', $code)->where('is_default', true)->first();
    }
}
