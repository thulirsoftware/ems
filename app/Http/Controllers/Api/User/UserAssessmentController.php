<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAssignment;
use Illuminate\Http\Request;

class UserAssessmentController extends Controller
{
    // List all assigned assessments
    public function index(Request $request)
    {
        $user = $request->user('users');

        $assessments = AssessmentAssignment::where('user_id', $user->id)
            ->with('assessment')
            ->get()
            ->pluck('assessment');

        return response()->json($assessments);
    }

    // Assessments assigned to user but not started yet (today)
    public function upcoming(Request $request)
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
