<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            UserSeeder::class,
            AssessmentTypeSeeder::class,
            AssessmentSeeder::class,
            AssessmentQuestionSeeder::class,
            AssessmentChoiceSeeder::class,
            AssessmentAssignmentSeeder::class,
            AssessmentAttemptSeeder::class,
            AssessmentAnswerSeeder::class,
        ]);
    }
}
