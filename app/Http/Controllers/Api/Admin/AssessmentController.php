<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Batch;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    private function isAssessmentLocked($assessment_id)
    {
        return AssessmentAttempt::where('assessment_id', $assessment_id)->exists();
    }

    public function index(Request $request)
    {
        $admin = $request->user('admins');

        $assessments = Assessment::where('admin_id', $admin->id)
            ->latest()
            ->get();

        $result = [];

        foreach ($assessments as $assessment) {

            $batches = Batch::where('assessment_id', $assessment->id)->get();

            foreach ($batches as $batch) {
                $result[] = [
                    ...$assessment->toArray(),
                    'batch_id' => $batch->id,
                    'publish_date' => $batch->publish_date,
                    'start_time' => $batch->start_time,
                    'end_time' => $batch->end_time,
                    'expiry_date' => $batch->expiry_date,
                    'duration_minutes' => $batch->duration_minutes,
                ];
            }
        }

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'assessment_type_id' => 'required|exists:assessment_types,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',

            'is_batch_wise' => 'required|boolean',
            'is_flexible' => 'boolean',

            'difficulty_level' => 'required|string',
            'shuffle' => 'boolean',
            'is_library' => 'boolean',
            'is_active' => 'boolean',
            'has_negative' => 'boolean',
            'negative_marks' => 'nullable|numeric|min:0',

            'publish_date' => 'nullable|date',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s',

            'expiry_date' => 'nullable|date',
            'duration_minutes' => 'nullable|integer|min:1',
        ]);

        if (
            ($validated['is_batch_wise'] ?? false) &&
            ($validated['is_flexible'] ?? false)
        ) {
            return response()->json([
                'message' => 'Assessment cannot be both batch wise and flexible'
            ], 422);
        }

        if (
            !($validated['is_batch_wise'] ?? false) &&
            !($validated['is_flexible'] ?? false)
        ) {

            if (
                empty($validated['publish_date']) ||
                empty($validated['start_time']) ||
                empty($validated['end_time'])
            ) {
                return response()->json([
                    'message' => 'Publish date, start time and end time are required'
                ], 422);
            }
        }

        if (
            ($validated['is_flexible'] ?? false) &&
            empty($validated['duration_minutes'])
        ) {
            return response()->json([
                'message' => 'Duration is required for flexible assessments'
            ], 422);
        }

        $assessment = Assessment::create([
            'admin_id' => $request->user('admins')->id,

            ...collect($validated)
                ->except([
                    'publish_date',
                    'start_time',
                    'end_time',
                    'expiry_date',
                    'duration_minutes',
                ])
                ->toArray(),
        ]);

        if (!$assessment->is_batch_wise) {

            Batch::create([
                'name' => 'individual_batch_' . $assessment->id,

                'publish_date' => $validated['publish_date'] ?? null,
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,

                'expiry_date' => $validated['expiry_date'] ?? null,
                'duration_minutes' => $validated['duration_minutes'] ?? null,

                'assessment_id' => $assessment->id,
                'capacity' => null,
            ]);
        }

        return response()->json([
            'message' => 'Assessment created successfully',
            'data' => $assessment,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $admin = $request->user('admins');

        $assessment = Assessment::where('admin_id', $admin->id)
            ->where('id', $id)
            ->firstOrFail();

        $batches = Batch::where('assessment_id', $assessment->id)->get();

        return response()->json([
            ...$assessment->toArray(),
            'batches' => $batches
        ]);
    }

    public function update(Request $request, $id)
    {
        $admin = $request->user('admins');

        $assessment = Assessment::where('admin_id', $admin->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($this->isAssessmentLocked($assessment->id)) {
            return response()->json([
                'message' => 'Cannot modify assessment after it has been attempted'
            ], 403);
        }

        $validated = $request->validate([
            'assessment_type_id' => 'sometimes|exists:assessment_types,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',

            'difficulty_level' => 'nullable|string',
            'shuffle' => 'boolean',
            'is_library' => 'boolean',
            'is_active' => 'boolean',
            'has_negative' => 'boolean',
            'negative_marks' => 'nullable|numeric|min:0',

            'is_batch_wise' => 'boolean',
            'is_flexible' => 'boolean',

            'publish_date' => 'nullable|date',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s',

            'expiry_date' => 'nullable|date',
            'duration_minutes' => 'nullable|integer|min:1',
        ]);

        if (!$assessment->is_batch_wise) {

            if ($assessment->is_flexible) {

                if (
                    array_key_exists('duration_minutes', $validated) &&
                    !$validated['duration_minutes']
                ) {
                    return response()->json([
                        'message' => 'Duration is required for flexible assessments'
                    ], 422);
                }

            } else {

                if (
                    (
                        array_key_exists('publish_date', $validated) &&
                        !$validated['publish_date']
                    ) ||
                    (
                        array_key_exists('start_time', $validated) &&
                        !$validated['start_time']
                    ) ||
                    (
                        array_key_exists('end_time', $validated) &&
                        !$validated['end_time']
                    )
                ) {
                    return response()->json([
                        'message' => 'Publish date, start time and end time are required'
                    ], 422);
                }
            }
        }

        if (
            isset($validated['is_batch_wise']) &&
            $validated['is_batch_wise'] != $assessment->is_batch_wise
        ) {
            return response()->json([
                'message' => 'Changing batch mode is not allowed'
            ], 422);
        }

        if (
            isset($validated['is_flexible']) &&
            $validated['is_flexible'] != $assessment->is_flexible
        ) {
            return response()->json([
                'message' => 'Changing flexible mode is not allowed'
            ], 422);
        }

        $assessment->update(
            collect($validated)->except([
                'publish_date',
                'start_time',
                'end_time',
                'expiry_date',
                'duration_minutes',
            ])->toArray()
        );

        if (!$assessment->is_batch_wise) {

            $batch = Batch::where(
                'assessment_id',
                $assessment->id
            )->first();

            if ($batch) {

                $batch->update([
                    'publish_date' => $validated['publish_date'] ?? $batch->publish_date,
                    'start_time' => $validated['start_time'] ?? $batch->start_time,
                    'end_time' => $validated['end_time'] ?? $batch->end_time,

                    'expiry_date' => $validated['expiry_date'] ?? $batch->expiry_date,
                    'duration_minutes' => $validated['duration_minutes'] ?? $batch->duration_minutes,
                ]);
            }
        }

        return response()->json([
            'message' => 'Assessment updated successfully',
            'data' => $assessment
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $admin = $request->user('admins');

        $assessment = Assessment::where('admin_id', $admin->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($this->isAssessmentLocked($assessment->id)) {
            return response()->json([
                'message' => 'Cannot delete assessment after it has been attempted'
            ], 403);
        }

        $assessment->delete();

        return response()->json([
            'message' => 'Assessment deleted successfully'
        ]);
    }

    public function library(Request $request)
    {
        $admin = $request->user('admins');

        $assessments = Assessment::where('admin_id', $admin->id)
            ->where('is_library', true)
            ->latest()
            ->get();

        $result = [];

        foreach ($assessments as $assessment) {

            $batches = Batch::where('assessment_id', $assessment->id)->get();

            foreach ($batches as $batch) {
                $result[] = [
                    ...$assessment->toArray(),
                    'batch_id' => $batch->id,
                    'publish_date' => $batch->publish_date,
                    'start_time' => $batch->start_time,
                    'end_time' => $batch->end_time,
                    'expiry_date' => $batch->expiry_date,
                    'duration_minutes' => $batch->duration_minutes,
                ];
            }
        }

        return response()->json($result);
    }

    public function upcoming(Request $request)
    {
        $admin = $request->user('admins');

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $assessments = Assessment::where('admin_id', $admin->id)
            ->where('is_active', true)
            ->get();

        $result = [];

        foreach ($assessments as $assessment) {

            if ($assessment->is_flexible) {
                continue;
            }

            $batches = Batch::where('assessment_id', $assessment->id)->get();

            foreach ($batches as $batch) {

                if (
                    $batch->publish_date == $today &&
                    $batch->start_time > $nowTime
                ) {
                    $result[] = [
                        ...$assessment->toArray(),
                        'batch_id' => $batch->id,
                        'publish_date' => $batch->publish_date,
                        'start_time' => $batch->start_time,
                        'end_time' => $batch->end_time,
                    ];
                }
            }
        }

        return response()->json($result);
    }

    public function running(Request $request)
    {
        $admin = $request->user('admins');

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $assessments = Assessment::where('admin_id', $admin->id)
            ->where('is_active', true)
            ->get();

        $result = [];

        foreach ($assessments as $assessment) {

            $batches = Batch::where('assessment_id', $assessment->id)->get();

            foreach ($batches as $batch) {

                $isRunning = false;

                if ($assessment->is_flexible) {
                    $isRunning =
                        !$batch->expiry_date ||
                        $batch->expiry_date >= $today;
                } else {
                    $isRunning =
                        $batch->publish_date == $today &&
                        $batch->start_time <= $nowTime &&
                        $batch->end_time >= $nowTime;
                }

                if (!$isRunning) {
                    continue;
                }

                $result[] = [
                    ...$assessment->toArray(),
                    'batch_id' => $batch->id,
                    'publish_date' => $batch->publish_date,
                    'start_time' => $batch->start_time,
                    'end_time' => $batch->end_time,
                    'expiry_date' => $batch->expiry_date,
                    'duration_minutes' => $batch->duration_minutes,
                ];
            }
        }

        return response()->json($result);
    }
}