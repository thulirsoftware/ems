<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Fixed, well-known credentials for manual login/testing.
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'name' => "Student $i",
                'email' => "user$i@gmail.com",
                'email_verified_at' => now(),
                'password' => Hash::make('user@123#'),
            ]);
        }

        // Extra random users so listings, pagination, and batch capacity
        // have more than a handful of rows to work with.
        User::factory()->count(15)->create();
    }
}
