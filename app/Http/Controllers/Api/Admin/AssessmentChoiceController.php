<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ChoiceService;
use Illuminate\Http\Request;

class AssessmentChoiceController extends Controller
{
    public function __construct(
        private ChoiceService $choiceService
    ) {}

    public function index(Request $request, $question_id)
    {
        return response()->json($this->choiceService->list($request->user('admins'), $question_id));
    }

    public function store(Request $request, $question_id)
    {
        return response()->json($this->choiceService->create($request->user('admins'), $question_id, $request->all()), 201);
    }

    public function update(Request $request, $id)
    {
        return response()->json($this->choiceService->update($request->user('admins'), $id, $request->all()));
    }

    public function destroy(Request $request, $id)
    {
        $this->choiceService->delete($request->user('admins'), $id);

        return response()->json(['message' => 'Choice deleted']);
    }
}
