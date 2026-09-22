<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AssessmentTypeService;
use Illuminate\Http\Request;

class AssessmentTypeController extends Controller
{
    public function __construct(
        private AssessmentTypeService $assessmentTypeService
    ) {}

    public function index(Request $request)
    {
        return response()->json($this->assessmentTypeService->list());
    }
}
