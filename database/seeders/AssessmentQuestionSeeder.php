<?php

namespace Database\Seeders;

use App\Models\AssessmentQuestion;
use Illuminate\Database\Seeder;

class AssessmentQuestionSeeder extends Seeder
{
    public function run(): void
    {
        AssessmentQuestion::create([
            'assessment_id' => 1,
            'type' => 'mcq',
            'question_text' => 'What is 2 + 2?',
            'order' => 1,
        ]);
    }
}