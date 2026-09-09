<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PassportClientSeeder::class,
            AdminSeeder::class,
            UserSeeder::class,
            AssessmentTypeSeeder::class,
            AssessmentSeeder::class,
            BatchSeeder::class,
            AssessmentQuestionSeeder::class,
            AssessmentAssignmentSeeder::class,
            AssessmentAttemptSeeder::class,
            AssessmentAnswerSeeder::class,
            NotificationSeeder::class,
            OtpSeeder::class,
        ]);
    }
}
