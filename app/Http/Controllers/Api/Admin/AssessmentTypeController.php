<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentType;
use Illuminate\Http\Request;

class AssessmentTypeController extends Controller
{
    public function index()
    {
        return AssessmentType::latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:assessment_types,slug',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $type = AssessmentType::create($validated);

        return response()->json($type, 201);
    }

    public function show($id)
    {
        return AssessmentType::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $type = AssessmentType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'slug' => 'sometimes|string|max:100|unique:assessment_types,slug,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $type->update($validated);

        return response()->json($type);
    }

    public function destroy($id)
    {
        $type = AssessmentType::findOrFail($id);
        $type->delete();

        return response()->json([
            'message' => 'Assessment type deleted'
        ]);
    }
}
