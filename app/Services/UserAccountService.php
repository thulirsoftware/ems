<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
        $seenEmails = [];

        foreach ($rows as $index => $data) {
            try {
                $data['email'] = trim((string) ($data['email'] ?? ''));

                if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = ['row' => $index + 2, 'error' => 'Invalid email'];
                    continue;
                }

                // The whole batch is inserted at once, so a repeated email
                // inside the file would fail the insert for every row.
                $emailKey = strtolower($data['email']);

                if (isset($seenEmails[$emailKey])) {
                    $errors[] = ['row' => $index + 2, 'error' => 'Duplicate email in file'];
                    continue;
                }

                if (User::withTrashed()->where('email', $data['email'])->exists()) {
                    $errors[] = ['row' => $index + 2, 'error' => 'Email already exists'];
                    continue;
                }

                $rawPassword = isset($data['password']) ? (string) $data['password'] : null;

                if ($rawPassword !== null && $rawPassword !== '' && strlen($rawPassword) < 8) {
                    $errors[] = ['row' => $index + 2, 'error' => 'Password must be at least 8 characters'];
                    continue;
                }

                $seenEmails[$emailKey] = true;

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
