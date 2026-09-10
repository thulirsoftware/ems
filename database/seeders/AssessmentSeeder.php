<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Assessment;
use App\Models\AssessmentType;
use Illuminate\Database\Seeder;

class AssessmentSeeder extends Seeder
{
    public function run(): void
    {
        $admin1 = Admin::where('email', 'admin1@gmail.com')->firstOrFail();
        $admin2 = Admin::where('email', 'admin2@gmail.com')->firstOrFail();
        $admin3 = Admin::where('email', 'admin3@gmail.com')->firstOrFail();

        $mcq = AssessmentType::where('slug', 'mcq')->firstOrFail();
        $descriptive = AssessmentType::where('slug', 'descriptive')->firstOrFail();

        // Fixed-schedule, single-batch mcq assessment (also library material).
        Assessment::create([
            'admin_id' => $admin1->id,
            'assessment_type_id' => $mcq->id,
            'title' => 'Basic Math Test',
            'description' => 'Simple arithmetic assessment for onboarding students.',
            'is_active' => true,
            'difficulty_level' => 'easy',
            'shuffle' => true,
            'is_library' => true,
            'has_negative' => false,
            'negative_marks' => 0,
            'scheduling_type' => Assessment::SCHEDULING_FIXED,
        ]);

        // Batch-wise mcq assessment with negative marking (multiple batches).
        Assessment::create([
            'admin_id' => $admin1->id,
            'assessment_type_id' => $mcq->id,
            'title' => 'Advanced Algebra Quiz',
            'description' => 'Timed algebra quiz run across multiple batches.',
            'is_active' => true,
            'difficulty_level' => 'hard',
            'shuffle' => true,
            'is_library' => false,
            'has_negative' => true,
            'negative_marks' => 0.25,
            'scheduling_type' => Assessment::SCHEDULING_BATCH_WISE,
        ]);

        // Fixed-schedule descriptive assessment (manual grading).
        Assessment::create([
            'admin_id' => $admin1->id,
            'assessment_type_id' => $descriptive->id,
            'title' => 'General Knowledge Descriptive',
            'description' => 'Short-answer questions covering general knowledge.',
            'is_active' => true,
            'difficulty_level' => 'medium',
            'shuffle' => false,
            'is_library' => false,
            'has_negative' => false,
            'negative_marks' => 0,
            'scheduling_type' => Assessment::SCHEDULING_FIXED,
        ]);

        // Flexible mcq assessment: available anytime before expiry, timed per attempt.
        Assessment::create([
            'admin_id' => $admin2->id,
            'assessment_type_id' => $mcq->id,
            'title' => 'Flexible Practice Test',
            'description' => 'Self-paced practice test students can take anytime before it expires.',
            'is_active' => true,
            'difficulty_level' => 'easy',
            'shuffle' => true,
            'is_library' => true,
            'has_negative' => false,
            'negative_marks' => 0,
            'scheduling_type' => Assessment::SCHEDULING_FLEXIBLE,
        ]);

        // Draft descriptive assessment, not yet published to students.
        Assessment::create([
            'admin_id' => $admin2->id,
            'assessment_type_id' => $descriptive->id,
            'title' => 'Coding Challenge - Arrays',
            'description' => 'Array manipulation problems, manually graded.',
            'is_active' => false,
            'difficulty_level' => 'hard',
            'shuffle' => false,
            'is_library' => false,
            'has_negative' => false,
            'negative_marks' => 0,
            'scheduling_type' => Assessment::SCHEDULING_FIXED,
        ]);

        // Batch-wise case study assessment.
        Assessment::create([
            'admin_id' => $admin3->id,
            'assessment_type_id' => $descriptive->id,
            'title' => 'Startup Case Study',
            'description' => 'Analyze a startup scenario and answer scenario-based questions.',
            'is_active' => true,
            'difficulty_level' => 'medium',
            'shuffle' => false,
            'is_library' => false,
            'has_negative' => false,
            'negative_marks' => 0,
            'scheduling_type' => Assessment::SCHEDULING_BATCH_WISE,
        ]);

        // Extra library assessments for pagination/browsing (no questions needed).
        Assessment::factory()
            ->library()
            ->count(3)
            ->create([
                'admin_id' => $admin3->id,
                'assessment_type_id' => $mcq->id,
                'scheduling_type' => Assessment::SCHEDULING_FIXED,
            ]);
    }
}
