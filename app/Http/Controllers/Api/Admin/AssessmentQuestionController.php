<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\QuestionService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AssessmentQuestionController extends Controller
{
    public function __construct(
        private QuestionService $questionService
    ) {}

    public function index(Request $request, $assessment_id)
    {
        return response()->json($this->questionService->list($request->user('admins'), $assessment_id));
    }

    public function store(Request $request, $assessment_id)
    {
        return response()->json($this->questionService->create($request->user('admins'), $assessment_id, $request->all()), 201);
    }

    public function update(Request $request, $id)
    {
        return response()->json($this->questionService->update($request->user('admins'), $id, $request->all()));
    }

    public function destroy(Request $request, $id)
    {
        $this->questionService->delete($request->user('admins'), $id);

        return response()->json(['message' => 'Question deleted']);
    }

    public function indexWithChoices(Request $request, $assessment_id)
    {
        return response()->json($this->questionService->listWithChoices($request->user('admins'), $assessment_id));
    }

    public function showWithChoices(Request $request, $question_id)
    {
        return response()->json($this->questionService->getWithChoices($request->user('admins'), $question_id));
    }

    public function storeWithChoices(Request $request, $assessment_id)
    {
        return response()->json($this->questionService->createWithChoices($request->user('admins'), $assessment_id, $request->all()), 201);
    }

    public function updateWithChoices(Request $request, $question_id)
    {
        return response()->json($this->questionService->updateWithChoices($request->user('admins'), $question_id, $request->all()));
    }

    public function bulkStoreQuestions(Request $request, $assessment_id)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx',
        ]);

        $sheet = Excel::toArray([], $request->file('file'))[0];

        if (count($sheet) < 2) {
            return response()->json(['message' => 'File is empty'], 400);
        }

        $header = array_map('strtolower', $sheet[0]);

        $rows = array_map(
            fn ($row) => array_combine($header, $row),
            array_slice($sheet, 1)
        );

        $result = $this->questionService->bulkCreate($request->user('admins'), $assessment_id, $rows);

        return response()->json([
            'message' => 'Bulk questions upload completed',
            ...$result,
        ]);
    }
}
