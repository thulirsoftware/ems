<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AdminAccountService;

class AdminAuthController extends Controller
{
    public function __construct(
        private AdminAccountService $adminAccountService
    ) {}

    public function register(Request $request)
    {
        return response()->json($this->adminAccountService->create($request->all()), 201);
    }

    public function login(Request $request)
    {
        $result = $this->adminAccountService->login($request->all());

        return response()->json([
            'admin' => $result['admin'],
            'token' => $result['token'],
        ])->cookie(
                'admin_access_token',
                $result['token'],
                60 * 24 * 30, // 30 days
                '/',
                null,
                app()->environment('production'), // secure
                true   // httpOnly
            );
    }

    public function logout(Request $request)
    {
        $this->adminAccountService->logout($request->user('admins'));

        return response()->json([
            'message' => 'Logged out successfully'
        ])->cookie('admin_access_token', '', -1);
    }
}
