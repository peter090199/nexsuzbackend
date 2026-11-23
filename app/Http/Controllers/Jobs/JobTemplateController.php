<?php

namespace App\Http\Controllers\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Jobs\JobTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Job posting templates & themes.
 *
 *  Read  (any signed-in user): published templates to choose from, one template to render a job.
 *  Write (DEF-MASTERADMIN only): create, edit, duplicate, delete, publish, set the default.
 *
 * The write routes sit behind the `masteradmin` middleware AND every write method re-checks the
 * `manage-job-templates` gate, so nothing depends on the Angular UI hiding a button.
 */
class JobTemplateController extends Controller
{
    private const HEX = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];

    // =========================================================================== read (signed-in users)

    /** GET job-templates : published templates a poster can pick, plus which one is the default */
    public function index()
    {
        $templates = JobTemplate::published()->orderByDesc('is_default')->orderBy('name')->get();

        return response()->json([
            'success'    => true,
            'data'       => $templates->map->toPublic()->values(),
            'default_id' => optional($templates->firstWhere('is_default', true))->id,
        ]);
    }

    /**
     * GET job-templates/{id} : one template, to render a job.
     * Published templates are open to everyone signed in. A template that is no longer published (or was
     * deleted) is still returned when a job uses it, so already-posted jobs keep their look.
     */
    public function show($id)
    {
        $template = JobTemplate::withTrashed()->find($id);
        if (!$template) {
            return response()->json(['success' => false, 'message' => 'Template not found.'], 404);
        }

        $visible = ($template->status === 'published' && !$template->trashed())
            || DB::table('jobposting')->where('template_id', $template->id)->exists()
            || Gate::allows('manage-job-templates');

        if (!$visible) {
            return response()->json(['success' => false, 'message' => 'Template not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $template->toPublic()]);
    }

    // =========================================================================== write (DEF-MASTERADMIN)

    private function deny()
    {
        if (Gate::denies('manage-job-templates')) {
            return response()->json(['success' => false, 'message' => 'Only a master administrator can manage job posting templates.'], 403);
        }

        return null;
    }

    /** GET admin/job-templates : everything, drafts included, with how many jobs use each */
    public function adminIndex()
    {
        if ($r = $this->deny()) { return $r; }

        $usage = DB::table('jobposting')->whereNotNull('template_id')
            ->select('template_id', DB::raw('COUNT(*) as jobs'))->groupBy('template_id')->pluck('jobs', 'template_id');

        $rows = JobTemplate::orderByDesc('is_default')->orderBy('name')->get()->map(function ($t) use ($usage) {
            return $t->toPublic() + ['jobs_using' => (int) ($usage[$t->id] ?? 0)];
        });

        return response()->json(['success' => true, 'data' => $rows->values()]);
    }

    /** POST admin/job-templates */
    public function store(Request $request)
    {
        if ($r = $this->deny()) { return $r; }

        $data = $this->validated($request);
        $template = JobTemplate::create($data + [
            'is_default' => false,
            'status'     => 'draft',
            'created_by' => Auth::user()->code,
            'updated_by' => Auth::user()->code,
        ]);

        return response()->json(['success' => true, 'message' => 'Template created.', 'data' => $template->toPublic()], 201);
    }

    /** PUT admin/job-templates/{id} */
    public function update(Request $request, $id)
    {
        if ($r = $this->deny()) { return $r; }

        $template = JobTemplate::findOrFail($id);
        $template->update($this->validated($request) + ['updated_by' => Auth::user()->code]);

        return response()->json(['success' => true, 'message' => 'Template saved.', 'data' => $template->fresh()->toPublic()]);
    }

    /** POST admin/job-templates/{id}/duplicate : the copy is a draft and never the default */
    public function duplicate($id)
    {
        if ($r = $this->deny()) { return $r; }

        $source = JobTemplate::findOrFail($id);
        $copy = JobTemplate::create([
            'name'        => mb_substr($source->name, 0, 112) . ' (copy)',
            'description' => $source->description,
            'theme'       => $source->theme,
            'sections'    => $source->sections,
            'is_default'  => false,
            'status'      => 'draft',
            'created_by'  => Auth::user()->code,
            'updated_by'  => Auth::user()->code,
        ]);

        return response()->json(['success' => true, 'message' => 'Template duplicated.', 'data' => $copy->toPublic()], 201);
    }

    /** POST admin/job-templates/{id}/publish */
    public function publish($id)
    {
        if ($r = $this->deny()) { return $r; }

        $template = JobTemplate::findOrFail($id);
        $template->update(['status' => 'published', 'updated_by' => Auth::user()->code]);

        return response()->json(['success' => true, 'message' => 'Template published.', 'data' => $template->fresh()->toPublic()]);
    }

    /** POST admin/job-templates/{id}/unpublish : the default can't be hidden */
    public function unpublish($id)
    {
        if ($r = $this->deny()) { return $r; }

        $template = JobTemplate::findOrFail($id);
        if ($template->is_default) {
            return response()->json(['success' => false, 'message' => 'The default template can\'t be unpublished. Choose another default first.'], 422);
        }
        $template->update(['status' => 'draft', 'updated_by' => Auth::user()->code]);

        return response()->json(['success' => true, 'message' => 'Template unpublished.', 'data' => $template->fresh()->toPublic()]);
    }

    /** POST admin/job-templates/{id}/default : only a published template can be the default */
    public function setDefault($id)
    {
        if ($r = $this->deny()) { return $r; }

        $template = JobTemplate::findOrFail($id);
        if ($template->status !== 'published') {
            return response()->json(['success' => false, 'message' => 'Publish the template before making it the default.'], 422);
        }

        DB::transaction(function () use ($template) {
            JobTemplate::where('is_default', true)->update(['is_default' => false]);
            $template->update(['is_default' => true, 'updated_by' => Auth::user()->code]);
        });

        return response()->json(['success' => true, 'message' => 'Default template updated.', 'data' => $template->fresh()->toPublic()]);
    }

    /** DELETE admin/job-templates/{id} : soft delete, so jobs already posted with it keep their look */
    public function destroy($id)
    {
        if ($r = $this->deny()) { return $r; }

        $template = JobTemplate::findOrFail($id);
        if ($template->is_default) {
            return response()->json(['success' => false, 'message' => 'The default template can\'t be deleted. Choose another default first.'], 422);
        }
        $template->delete();

        return response()->json(['success' => true, 'message' => 'Template deleted.']);
    }

    // =========================================================================== validation

    /** colours, fonts, shapes and layouts only accept known values: they end up inside CSS in the browser */
    private function validated(Request $request): array
    {
        $v = $request->validate([
            'name'        => 'required|string|max:120',
            'description' => 'nullable|string|max:255',

            'theme'                 => 'required|array',
            'theme.preset'          => 'nullable|string|max:40',
            'theme.primary'         => self::HEX,
            'theme.accent'          => self::HEX,
            'theme.background'      => self::HEX,
            'theme.surface'         => self::HEX,
            'theme.text'            => self::HEX,
            'theme.font'            => 'required|in:' . implode(',', JobTemplate::FONTS),
            'theme.radius'          => 'required|integer|between:0,32',
            'theme.buttonStyle'     => 'required|in:' . implode(',', JobTemplate::BUTTON_STYLES),
            'theme.buttonShape'     => 'required|in:' . implode(',', JobTemplate::BUTTON_SHAPES),
            'theme.headerStyle'     => 'required|in:' . implode(',', JobTemplate::HEADER_STYLES),
            'theme.backgroundStyle' => 'required|in:' . implode(',', JobTemplate::BACKGROUND_STYLES),
            'theme.layout'          => 'required|in:' . implode(',', JobTemplate::LAYOUTS),

            'sections'           => 'required|array|min:1|max:' . count(JobTemplate::SECTION_KEYS),
            'sections.*.key'     => 'required|in:' . implode(',', JobTemplate::SECTION_KEYS),
            'sections.*.label'   => 'nullable|string|max:60',
            'sections.*.enabled' => 'nullable|boolean',
        ]);

        $keys = array_column($v['sections'], 'key');
        if (count($keys) !== count(array_unique($keys))) {
            throw ValidationException::withMessages(['sections' => 'A section can only appear once.']);
        }

        $theme = array_intersect_key($v['theme'], array_flip([
            'preset', 'primary', 'accent', 'background', 'surface', 'text', 'font', 'radius',
            'buttonStyle', 'buttonShape', 'headerStyle', 'backgroundStyle', 'layout',
        ]));

        return [
            'name'        => trim($v['name']),
            'description' => isset($v['description']) ? trim($v['description']) : null,
            'theme'       => $theme,
            'sections'    => JobTemplate::normalizeSections($v['sections']),
        ];
    }
}
