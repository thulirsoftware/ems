<?php

namespace Database\Seeders;

use App\Models\AssessmentAttempt;
use Illuminate\Database\Seeder;

class AssessmentAttemptSeeder extends Seeder
{
    public function run(): void
    {
        AssessmentAttempt::create([
            'assessment_id' => 1,
            'user_id' => 1,
            'score' => 100,
        ]);
    }
}