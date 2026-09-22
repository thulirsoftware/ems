<?php

namespace App\Services;

use App\Mail\EmailVerificationCodeMail;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class UserAuthService
{
    public function register(array $data): User
    {
        $validated = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ])->validate();

        return User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
    }

    public function login(array $data): array
    {
        $validated = Validator::make($data, [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ])->validate();

        $user = User::where('email', $validated['email'])
            ->whereNull('deleted_at')
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            abort(401, 'Invalid credentials');
        }

        if (is_null($user->email_verified_at)) {
            abort(403, 'Please verify your email first');
        }

        return [
            'user' => $user,
            'token' => $user->createToken('user-token')->accessToken,
        ];
    }

    public function logout(User $user): void
    {
        $user->token()->revoke();
    }

    public function sendVerificationCode(array $data): void
    {
        $validated = Validator::make($data, [
            'email' => 'required|email|exists:users,email',
        ])->validate();

        $user = User::where('email', $validated['email'])->first();

        if ($user->email_verified_at) {
            abort(422, 'Email already verified');
        }

        $existing = Otp::where('user_id', $user->id)
            ->where('type', 'email_verification')
            ->where('expires_at', '>', app_now())
            ->first();

        if ($existing) {
            abort(429, 'OTP already sent. Please wait until it expires.');
        }

        $code = OtpService::generate($user->id, 'email_verification');

        Mail::to($user->email)->send(new EmailVerificationCodeMail($code));
    }

    public function verifyEmail(array $data): void
    {
        $validated = Validator::make($data, [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string',
        ])->validate();

        $user = User::where('email', $validated['email'])->first();

        if ($user->email_verified_at) {
            abort(422, 'Email already verified');
        }

        $valid = OtpService::verify($user->id, 'email_verification', $validated['code']);

        if (!$valid) {
            abort(422, 'Invalid or expired code');
        }

        $user->email_verified_at = app_now();
        $user->save();
    }
}
