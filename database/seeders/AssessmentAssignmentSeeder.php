<?php

namespace Database\Seeders;

use App\Models\AssessmentAssignment;
use Illuminate\Database\Seeder;

class AssessmentAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        AssessmentAssignment::create([
            'assessment_id' => 1,
            'user_id' => 1,
        ]);

        AssessmentAssignment::create([
            'assessment_id' => 1,
            'user_id' => 2,
        ]);
    }
}