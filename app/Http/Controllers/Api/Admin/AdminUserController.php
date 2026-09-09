<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(User::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = Hash::make($validated['password']);
        $user->email_verified_at = app_now();
        $user->save();

        return response()->json($user, 201);
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx'
        ]);

        $file = $request->file('file');

        $rows = Excel::toArray([], $file)[0]; // first sheet

        if (count($rows) < 2) {
            return response()->json(['message' => 'File is empty'], 400);
        }

        $header = array_map('strtolower', $rows[0]); // normalize header

        $users = [];
        $generatedPasswords = [];
        $errors = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            try {
                $data = array_combine($header, $row);

                if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = ['row' => $index + 2, 'error' => 'Invalid email'];
                    continue;
                }

                if (User::where('email', $data['email'])->exists()) {
                    $errors[] = ['row' => $index + 2, 'error' => 'Email already exists'];
                    continue;
                }

                // A shared, guessable fallback password (e.g. "123456") would
                // leave every such account equally vulnerable. Generate a
                // unique temporary password per user instead and report it
                // back so the admin can distribute it.
                $rawPassword = $data['password'] ?? null;

                if (empty($rawPassword)) {
                    $rawPassword = Str::password(12);
                    $generatedPasswords[] = ['email' => $data['email'], 'temporary_password' => $rawPassword];
                }

                $users[] = [
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

        if (!empty($users)) {
            User::insert($users);
        }

        return response()->json([
            'message' => 'Bulk upload completed',
            'inserted' => count($users),
            'generated_passwords' => $generatedPasswords,
            'errors' => $errors
        ]);
    }
}
