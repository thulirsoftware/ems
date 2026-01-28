<?php

namespace Database\Seeders;

use App\Models\AssessmentChoice;
use Illuminate\Database\Seeder;

class AssessmentChoiceSeeder extends Seeder
{
    public function run(): void
    {
        AssessmentChoice::create([
            'question_id' => 1,
            'option' => '3',
            'is_correct' => false,
            'order' => 1,
        ]);

        AssessmentChoice::create([
            'question_id' => 1,
            'option' => '4',
            'is_correct' => true,
            'order' => 2,
        ]);
    }
}