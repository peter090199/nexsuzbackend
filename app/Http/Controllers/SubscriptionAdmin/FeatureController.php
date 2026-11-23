<?php

namespace App\Http\Controllers\SubscriptionAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionAdmin\SaveFeatureRequest;
use App\Http\Resources\SubscriptionAdmin\FeatureResource;
use App\Models\AccountPlan\SubscriptionFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/** Feature catalog (master admin). */
class FeatureController extends Controller
{
    public function index(): JsonResponse
    {
        $used = DB::table('userplandetails')->where('recordStatus', 'active')
            ->whereNotNull('feature_code')
            ->select('feature_code', DB::raw('COUNT(DISTINCT planId) as c'))->groupBy('feature_code')->pluck('c', 'feature_code');

        $features = SubscriptionFeature::orderBy('category')->orderBy('name')->get()
            ->each(fn ($f) => $f->plans_count = (int) ($used[$f->feature_code] ?? 0));

        return response()->json([
            'success'    => true,
            'data'       => FeatureResource::collection($features),
            'categories' => SubscriptionFeature::select('category')->distinct()->orderBy('category')->pluck('category'),
        ]);
    }

    public function store(SaveFeatureRequest $request): JsonResponse
    {
        $feature = SubscriptionFeature::create($request->validated() + ['is_active' => true]);

        return response()->json(['success' => true, 'message' => 'Feature created.', 'data' => new FeatureResource($feature)], 201);
    }

    public function update(SaveFeatureRequest $request, int $id): JsonResponse
    {
        $feature = SubscriptionFeature::findOrFail($id);
        $feature->update($request->safe()->except('feature_code')); // key is immutable

        return response()->json(['success' => true, 'message' => 'Feature updated.', 'data' => new FeatureResource($feature)]);
    }

    public function toggle(int $id): JsonResponse
    {
        $feature = SubscriptionFeature::findOrFail($id);
        $feature->update(['is_active' => !$feature->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Feature ' . ($feature->is_active ? 'enabled' : 'disabled') . '.',
            'data'    => new FeatureResource($feature),
        ]);
    }
}
