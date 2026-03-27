<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Batch;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
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

            'publish_date' => 'required_if:is_batch_wise,false|date',
            'start_time' => 'required_if:is_batch_wise,false|date_format:H:i:s',
            'end_time' => 'required_if:is_batch_wise,false|date_format:H:i:s',

            'difficulty_level' => 'required|string',
            'shuffle' => 'boolean',
            'is_library' => 'boolean',
            'is_active' => 'boolean',
            'has_negative' => 'boolean',
            'negative_marks' => 'nullable|numeric|min:0',
        ]);

        $assessment = Assessment::create([
            'admin_id' => $request->user('admins')->id,
            ...$validated,
        ]);

        // create default batch for individual
        if (!$assessment->is_batch_wise) {
            Batch::create([
                'name' => 'individual_batch_' . $assessment->id,
                'publish_date' => $assessment->publish_date,
                'start_time' => $assessment->start_time,
                'end_time' => $assessment->end_time,
                'assessment_id' => $assessment->id,
                'capacity' => null,
            ]);
        }

        return response()->json($assessment, 201);
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

        $validated = $request->validate([
            'assessment_type_id' => 'sometimes|exists:assessment_types,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'publish_date' => 'nullable|date',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s',
            'difficulty_level' => 'nullable|string',
            'shuffle' => 'boolean',
            'is_library' => 'boolean',
            'is_active' => 'boolean',
            'has_negative' => 'boolean',
            'negative_marks' => 'nullable|numeric|min:0',
            'is_batch_wise' => 'boolean'
        ]);

        $assessment->update($validated);

        // sync default batch for individual
        if (!$assessment->is_batch_wise) {
            $batch = Batch::where('assessment_id', $assessment->id)
                ->where('name', 'individual_batch_' . $assessment->id)
                ->first();

            if ($batch) {
                $batch->update([
                    'publish_date' => $assessment->publish_date,
                    'start_time' => $assessment->start_time,
                    'end_time' => $assessment->end_time,
                ]);
            }
        }

        return response()->json($assessment);
    }

    public function destroy(Request $request, $id)
    {
        $admin = $request->user('admins');

        $assessment = Assessment::where('admin_id', $admin->id)
            ->where('id', $id)
            ->firstOrFail();

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

            $batches = \App\Models\Batch::where('assessment_id', $assessment->id)->get();

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

                if (
                    $batch->publish_date == $today &&
                    $batch->start_time <= $nowTime &&
                    $batch->end_time >= $nowTime
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
}