<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DashboardAnalyticsService;

class AnalyticsController extends Controller
{
    public function dashboard(Request $request, DashboardAnalyticsService $service)
    {
        $businessId = $request->user()->business_id;

        return response()->json(
            $service->getDashboard($businessId)
        );
    }
}
