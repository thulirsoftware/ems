<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Batch;
use Illuminate\Support\Facades\Validator;

class AssessmentService
{
    private function isLocked(int $assessmentId): bool
    {
        return AssessmentAttempt::where('assessment_id', $assessmentId)->exists();
    }

    // $id is untyped: it originates from a raw route segment, and Eloquent's
    // where() compares it at the DB level exactly like the original
    // controllers did — a non-numeric value simply matches no row (404 via
    // firstOrFail), instead of a native `int` param throwing a TypeError
    // (500) for the same input.
    private function findOwned(Admin $admin, $id): Assessment
    {
        return Assessment::where('admin_id', $admin->id)->where('id', $id)->firstOrFail();
    }

    private function withSchedule($assessments): array
    {
        $batchesByAssessment = Batch::whereIn('assessment_id', $assessments->pluck('id'))->get()->groupBy('assessment_id');

        $result = [];

        foreach ($assessments as $assessment) {
            foreach ($batchesByAssessment->get($assessment->id, collect()) as $batch) {
                $result[] = [...$assessment->toArray(), ...batch_schedule_fields($assessment, $batch)];
            }
        }

        return $result;
    }

    public function list(Admin $admin): array
    {
        return $this->withSchedule(Assessment::where('admin_id', $admin->id)->latest()->get());
    }

    public function library(Admin $admin): array
    {
        return $this->withSchedule(Assessment::where('admin_id', $admin->id)->where('is_library', true)->latest()->get());
    }

    public function upcoming(Admin $admin): array
    {
        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $assessments = Assessment::where('admin_id', $admin->id)->where('is_active', true)->get();
        $batchesByAssessment = Batch::whereIn('assessment_id', $assessments->pluck('id'))->get()->groupBy('assessment_id');

        $result = [];

        foreach ($assessments as $assessment) {
            if ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {
                continue;
            }

            foreach ($batchesByAssessment->get($assessment->id, collect()) as $batch) {
                if ($batch->publish_date == $today && $batch->start_time > $nowTime) {
                    $result[] = [...$assessment->toArray(), ...batch_schedule_fields($assessment, $batch)];
                }
            }
        }

        return $result;
    }

    public function running(Admin $admin): array
    {
        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $assessments = Assessment::where('admin_id', $admin->id)->where('is_active', true)->get();
        $batchesByAssessment = Batch::whereIn('assessment_id', $assessments->pluck('id'))->get()->groupBy('assessment_id');

        $result = [];

        foreach ($assessments as $assessment) {
            foreach ($batchesByAssessment->get($assessment->id, collect()) as $batch) {
                $isRunning = $assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE
                    ? (!$batch->publish_date || $batch->publish_date <= $today) && (!$batch->expiry_date || $batch->expiry_date >= $today)
                    : $batch->publish_date == $today && $batch->start_time <= $nowTime && $batch->end_time >= $nowTime;

                if ($isRunning) {
                    $result[] = [...$assessment->toArray(), ...batch_schedule_fields($assessment, $batch)];
                }
            }
        }

        return $result;
    }

    public function get(Admin $admin, $id): array
    {
        $assessment = $this->findOwned($admin, $id);

        if ($assessment->scheduling_type === Assessment::SCHEDULING_BATCH_WISE) {
            return [...$assessment->toArray(), 'batches' => Batch::where('assessment_id', $assessment->id)->get()];
        }

        $batch = Batch::where('assessment_id', $assessment->id)->first();

        return [...$assessment->toArray(), ...batch_schedule_fields($assessment, $batch)];
    }

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

    public function create(Admin $admin, array $data): Assessment
    {
        $validated = Validator::make($data, [
            'assessment_type_id' => 'required|exists:assessment_types,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduling_type' => 'required|in:'.implode(',', Assessment::SCHEDULING_TYPES),
            'difficulty_level' => 'required|string',
            'shuffle' => 'boolean',
            'is_library' => 'boolean',
            'is_active' => 'boolean',
            'has_negative' => 'boolean',
            'negative_marks' => 'nullable|numeric|min:0',
            ...$this->scheduleRules(required: true),
        ])->validate();

        if ($validated['scheduling_type'] === Assessment::SCHEDULING_FIXED && $validated['end_time'] <= $validated['start_time']) {
            abort(422, 'end_time must be after start_time');
        }

        if ($validated['scheduling_type'] === Assessment::SCHEDULING_FLEXIBLE && $validated['end_date'] < $validated['start_date']) {
            abort(422, 'end_date must be on or after start_date');
        }

        $assessment = Assessment::create([
            'admin_id' => $admin->id,
            ...collect($validated)->except(['publish_date', 'start_time', 'end_time', 'start_date', 'end_date', 'duration_minutes'])->toArray(),
        ]);

        if ($assessment->scheduling_type === Assessment::SCHEDULING_FIXED) {
            Batch::create([
                'name' => 'individual_batch_'.$assessment->id,
                'publish_date' => $validated['publish_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'assessment_id' => $assessment->id,
                'capacity' => null,
            ]);
        } elseif ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {
            Batch::create([
                'name' => 'individual_batch_'.$assessment->id,
                'publish_date' => $validated['start_date'],
                'expiry_date' => $validated['end_date'],
                'duration_minutes' => $validated['duration_minutes'],
                'assessment_id' => $assessment->id,
                'capacity' => null,
            ]);
        }

        return $assessment;
    }

    public function update(Admin $admin, $id, array $data): Assessment
    {
        $assessment = $this->findOwned($admin, $id);

        if ($this->isLocked($assessment->id)) {
            abort(403, 'Cannot modify assessment after it has been attempted');
        }

        $data = [...$data, 'scheduling_type' => $data['scheduling_type'] ?? $assessment->scheduling_type];

        $validated = Validator::make($data, [
            'assessment_type_id' => 'sometimes|exists:assessment_types,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'difficulty_level' => 'nullable|string',
            'shuffle' => 'boolean',
            'is_library' => 'boolean',
            'is_active' => 'boolean',
            'has_negative' => 'boolean',
            'negative_marks' => 'nullable|numeric|min:0',
            'scheduling_type' => 'sometimes|in:'.implode(',', Assessment::SCHEDULING_TYPES),
            ...$this->scheduleRules(required: false),
        ])->validate();

        if ($validated['scheduling_type'] !== $assessment->scheduling_type) {
            abort(422, 'Changing scheduling type is not allowed');
        }

        if ($assessment->scheduling_type === Assessment::SCHEDULING_FIXED) {
            foreach (['publish_date', 'start_time', 'end_time'] as $field) {
                if (array_key_exists($field, $validated) && !$validated[$field]) {
                    abort(422, 'Publish date, start time and end time are required');
                }
            }
        } elseif ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {
            foreach (['start_date', 'end_date', 'duration_minutes'] as $field) {
                if (array_key_exists($field, $validated) && !$validated[$field]) {
                    abort(422, 'start_date, end_date and duration_minutes are required for flexible assessments');
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
                abort(422, 'end_time must be after start_time');
            }
        } elseif ($batch && $assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {
            $effectiveStart = $validated['start_date'] ?? $batch->publish_date;
            $effectiveEnd = $validated['end_date'] ?? $batch->expiry_date;

            if ($effectiveStart && $effectiveEnd && $effectiveEnd < $effectiveStart) {
                abort(422, 'end_date must be on or after start_date');
            }
        }

        $assessment->update(collect($validated)->except(['publish_date', 'start_time', 'end_time', 'start_date', 'end_date', 'duration_minutes'])->toArray());

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

        return $assessment;
    }

    public function delete(Admin $admin, $id): void
    {
        $assessment = $this->findOwned($admin, $id);

        if ($this->isLocked($assessment->id)) {
            abort(403, 'Cannot delete assessment after it has been attempted');
        }

        $assessment->delete();
    }
}
