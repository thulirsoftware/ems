<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\UserAccountService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AdminUserController extends Controller
{
    public function __construct(
        private UserAccountService $userAccountService
    ) {}

    public function index(Request $request)
    {
        return response()->json($this->userAccountService->list());
    }

    public function store(Request $request)
    {
        return response()->json($this->userAccountService->create($request->all()), 201);
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx',
        ]);

        $sheet = Excel::toArray([], $request->file('file'))[0];

        if (count($sheet) < 2) {
            return response()->json(['message' => 'File is empty'], 400);
        }

        $header = array_map('strtolower', $sheet[0]);

        $rows = array_map(
            fn ($row) => array_combine($header, $row),
            array_slice($sheet, 1)
        );

        $result = $this->userAccountService->bulkCreate($rows);

        return response()->json([
            'message' => 'Bulk upload completed',
            ...$result,
        ]);
    }
}
