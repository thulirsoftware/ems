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
}
