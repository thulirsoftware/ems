<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Services\OtpService;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailVerificationCodeMail;

class UserAuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json($user, 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::where('email', $validated['email'])
            ->whereNull('deleted_at')
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (is_null($user->email_verified_at)) {
            return response()->json([
                'message' => 'Please verify your email first'
            ], 403);
        }

        $token = $user->createToken('user-token')->accessToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ])->cookie(
                'user_access_token',
                $token,
                60 * 24 * 30, // 30 days
                '/',
                null,
                false, // secure (true in prod)
                true   // httpOnly
            );
    }

    public function logout(Request $request)
    {
        $request->user('users')->token()->revoke();

        return response()->json([
            'message' => 'Logged out'
        ])->cookie('user_access_token', '', -1);
    }

    public function sendVerificationCode(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email already verified'
            ], 422);
        }

        $existing = \App\Models\Otp::where('user_id', $user->id)
            ->where('type', 'email_verification')
            ->where('expires_at', '>', app_now())
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'OTP already sent. Please wait until it expires.'
            ], 429);
        }

        $code = OtpService::generate($user->id, 'email_verification');

        Mail::to($user->email)
            ->send(new EmailVerificationCodeMail($code));

        return response()->json([
            'message' => 'Verification code sent'
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email already verified'
            ], 422);
        }

        $valid = OtpService::verify(
            $user->id,
            'email_verification',
            $validated['code']
        );

        if (!$valid) {
            return response()->json([
                'message' => 'Invalid or expired code'
            ], 422);
        }

        $user->email_verified_at = app_now();
        $user->save();

        return response()->json([
            'message' => 'Email verified successfully'
        ]);
    }
}
