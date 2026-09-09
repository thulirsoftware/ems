<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssessmentAttempt>
 */
class AssessmentAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'user_id' => User::factory(),
            'batch_id' => null,
            'started_at' => app_now()->toTimeString(),
            'submitted_at' => null,
            'score' => null,
            'question_order' => null,
        ];
    }

    public function submitted(string $score): static
    {
        return $this->state([
            'submitted_at' => app_now()->toTimeString(),
            'score' => $score,
        ]);
    }

    public function pendingEvaluation(): static
    {
        return $this->submitted('Pending Evaluation');
    }
}
