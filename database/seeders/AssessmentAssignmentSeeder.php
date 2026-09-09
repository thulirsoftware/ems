<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\Batch;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssessmentAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereIn('email', [
            'user1@gmail.com',
            'user2@gmail.com',
            'user3@gmail.com',
            'user4@gmail.com',
            'user5@gmail.com',
        ])->get()->keyBy('email');

        // Basic Math Test: single batch, every fixed test user assigned.
        $mathBatch = $this->soleBatch('Basic Math Test');
        foreach (['user1@gmail.com', 'user2@gmail.com', 'user3@gmail.com', 'user4@gmail.com', 'user5@gmail.com'] as $email) {
            $this->assign($mathBatch, $users[$email]);
        }

        // Advanced Algebra Quiz: two batches, split between users.
        $algebraMorning = $this->batchNamed('Advanced Algebra Quiz', 'Morning Batch');
        $algebraEvening = $this->batchNamed('Advanced Algebra Quiz', 'Evening Batch');
        $this->assign($algebraMorning, $users['user1@gmail.com']);
        $this->assign($algebraMorning, $users['user2@gmail.com']);
        $this->assign($algebraEvening, $users['user3@gmail.com']);
        $this->assign($algebraEvening, $users['user4@gmail.com']);

        // General Knowledge Descriptive: single batch.
        $gkBatch = $this->soleBatch('General Knowledge Descriptive');
        $this->assign($gkBatch, $users['user1@gmail.com']);
        $this->assign($gkBatch, $users['user3@gmail.com']);
        $this->assign($gkBatch, $users['user5@gmail.com']);

        // Flexible Practice Test: single batch, take anytime before expiry.
        $flexBatch = $this->soleBatch('Flexible Practice Test');
        $this->assign($flexBatch, $users['user2@gmail.com']);
        $this->assign($flexBatch, $users['user4@gmail.com']);

        // Coding Challenge - Arrays is still a draft (is_active = false):
        // intentionally no assignments yet.

        // Startup Case Study: only the morning batch has been filled so far.
        $caseMorning = $this->batchNamed('Startup Case Study', 'Morning Batch');
        $this->assign($caseMorning, $users['user1@gmail.com']);
        $this->assign($caseMorning, $users['user2@gmail.com']);
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

    private function assign(Batch $batch, User $user): void
    {
        AssessmentAssignment::create([
            'assessment_id' => $batch->assessment_id,
            'user_id' => $user->id,
            'batch_id' => $batch->id,
        ]);
    }
}
