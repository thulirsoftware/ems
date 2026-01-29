<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function index(Request $request)
    {
        $admin = $request->user('admins');

        $assessments = Assessment::where('admin_id', $admin->id)
            ->latest()
            ->get();

        return response()->json($assessments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'assessment_type_id' => 'required|exists:assessment_types,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'publish_date' => 'required|date',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s',
            'shuffle' => 'boolean',
            'is_library' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $assessment = Assessment::create([
            'admin_id' => $request->user('admins')->id,
            ...$validated,
        ]);

        return response()->json($assessment, 201);
    }

    public function show(Request $request, $id)
    {
        $admin = $request->user('admins');

        $assessment = Assessment::where('admin_id', $admin->id)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json($assessment);
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
            'shuffle' => 'boolean',
            'is_library' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $assessment->update($validated);

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

        return Assessment::where('admin_id', $admin->id)
            ->where('is_library', true)
            ->latest()
            ->get();
    }

    public function upcoming(Request $request)
    {
        $admin = $request->user('admins');

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $assessments = Assessment::where('admin_id', $admin->id)
            ->where('is_active', true)
            ->whereDate('publish_date', $today)
            ->whereTime('start_time', '>', $nowTime)
            ->orderBy('start_time')
            ->get();

        return response()->json($assessments);
    }

    public function running(Request $request)
    {
        $admin = $request->user('admins');

        $today = app_now()->toDateString();
        $nowTime = app_now()->toTimeString();

        $assessments = Assessment::where('admin_id', $admin->id)
            ->where('is_active', true)
            ->whereDate('publish_date', $today)
            ->whereTime('start_time', '<=', $nowTime)
            ->whereTime('end_time', '>=', $nowTime)
            ->orderBy('start_time')
            ->get();

        return response()->json($assessments);
    }
}
