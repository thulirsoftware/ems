<?php

namespace App\Services;

use App\Models\Otp;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    public static function generate(int $userId, string $type, int $minutes = 10): string
    {
        // remove previous active OTPs of same type
        Otp::where('user_id', $userId)
            ->where('type', $type)
            ->delete();

        $code = (string) random_int(100000, 999999);

        Otp::create([
            'user_id' => $userId,
            'type' => $type,
            'code' => Hash::make($code),
            'expires_at' => app_now()->addMinutes($minutes),
        ]);

        return $code;
    }

    public static function verify(int $userId, string $type, string $code): bool
    {
        $otp = Otp::where('user_id', $userId)
            ->where('type', $type)
            ->latest()
            ->first();

        if (!$otp)
            return false;
        if (app_now()->gt($otp->expires_at))
            return false;
        if (!Hash::check($code, $otp->code))
            return false;

        $otp->delete(); // one-time use

        return true;
    }
}
