<?php

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
        if ($assessment->is_flexible) {
            return !$batch->expiry_date || $batch->expiry_date >= $today
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