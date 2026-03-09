<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAssignment;
use Illuminate\Http\Request;

class UserAssessmentController extends Controller
{
    // Upcoming assessments (not started and not ended yet)
    public function upcoming(Request $request)
    {
        $user = $request->user('users');

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $assessments = AssessmentAssignment::where('user_id', $user->id)
            ->whereHas('assessment', function ($q) use ($today, $nowTime) {
                $q->where('is_active', true)
                    ->where(function ($q2) use ($today, $nowTime) {
                        $q2->where('publish_date', '>', $today)
                            ->orWhere(function ($q3) use ($today, $nowTime) {
                                $q3->where('publish_date', $today)
                                    ->where('start_time', '>', $nowTime);
                            });
                    });
            })
            ->with('assessment')
            ->get()
            ->pluck('assessment');

        return response()->json($assessments);
    }

    // Today's assessments (previously upcoming)
    public function today(Request $request)
    {
        $user = $request->user('users');

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $assessments = AssessmentAssignment::where('user_id', $user->id)
            ->whereHas('assessment', function ($q) use ($today, $nowTime) {
                $q->where('is_active', true)
                    ->where('publish_date', $today)
                    ->where('start_time', '>', $nowTime);
            })
            ->with('assessment')
            ->get()
            ->pluck('assessment');

        return response()->json($assessments);
    }

    // Assessments user can take right now
    public function running(Request $request)
    {
        $user = $request->user('users');

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $assessments = AssessmentAssignment::where('user_id', $user->id)
            ->whereHas('assessment', function ($q) use ($today, $nowTime) {
                $q->where('is_active', true)
                    ->where('publish_date', $today)
                    ->where('start_time', '<=', $nowTime)
                    ->where('end_time', '>=', $nowTime);
            })
            // 🚫 exclude completed attempts
            ->whereNotExists(function ($q) use ($user) {
                $q->selectRaw(1)
                ->from('assessment_attempts')
                ->whereColumn('assessment_attempts.assessment_id', 'assessment_assignments.assessment_id')
                ->where('assessment_attempts.user_id', $user->id)
                ->whereNotNull('assessment_attempts.submitted_at');
            })
            ->with('assessment')
            ->get()
            ->pluck('assessment');

        return response()->json($assessments);
    }

    // Completed assessments
    public function completed(Request $request)
    {
        $user = $request->user('users');

        $assessmentIds = \DB::table('assessment_attempts')
            ->where('user_id', $user->id)
            ->pluck('assessment_id');

        $assessments = AssessmentAssignment::where('user_id', $user->id)
            ->whereIn('assessment_id', $assessmentIds)
            ->with('assessment')
            ->get()
            ->pluck('assessment');

        return response()->json($assessments);
    }

    // Missed assessments
    public function missed(Request $request)
    {
        $user = $request->user('users');

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $attemptedIds = \DB::table('assessment_attempts')
            ->where('user_id', $user->id)
            ->pluck('assessment_id');

        $assessments = AssessmentAssignment::where('user_id', $user->id)
            ->whereNotIn('assessment_id', $attemptedIds)
            ->whereHas('assessment', function ($q) use ($today, $nowTime) {
                $q->where(function ($q2) use ($today, $nowTime) {
                    $q2->where('publish_date', '<', $today)
                        ->orWhere(function ($q3) use ($today, $nowTime) {
                            $q3->where('publish_date', $today)
                                ->where('end_time', '<', $nowTime);
                        });
                });
            })
            ->with('assessment')
            ->get()
            ->pluck('assessment');

        return response()->json($assessments);
    }

    // Show one assessment only if assigned to user
    public function show(Request $request, $id)
    {
        $user = $request->user('users');

        $assessment = AssessmentAssignment::where('user_id', $user->id)
            ->where('assessment_id', $id)
            ->with('assessment')
            ->firstOrFail()
            ->assessment;

        return response()->json($assessment);
    }
}
