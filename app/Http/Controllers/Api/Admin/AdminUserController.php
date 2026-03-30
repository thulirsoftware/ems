<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class AdminUserController extends Controller
{
    public function index()
    {
        return User::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = Hash::make($validated['password']);
        $user->email_verified_at = now();
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

                $users[] = [
                    'name' => $data['name'] ?? '',
                    'email' => $data['email'],
                    'password' => Hash::make($data['password'] ?? '123456'),
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

            } catch (\Exception $e) {
                $errors[] = ['row' => $index + 2, 'error' => $e->getMessage()];
            }
        }

        if (!empty($users)) {
            User::insert($users);
        }

        return response()->json([
            'message' => 'Bulk upload completed',
            'inserted' => count($users),
            'errors' => $errors
        ]);
    }
}
