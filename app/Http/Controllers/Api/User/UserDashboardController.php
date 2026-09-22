<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\StudentDashboardService;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function __construct(
        private StudentDashboardService $dashboardService
    ) {}

    public function index(Request $request)
    {
        return response()->json(
            $this->dashboardService->get($request->user('users'), resolve_limit($request))
        );
    }
}
