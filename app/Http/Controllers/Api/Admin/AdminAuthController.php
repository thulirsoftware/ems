<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:8',
        ]);

        $admin = Admin::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json($admin, 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        $admin = Admin::where('email', $validated['email'])
            ->whereNull('deleted_at')
            ->first();

        if (!$admin || !Hash::check($validated['password'], $admin->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $admin->createToken('admin-token')->accessToken;

        return response()->json([
            'admin' => $admin,
            'token' => $token
        ])->cookie(
                'admin_access_token',
                $token,
                60 * 24 * 30, // 30 days
                '/',
                null,
                app()->environment('production'), // secure
                true   // httpOnly
            );
    }

    public function logout(Request $request)
    {
        $request->user('admins')->token()->revoke();

        return response()->json([
            'message' => 'Logged out successfully'
        ])->cookie('admin_access_token', '', -1);
    }
}
