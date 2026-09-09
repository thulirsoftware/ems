<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Batch;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssessmentAttemptSeeder extends Seeder
{
    public function run(): void
    {
        // Basic Math Test: one completed, one still in progress.
        $mathBatch = $this->soleBatch('Basic Math Test');
        $this->completed($mathBatch, 'user1@gmail.com', '4/5');
        $this->inProgress($mathBatch, 'user2@gmail.com');

        // Advanced Algebra Quiz: negative marking brings the score down.
        $algebraMorning = $this->batchNamed('Advanced Algebra Quiz', 'Morning Batch');
        $this->completed($algebraMorning, 'user1@gmail.com', '2.5/5');
        $this->inProgress($algebraMorning, 'user2@gmail.com');
        // Evening batch runs tomorrow: nobody has attempted it yet.

        // General Knowledge Descriptive: manual grading, so submitted
        // attempts sit at "Pending Evaluation" until an admin grades them.
        $gkBatch = $this->soleBatch('General Knowledge Descriptive');
        $this->completed($gkBatch, 'user1@gmail.com', 'Pending Evaluation');
        $this->inProgress($gkBatch, 'user3@gmail.com');

        // Flexible Practice Test: available anytime, so a full attempt fits here.
        $flexBatch = $this->soleBatch('Flexible Practice Test');
        $this->completed($flexBatch, 'user2@gmail.com', '5/5');
        $this->inProgress($flexBatch, 'user4@gmail.com');

        // Startup Case Study: manual grading again.
        $caseMorning = $this->batchNamed('Startup Case Study', 'Morning Batch');
        $this->completed($caseMorning, 'user1@gmail.com', 'Pending Evaluation');
    }

    private function completed(Batch $batch, string $email, string $score): AssessmentAttempt
    {
        $user = User::where('email', $email)->firstOrFail();

        return AssessmentAttempt::create([
            'assessment_id' => $batch->assessment_id,
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'started_at' => app_now()->subMinutes(20)->toTimeString(),
            'submitted_at' => app_now()->toTimeString(),
            'score' => $score,
            'question_order' => $this->questionOrder($batch->assessment_id),
        ]);
    }

    private function inProgress(Batch $batch, string $email): AssessmentAttempt
    {
        $user = User::where('email', $email)->firstOrFail();

        return AssessmentAttempt::create([
            'assessment_id' => $batch->assessment_id,
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'started_at' => app_now()->subMinutes(5)->toTimeString(),
            'question_order' => $this->questionOrder($batch->assessment_id),
        ]);
    }

    private function questionOrder(int $assessmentId): array
    {
        return Assessment::find($assessmentId)
            ->questions()
            ->orderBy('order')
            ->pluck('id')
            ->all();
    }

    private function soleBatch(string $assessmentTitle): Batch
    {
        $assessment = Assessment::where('title', $assessmentTitle)->firstOrFail();

        return Batch::where('assessment_id', $assessment->id)->firstOrFail();
    }

    private function batchNamed(string $assessmentTitle, string $suffix): Batch
    {
        $assessment = Assessment::where('title', $assessmentTitle)->firstOrFail();

        return Batch::where('assessment_id', $assessment->id)
            ->where('name', $assessmentTitle . ' - ' . $suffix)
            ->firstOrFail();
    }
}
