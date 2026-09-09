<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Fixed, well-known credentials for manual login/testing.
        Admin::create([
            'name' => 'Admin One',
            'email' => 'admin1@gmail.com',
            'password' => Hash::make('admin@123#'),
        ]);

        Admin::create([
            'name' => 'Admin Two',
            'email' => 'admin2@gmail.com',
            'password' => Hash::make('admin@123#'),
        ]);

        Admin::create([
            'name' => 'Admin Three',
            'email' => 'admin3@gmail.com',
            'password' => Hash::make('admin@123#'),
        ]);
    }
}
