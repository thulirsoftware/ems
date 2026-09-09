<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Otp>
 */
class OtpFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'code' => Hash::make((string) fake()->numberBetween(100000, 999999)),
            'type' => 'email_verification',
            'expires_at' => app_now()->addMinutes(10),
        ];
    }

    public function expired(): static
    {
        return $this->state([
            'expires_at' => app_now()->subMinutes(5),
        ]);
    }

    public function passwordReset(): static
    {
        return $this->state(['type' => 'password_reset']);
    }
}
