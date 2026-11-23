<?php

namespace App\Http\Controllers\SubscriptionAdmin;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private SubscriptionAdminService $service) { }

    public function index(Request $request): JsonResponse
    {
        $request->validate(['expiring_days' => 'nullable|integer|min:1|max:365']);

        return response()->json(['success' => true, 'data' => $this->service->report((int) $request->input('expiring_days', 30))]);
    }
}
