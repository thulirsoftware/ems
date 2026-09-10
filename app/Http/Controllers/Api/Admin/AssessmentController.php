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

    // Validation rules for the schedule fields, shared by store() and
    // update(). `prohibited_unless` rejects fields that don't belong to the
    // given scheduling_type (including all six for batch_wise, which manages
    // its schedule entirely through Batch CRUD instead); `required_if` is
    // added on top for store(), where every field for the chosen type must
    // be supplied up front.
    private function scheduleRules(bool $required): array
    {
        $fixedExtra = $required ? ['required_if:scheduling_type,fixed'] : [];
        $flexibleExtra = $required ? ['required_if:scheduling_type,flexible'] : [];

        return [
            'publish_date' => ['nullable', 'date', 'prohibited_unless:scheduling_type,fixed', ...$fixedExtra],
            'start_time' => ['nullable', 'date_format:H:i:s', 'prohibited_unless:scheduling_type,fixed', ...$fixedExtra],
            'end_time' => ['nullable', 'date_format:H:i:s', 'prohibited_unless:scheduling_type,fixed', ...$fixedExtra],

            'start_date' => ['nullable', 'date', 'prohibited_unless:scheduling_type,flexible', ...$flexibleExtra],
            'end_date' => ['nullable', 'date', 'prohibited_unless:scheduling_type,flexible', ...$flexibleExtra],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'prohibited_unless:scheduling_type,flexible', ...$flexibleExtra],
        ];
    }

    public function index(Request $request)
    {
        $admin = $request->user('admins');

        $assessments = Assessment::where('admin_id', $admin->id)
            ->latest()
            ->get();

        $batchesByAssessment = Batch::whereIn('assessment_id', $assessments->pluck('id'))
            ->get()
            ->groupBy('assessment_id');

        $result = [];

        foreach ($assessments as $assessment) {

            $batches = $batchesByAssessment->get($assessment->id, collect());

            foreach ($batches as $batch) {
                $result[] = [
                    ...$assessment->toArray(),
                    ...batch_schedule_fields($assessment, $batch),
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

            'scheduling_type' => 'required|in:' . implode(',', Assessment::SCHEDULING_TYPES),

            'difficulty_level' => 'required|string',
            'shuffle' => 'boolean',
            'is_library' => 'boolean',
            'is_active' => 'boolean',
            'has_negative' => 'boolean',
            'negative_marks' => 'nullable|numeric|min:0',

            ...$this->scheduleRules(required: true),
        ]);

        if (
            $validated['scheduling_type'] === Assessment::SCHEDULING_FIXED &&
            $validated['end_time'] <= $validated['start_time']
        ) {
            return response()->json([
                'message' => 'end_time must be after start_time'
            ], 422);
        }

        if (
            $validated['scheduling_type'] === Assessment::SCHEDULING_FLEXIBLE &&
            $validated['end_date'] < $validated['start_date']
        ) {
            return response()->json([
                'message' => 'end_date must be on or after start_date'
            ], 422);
        }

        $assessment = Assessment::create([
            'admin_id' => $request->user('admins')->id,

            ...collect($validated)
                ->except([
                    'publish_date',
                    'start_time',
                    'end_time',
                    'start_date',
                    'end_date',
                    'duration_minutes',
                ])
                ->toArray(),
        ]);

        if ($assessment->scheduling_type === Assessment::SCHEDULING_FIXED) {

            Batch::create([
                'name' => 'individual_batch_' . $assessment->id,
                'publish_date' => $validated['publish_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'assessment_id' => $assessment->id,
                'capacity' => null,
            ]);

        } elseif ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {

            Batch::create([
                'name' => 'individual_batch_' . $assessment->id,
                'publish_date' => $validated['start_date'],
                'expiry_date' => $validated['end_date'],
                'duration_minutes' => $validated['duration_minutes'],
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

        if ($assessment->scheduling_type === Assessment::SCHEDULING_BATCH_WISE) {

            $batches = Batch::where('assessment_id', $assessment->id)->get();

            return response()->json([
                ...$assessment->toArray(),
                'batches' => $batches,
            ]);
        }

        $batch = Batch::where('assessment_id', $assessment->id)->first();

        return response()->json([
            ...$assessment->toArray(),
            ...batch_schedule_fields($assessment, $batch),
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

        // scheduling_type is immutable, so the prohibited_unless rules below
        // must key off the assessment's persisted type even when the request
        // doesn't resupply it.
        $request->mergeIfMissing(['scheduling_type' => $assessment->scheduling_type]);

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

            'scheduling_type' => 'sometimes|in:' . implode(',', Assessment::SCHEDULING_TYPES),

            ...$this->scheduleRules(required: false),
        ]);

        if ($validated['scheduling_type'] !== $assessment->scheduling_type) {
            return response()->json([
                'message' => 'Changing scheduling type is not allowed'
            ], 422);
        }

        if ($assessment->scheduling_type === Assessment::SCHEDULING_FIXED) {

            foreach (['publish_date', 'start_time', 'end_time'] as $field) {
                if (array_key_exists($field, $validated) && !$validated[$field]) {
                    return response()->json([
                        'message' => 'Publish date, start time and end time are required'
                    ], 422);
                }
            }

        } elseif ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {

            foreach (['start_date', 'end_date', 'duration_minutes'] as $field) {
                if (array_key_exists($field, $validated) && !$validated[$field]) {
                    return response()->json([
                        'message' => 'start_date, end_date and duration_minutes are required for flexible assessments'
                    ], 422);
                }
            }
        }

        $batch = $assessment->scheduling_type === Assessment::SCHEDULING_BATCH_WISE
            ? null
            : Batch::where('assessment_id', $assessment->id)->first();

        if ($batch && $assessment->scheduling_type === Assessment::SCHEDULING_FIXED) {

            $effectiveStart = $validated['start_time'] ?? $batch->start_time;
            $effectiveEnd = $validated['end_time'] ?? $batch->end_time;

            if ($effectiveStart && $effectiveEnd && $effectiveEnd <= $effectiveStart) {
                return response()->json([
                    'message' => 'end_time must be after start_time'
                ], 422);
            }

        } elseif ($batch && $assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {

            $effectiveStart = $validated['start_date'] ?? $batch->publish_date;
            $effectiveEnd = $validated['end_date'] ?? $batch->expiry_date;

            if ($effectiveStart && $effectiveEnd && $effectiveEnd < $effectiveStart) {
                return response()->json([
                    'message' => 'end_date must be on or after start_date'
                ], 422);
            }
        }

        $assessment->update(
            collect($validated)->except([
                'publish_date',
                'start_time',
                'end_time',
                'start_date',
                'end_date',
                'duration_minutes',
            ])->toArray()
        );

        if ($batch && $assessment->scheduling_type === Assessment::SCHEDULING_FIXED) {

            $batch->update([
                'publish_date' => $validated['publish_date'] ?? $batch->publish_date,
                'start_time' => $validated['start_time'] ?? $batch->start_time,
                'end_time' => $validated['end_time'] ?? $batch->end_time,
            ]);

        } elseif ($batch && $assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {

            $batch->update([
                'publish_date' => $validated['start_date'] ?? $batch->publish_date,
                'expiry_date' => $validated['end_date'] ?? $batch->expiry_date,
                'duration_minutes' => $validated['duration_minutes'] ?? $batch->duration_minutes,
            ]);
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

        $batchesByAssessment = Batch::whereIn('assessment_id', $assessments->pluck('id'))
            ->get()
            ->groupBy('assessment_id');

        $result = [];

        foreach ($assessments as $assessment) {

            $batches = $batchesByAssessment->get($assessment->id, collect());

            foreach ($batches as $batch) {
                $result[] = [
                    ...$assessment->toArray(),
                    ...batch_schedule_fields($assessment, $batch),
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

        $batchesByAssessment = Batch::whereIn('assessment_id', $assessments->pluck('id'))
            ->get()
            ->groupBy('assessment_id');

        $result = [];

        foreach ($assessments as $assessment) {

            if ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {
                continue;
            }

            $batches = $batchesByAssessment->get($assessment->id, collect());

            foreach ($batches as $batch) {

                if (
                    $batch->publish_date == $today &&
                    $batch->start_time > $nowTime
                ) {
                    $result[] = [
                        ...$assessment->toArray(),
                        ...batch_schedule_fields($assessment, $batch),
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

        $batchesByAssessment = Batch::whereIn('assessment_id', $assessments->pluck('id'))
            ->get()
            ->groupBy('assessment_id');

        $result = [];

        foreach ($assessments as $assessment) {

            $batches = $batchesByAssessment->get($assessment->id, collect());

            foreach ($batches as $batch) {

                $isRunning = false;

                if ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {
                    $isRunning =
                        (!$batch->publish_date || $batch->publish_date <= $today) &&
                        (!$batch->expiry_date || $batch->expiry_date >= $today);
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
                    ...batch_schedule_fields($assessment, $batch),
                ];
            }
        }

        return response()->json($result);
    }
}
