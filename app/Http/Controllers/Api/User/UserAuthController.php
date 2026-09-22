<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\UserAuthService;
use Illuminate\Http\Request;

class UserAuthController extends Controller
{
    public function __construct(
        private UserAuthService $userAuthService
    ) {}

    public function register(Request $request)
    {
        return response()->json($this->userAuthService->register($request->all()), 201);
    }

    public function login(Request $request)
    {
        $result = $this->userAuthService->login($request->all());

        return response()->json([
            'user' => $result['user'],
            'token' => $result['token'],
        ])->cookie(
                'user_access_token',
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
        $this->userAuthService->logout($request->user('users'));

        return response()->json([
            'message' => 'Logged out'
        ])->cookie('user_access_token', '', -1);
    }

    public function sendVerificationCode(Request $request)
    {
        $this->userAuthService->sendVerificationCode($request->all());

        return response()->json([
            'message' => 'Verification code sent'
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $this->userAuthService->verifyEmail($request->all());

        return response()->json([
            'message' => 'Email verified successfully'
        ]);
    }
}
