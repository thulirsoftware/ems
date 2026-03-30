<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\User;
use Illuminate\Http\Request;

class AdminBatchController extends Controller
{
    private function isBatchLocked($batch_id)
    {
        return \App\Models\AssessmentAttempt::where('batch_id', $batch_id)->exists();
    }

    public function index(Request $request)
    {
        $admin = $request->user('admins');

        return response()->json(
            Batch::whereHas('assessment', function ($q) use ($admin) {
                $q->where('admin_id', $admin->id);
            })->get()
        );
    }

    public function getBatchesByAssessment(Request $request, $assessment_id)
    {
        $admin = $request->user('admins');

        $assessment = Assessment::where('id', $assessment_id)
            ->where('admin_id', $admin->id)
            ->firstOrFail();

        $batches = Batch::where('assessment_id', $assessment->id)->get();

        return response()->json($batches);
    }

    public function store(Request $request)
    {
        $admin = $request->user('admins');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'publish_date' => 'required|date',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s',
            'assessment_id' => 'required|exists:assessments,id',
            'capacity' => 'required|integer|min:1',
        ]);

        $assessment = Assessment::where('id', $validated['assessment_id'])
            ->where('admin_id', $admin->id)
            ->firstOrFail();

        $conflict = Batch::where('assessment_id', $assessment->id)
            ->whereDate('publish_date', $validated['publish_date'])
            ->where(function ($q) use ($validated) {
                $q->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time']);
            })
            ->exists();

        if ($conflict) {
            return response()->json(['message' => 'Batch time conflict'], 422);
        }

        if (!$assessment->is_batch_wise) {
            $exists = Batch::where('assessment_id', $assessment->id)
                ->where('name', 'individual_batch_' . $assessment->id)
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'Default batch exists'], 422);
            }
        }

        return response()->json(Batch::create($validated), 201);
    }

    public function show(Request $request, $id)
    {
        $admin = $request->user('admins');

        $batch = Batch::where('id', $id)
            ->whereHas('assessment', function ($q) use ($admin) {
                $q->where('admin_id', $admin->id);
            })
            ->firstOrFail();

        return response()->json($batch);
    }

    public function update(Request $request, $id)
    {
        $admin = $request->user('admins');

        $batch = Batch::where('id', $id)
            ->whereHas('assessment', function ($q) use ($admin) {
                $q->where('admin_id', $admin->id);
            })
            ->firstOrFail();

        if ($this->isBatchLocked($batch->id)) {
            return response()->json([
                'message' => 'Cannot modify batch after it has been attempted'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'publish_date' => 'nullable|date',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s',
            'capacity' => 'sometimes|required|integer|min:1',
        ]);

        $start = $validated['start_time'] ?? $batch->start_time;
        $end = $validated['end_time'] ?? $batch->end_time;
        $date = $validated['publish_date'] ?? $batch->publish_date;

        $conflict = Batch::where('assessment_id', $batch->assessment_id)
            ->whereDate('publish_date', $date)
            ->where('id', '!=', $batch->id)
            ->where(function ($q) use ($start, $end) {
                $q->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            })
            ->exists();

        if ($conflict) {
            return response()->json(['message' => 'Batch time conflict'], 422);
        }

        $assessment = Assessment::find($batch->assessment_id);

        if ($assessment && !$assessment->is_batch_wise) {

            $defaultName = 'individual_batch_' . $assessment->id;
            $newName = $validated['name'] ?? $batch->name;

            if ($newName === $defaultName) {

                $existingDefault = Batch::where('assessment_id', $assessment->id)
                    ->where('name', $defaultName)
                    ->where('id', '!=', $batch->id)
                    ->exists();

                if ($existingDefault) {
                    return response()->json([
                        'message' => 'Default batch already exists for this assessment'
                    ], 422);
                }
            }
        }

        $batch->update($validated);

        return response()->json($batch);
    }

    public function destroy(Request $request, $id)
    {
        $admin = $request->user('admins');

        $batch = Batch::where('id', $id)
            ->whereHas('assessment', function ($q) use ($admin) {
                $q->where('admin_id', $admin->id);
            })
            ->firstOrFail();

        if ($this->isBatchLocked($batch->id)) {
            return response()->json([
                'message' => 'Cannot delete batch after it has been attempted'
            ], 403);
        }

        $batch->delete();

        return response()->json(['message' => 'Batch deleted']);
    }

    function getUsersByBatchId($id)
    {
        return User::whereIn(
            'id',
            AssessmentAssignment::where('batch_id', $id)
                ->pluck('user_id')
        )->get();
    }

    public function addUsers(Request $request, $id)
    {
        $admin = $request->user('admins');

        $batch = Batch::where('id', $id)
            ->whereHas('assessment', function ($q) use ($admin) {
                $q->where('admin_id', $admin->id);
            })
            ->firstOrFail();

        if ($this->isBatchLocked($batch->id)) {
            return response()->json([
                'message' => 'Cannot add users after batch has started'
            ], 403);
        }

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        // ✅ correct capacity check (no duplicates)
        $currentUsers = AssessmentAssignment::where('batch_id', $batch->id)
            ->pluck('user_id')
            ->toArray();

        $newUsers = array_diff($validated['user_ids'], $currentUsers);

        if ($batch->capacity && (count($currentUsers) + count($newUsers)) > $batch->capacity) {
            return response()->json(['message' => 'Capacity exceeded'], 422);
        }

        $assignmentController = app(AssessmentAssignmentController::class);

        $request->merge([
            'assessment_id' => $batch->assessment_id,
            'user_ids' => $validated['user_ids'],
            'batch_id' => $batch->id,
        ]);

        return $assignmentController->store($request);
    }

    public function removeUsers(Request $request, $id)
    {
        $admin = $request->user('admins');

        $batch = Batch::where('id', $id)
            ->whereHas('assessment', function ($q) use ($admin) {
                $q->where('admin_id', $admin->id);
            })
            ->firstOrFail();

        if ($this->isBatchLocked($batch->id)) {
            return response()->json([
                'message' => 'Cannot remove users after batch has started'
            ], 403);
        }

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $assignmentController = app(AssessmentAssignmentController::class);

        $request->merge([
            'assessment_id' => $batch->assessment_id,
            'user_ids' => $validated['user_ids'],
            'batch_id' => $batch->id, // 🔥 REQUIRED
        ]);

        return $assignmentController->destroy($request);
    }
}