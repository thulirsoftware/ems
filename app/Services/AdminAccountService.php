<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminAccountService
{
    public function create(array $data): Admin
    {
        $validated = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:8',
        ])->validate();

        return Admin::create([
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

        $admin = Admin::where('email', $validated['email'])
            ->whereNull('deleted_at')
            ->first();

        if (!$admin || !Hash::check($validated['password'], $admin->password)) {
            abort(401, 'Invalid credentials');
        }

        return [
            'admin' => $admin,
            'token' => $admin->createToken('admin-token')->accessToken,
        ];
    }

    public function logout(Admin $admin): void
    {
        $admin->token()->revoke();
    }
}
