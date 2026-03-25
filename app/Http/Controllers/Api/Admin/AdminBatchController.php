<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use Illuminate\Http\Request;

class AdminBatchController extends Controller
{
    // Get all batches
    public function index()
    {
        return response()->json(Batch::all());
    }

    // Store new batch
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'publish_date' => 'required|date',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s',
            'assessment_id' => 'required|exists:assessments,id',
            'capacity' => 'required|integer|min:1',
        ]);

        $batch = Batch::create($validated);

        return response()->json($batch, 201);
    }

    // Get single batch
    public function show($id)
    {
        return response()->json(Batch::findOrFail($id));
    }

    // Update batch
    public function update(Request $request, $id)
    {
        $batch = Batch::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'publish_date' => 'nullable|date',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s',
            'assessment_id' => 'nullable|exists:assessments,id',
            'capacity' => 'sometimes|required|integer|min:1',
        ]);

        $batch->update($validated);

        return response()->json($batch);
    }

    // Delete batch
    public function destroy($id)
    {
        Batch::findOrFail($id)->delete();

        return response()->json(['message' => 'Batch deleted']);
    }

    public function addUsers(Request $request, $id)
    {
        $batch = Batch::findOrFail($id);

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $existing = $batch->user_ids ?? [];

        $merged = array_unique(array_merge($existing, $validated['user_ids']));

        // optional capacity check
        if ($batch->capacity && count($merged) > $batch->capacity) {
            return response()->json(['message' => 'Capacity exceeded'], 422);
        }

        $batch->update([
            'user_ids' => $merged
        ]);

        $newUsers = array_values(array_diff($validated['user_ids'], $existing));

        $assignmentResponse = null;

        if (!empty($newUsers) && $batch->assessment_id) {
            $assignmentController = app(AssessmentAssignmentController::class);

            $request->merge([
                'assessment_id' => $batch->assessment_id,
                'user_ids' => $newUsers,
                'batch_id' => $batch->id,
            ]);

            $assignmentResponse = $assignmentController->store($request);
        }

        return response()->json([
            'batch' => $batch,
            'assignment' => $assignmentResponse?->getData(true)
        ]);
    }

    public function removeUsers(Request $request, $id)
    {
        $batch = Batch::findOrFail($id);

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $existing = $batch->user_ids ?? [];

        $updated = array_values(array_diff($existing, $validated['user_ids']));

        $batch->update([
            'user_ids' => $updated
        ]);

        if ($batch->assessment_id) {
            $assignmentController = app(AssessmentAssignmentController::class);

            $request->merge([
                'assessment_id' => $batch->assessment_id,
                'user_ids' => $validated['user_ids'],
            ]);

            $assignmentController->destroy($request);
        }

        return response()->json($batch);
    }
}