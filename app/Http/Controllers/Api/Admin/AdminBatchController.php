<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\BatchService;
use Illuminate\Http\Request;

class AdminBatchController extends Controller
{
    public function __construct(
        private BatchService $batchService
    ) {}

    public function index(Request $request)
    {
        return response()->json($this->batchService->list($request->user('admins')));
    }

    public function getBatchesByAssessment(Request $request, $assessment_id)
    {
        return response()->json($this->batchService->listByAssessment($request->user('admins'), $assessment_id));
    }

    public function store(Request $request)
    {
        return response()->json($this->batchService->create($request->user('admins'), $request->all()), 201);
    }

    public function show(Request $request, $id)
    {
        return response()->json($this->batchService->get($request->user('admins'), $id));
    }

    public function update(Request $request, $id)
    {
        return response()->json($this->batchService->update($request->user('admins'), $id, $request->all()));
    }

    public function destroy(Request $request, $id)
    {
        $this->batchService->delete($request->user('admins'), $id);

        return response()->json(['message' => 'Batch deleted']);
    }

    public function getUsersByBatchId(Request $request, $id)
    {
        return response()->json($this->batchService->listUsers($request->user('admins'), $id));
    }

    public function addUsers(Request $request, $id)
    {
        $result = $this->batchService->addUsers($request->user('admins'), $id, $request->all());

        return response()->json([
            'message' => 'Assignment completed',
            ...$result,
        ], 201);
    }

    public function removeUsers(Request $request, $id)
    {
        $deleted = $this->batchService->removeUsers($request->user('admins'), $id, $request->all());

        return response()->json([
            'message' => 'Users unassigned successfully',
            'deleted_count' => $deleted,
        ]);
    }
}
