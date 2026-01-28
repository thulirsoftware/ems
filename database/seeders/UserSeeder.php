<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'name' => "Student $i",
                'email' => "user$i@gmail.com",
                'password' => Hash::make('user@123#'),
            ]);
        }
    }
}
