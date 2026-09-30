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
            // Plain CSV files are detected as text/plain, so allow txt as a MIME type
            // but still require a .csv/.xlsx file name.
            'file' => 'required|file|extensions:csv,xlsx|mimes:csv,txt,xlsx',
        ]);

        $sheet = Excel::toArray([], $request->file('file'))[0];

        if (count($sheet) < 2) {
            return response()->json(['message' => 'File is empty'], 400);
        }

        $header = array_map(fn ($cell) => strtolower(trim((string) $cell)), $sheet[0]);

        $rows = array_map(
            // pad or trim each row to the header width — a stray trailing comma
            // would otherwise make array_combine() throw
            fn ($row) => array_combine($header, array_slice(array_pad($row, count($header), null), 0, count($header))),
            array_slice($sheet, 1)
        );

        $result = $this->userAccountService->bulkCreate($rows);

        return response()->json([
            'message' => 'Bulk upload completed',
            ...$result,
        ]);
    }
}
