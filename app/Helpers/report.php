<?php

use App\Models\Assessment;

// Shared helpers for the dashboard and report endpoints.
// Extracted verbatim from the private copies that previously lived in
// AdminDashboardController, AdminReportController, UserDashboardController
// and UserReportController.

if (!function_exists('parse_score')) {
    // turn a "7/10" score string into numbers, null when not graded
    function parse_score($score)
    {
        if (!$score || !str_contains($score, '/')) {
            return null;
        }

        $parts = explode('/', $score);

        $value = (float) ($parts[0] ?? 0);
        $total = (float) ($parts[1] ?? 0);

        if ($total <= 0) {
            return null;
        }

        return [
            'score' => round($value, 2),
            'total' => (int) $total,
            'percentage' => round(($value / $total) * 100),
        ];
    }
}

if (!function_exists('attempt_result')) {
    // read an attempt's result, null while unsubmitted or ungraded
    function attempt_result($attempt)
    {
        if (!$attempt->submitted_at) {
            return null;
        }

        return parse_score($attempt->score);
    }
}

if (!function_exists('attempt_status')) {
    function attempt_status($attempt, $result)
    {
        if (!$attempt->submitted_at) {
            return 'in_progress';
        }

        return $result ? 'evaluated' : 'pending_evaluation';
    }
}

if (!function_exists('batch_status')) {
    // A batch's own scheduling status, independent of any one user's attempt.
    // Single source of truth shared by the admin dashboard and report
    // controllers, which previously kept two private copies that quietly
    // disagreed about what an unscheduled (no publish_date) batch should be
    // called.
    function batch_status($assessment, $batch, $today, $nowTime)
    {
        if ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {

            if ($batch->publish_date && $batch->publish_date > $today) {
                return 'upcoming';
            }

            return (!$batch->expiry_date || $batch->expiry_date >= $today)
                ? 'running'
                : 'finished';
        }

        if (!$batch->publish_date || $batch->publish_date > $today) {
            return $batch->publish_date ? 'upcoming' : 'unscheduled';
        }

        if ($batch->publish_date < $today) {
            return 'finished';
        }

        if ($batch->start_time > $nowTime) {
            return 'upcoming';
        }

        if ($batch->end_time < $nowTime) {
            return 'finished';
        }

        return 'running';
    }
}

if (!function_exists('is_implicit_batch')) {
    // The single default batch every fixed/flexible assessment gets at
    // creation (AssessmentController::store()), named individual_batch_{id}.
    // It is never a manageable/addressable batch entity — unlike a batch_wise
    // batch, or a later re-exam batch (a real, distinctly-named, deliberately
    // created batch that admins do need to reference), it must never surface
    // its id/name anywhere. Detected by the same name convention the rest of
    // the app already uses to find it (e.g. ReExamController's source-batch
    // lookup for non-batch-wise assessments).
    function is_implicit_batch($assessment, $batch)
    {
        return $assessment
            && $batch
            && $assessment->scheduling_type !== Assessment::SCHEDULING_BATCH_WISE
            && $batch->name === 'individual_batch_' . $assessment->id;
    }
}

if (!function_exists('batch_schedule_fields')) {
    // Flatten a batch's schedule onto the shape the Assessment APIs expose,
    // per scheduling_type: fixed/batch_wise batches carry real
    // publish_date/start_time/end_time; flexible batches carry a date range
    // (start_date/end_date, backed by the same publish_date/expiry_date
    // columns) plus duration_minutes instead. Every key is always present so
    // list rows have a uniform shape regardless of scheduling_type. batch_id
    // is omitted for the implicit batch — see is_implicit_batch().
    function batch_schedule_fields($assessment, $batch)
    {
        $fields = [
            'batch_id' => null,
            'publish_date' => null,
            'start_time' => null,
            'end_time' => null,
            'start_date' => null,
            'end_date' => null,
            'duration_minutes' => null,
        ];

        if (!$batch || !$assessment) {
            return $fields;
        }

        if (!is_implicit_batch($assessment, $batch)) {
            $fields['batch_id'] = $batch->id;
        }

        if ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {
            $fields['start_date'] = $batch->publish_date;
            $fields['end_date'] = $batch->expiry_date;
            $fields['duration_minutes'] = $batch->duration_minutes;

            return $fields;
        }

        $fields['publish_date'] = $batch->publish_date;
        $fields['start_time'] = $batch->start_time;
        $fields['end_time'] = $batch->end_time;

        return $fields;
    }
}

if (!function_exists('bucket_percentages')) {
    // percentage buckets for a results chart
    function bucket_percentages($percentages)
    {
        $buckets = [
            '0-39' => 0,
            '40-59' => 0,
            '60-79' => 0,
            '80-100' => 0,
        ];

        foreach ($percentages as $percentage) {

            if ($percentage < 40) {
                $buckets['0-39']++;
            } elseif ($percentage < 60) {
                $buckets['40-59']++;
            } elseif ($percentage < 80) {
                $buckets['60-79']++;
            } else {
                $buckets['80-100']++;
            }
        }

        return $buckets;
    }
}

if (!function_exists('attempt_percentages')) {
    // the graded percentages inside a set of attempts
    function attempt_percentages($attempts)
    {
        return $attempts
            ->map(fn($attempt) => attempt_result($attempt))
            ->filter()
            ->pluck('percentage');
    }
}

if (!function_exists('score_distribution')) {
    function score_distribution($attempts)
    {
        return bucket_percentages(attempt_percentages($attempts));
    }
}

if (!function_exists('score_performance')) {
    // score aggregates over a set of attempts
    function score_performance($attempts, $passing)
    {
        $percentages = attempt_percentages($attempts);

        $passed = $percentages->filter(fn($p) => $p >= $passing)->count();

        return [
            'evaluated' => $percentages->count(),
            'average_percentage' => $percentages->count() > 0 ? round($percentages->avg()) : 0,
            'highest_percentage' => $percentages->count() > 0 ? $percentages->max() : 0,
            'lowest_percentage' => $percentages->count() > 0 ? $percentages->min() : 0,
            'passed' => $passed,
            'failed' => $percentages->count() - $passed,
            'pass_rate' => $percentages->count() > 0
                ? round(($passed / $percentages->count()) * 100)
                : 0,
        ];
    }
}

if (!function_exists('applied_filters')) {
    // echo back only the filters that were actually supplied.
    // cast to object so an empty set still serialises as {} and not []
    function applied_filters($validated)
    {
        return (object) collect($validated)
            ->except(['page', 'page_size'])
            ->filter(fn($value) => $value !== null)
            ->toArray();
    }
}

if (!function_exists('passing_percentage')) {
    function passing_percentage($validated)
    {
        return (float) ($validated['passing_percentage'] ?? 40);
    }
}

if (!function_exists('paginate_rows')) {
    // manual pagination, matching the style used in AssessmentAttemptController::result
    function paginate_rows($rows, $validated)
    {
        $page = (int) ($validated['page'] ?? 1);
        $pageSize = (int) ($validated['page_size'] ?? 25);

        $total = $rows->count();

        return [
            'data' => $rows->slice(($page - 1) * $pageSize, $pageSize)->values(),
            'current_page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'total_pages' => (int) ceil($total / $pageSize),
        ];
    }
}

if (!function_exists('resolve_limit')) {
    // clamp the size of the embedded dashboard lists
    function resolve_limit($request, $default = 5, $max = 50)
    {
        $limit = (int) $request->query('limit', $default);

        if ($limit < 1) {
            return $default;
        }

        return $limit > $max ? $max : $limit;
    }
}