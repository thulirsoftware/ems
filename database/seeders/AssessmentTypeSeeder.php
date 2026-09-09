<?php

namespace Database\Seeders;

use App\Models\AssessmentType;
use Illuminate\Database\Seeder;

class AssessmentTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Slugs matter: AssessmentAttemptController::submit() switches on
        // assessment.type.slug to decide auto-grading (mcq) vs manual
        // evaluation (descriptive).
        AssessmentType::create([
            'name' => 'Objective',
            'slug' => 'mcq',
            'description' => 'Multiple-choice questions, auto-graded on submission.',
            'is_active' => true,
        ]);

        AssessmentType::create([
            'name' => 'Descriptive',
            'slug' => 'descriptive',
            'description' => 'Free-text answers that require manual evaluation.',
            'is_active' => true,
        ]);
    }
}
