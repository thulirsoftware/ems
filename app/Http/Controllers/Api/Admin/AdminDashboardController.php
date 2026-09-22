<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function __construct(
        private AdminDashboardService $dashboardService
    ) {}

    public function index(Request $request)
    {
        return response()->json(
            $this->dashboardService->get($request->user('admins'), resolve_limit($request))
        );
    }
}
