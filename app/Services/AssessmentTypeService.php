<?php

namespace App\Services;

use App\Models\AssessmentType;

class AssessmentTypeService
{
    public function list()
    {
        return AssessmentType::latest()->get();
    }
}
