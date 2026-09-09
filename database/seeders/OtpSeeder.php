<?php

namespace Database\Seeders;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OtpSeeder extends Seeder
{
    public function run(): void
    {
        $user4 = User::where('email', 'user4@gmail.com')->firstOrFail();
        $user5 = User::where('email', 'user5@gmail.com')->firstOrFail();

        // Still active, matching OtpService::generate()'s 10-minute window.
        Otp::create([
            'user_id' => $user4->id,
            'code' => Hash::make('123456'),
            'type' => 'email_verification',
            'expires_at' => app_now()->addMinutes(10),
        ]);

        // Already expired, demonstrating OtpService::verify()'s rejection path.
        Otp::create([
            'user_id' => $user5->id,
            'code' => Hash::make('654321'),
            'type' => 'email_verification',
            'expires_at' => app_now()->subMinutes(5),
        ]);

        // Password reset flow.
        Otp::create([
            'user_id' => $user5->id,
            'code' => Hash::make('999888'),
            'type' => 'password_reset',
            'expires_at' => app_now()->addMinutes(10),
        ]);
    }
}
