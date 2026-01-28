<?php

namespace Database\Seeders;

use App\Models\AssessmentAnswer;
use Illuminate\Database\Seeder;

class AssessmentAnswerSeeder extends Seeder
{
    public function run(): void
    {
        AssessmentAnswer::create([
            'attempt_id' => 1,
            'question_id' => 1,
            'answer' => ['choice_id' => 2],
            'is_correct' => true,
        ]);
    }
}