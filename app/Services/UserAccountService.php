<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Str;
use Illuminate\Support\Facades\Validator;

class UserAccountService
{
    public function list()
    {
        return User::all(['id', 'name', 'email']);
    }

    public function create(array $data): User
    {
        $validated = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ])->validate();

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = Hash::make($validated['password']);
        $user->email_verified_at = app_now();
        $user->save();

        return $user;
    }

    /**
     * $rows is a plain array of ['name' => ?, 'email' => ?, 'password' => ?],
     * regardless of whether the caller parsed them from an uploaded file
     * (AdminUserController) or received them directly (UserAccountTool).
     */
    public function bulkCreate(array $rows): array
    {
        $inserted = [];
        $generatedPasswords = [];
        $errors = [];

        foreach ($rows as $index => $data) {
            try {
                if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = ['row' => $index + 2, 'error' => 'Invalid email'];
                    continue;
                }

                if (User::where('email', $data['email'])->exists()) {
                    $errors[] = ['row' => $index + 2, 'error' => 'Email already exists'];
                    continue;
                }

                $rawPassword = $data['password'] ?? null;

                if (empty($rawPassword)) {
                    $rawPassword = Str::password(12);
                    $generatedPasswords[] = ['email' => $data['email'], 'temporary_password' => $rawPassword];
                }

                $inserted[] = [
                    'name' => $data['name'] ?? '',
                    'email' => $data['email'],
                    'password' => Hash::make($rawPassword),
                    'email_verified_at' => app_now(),
                    'created_at' => app_now(),
                    'updated_at' => app_now(),
                ];
            } catch (\Throwable $e) {
                $errors[] = ['row' => $index + 2, 'error' => $e->getMessage()];
            }
        }

        if (!empty($inserted)) {
            User::insert($inserted);
        }

        return [
            'inserted' => count($inserted),
            'generated_passwords' => $generatedPasswords,
            'errors' => $errors,
        ];
    }
}
