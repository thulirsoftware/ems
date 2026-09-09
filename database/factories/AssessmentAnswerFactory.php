<?php

namespace Database\Factories;

use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssessmentAnswer>
 */
class AssessmentAnswerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'attempt_id' => AssessmentAttempt::factory(),
            'question_id' => AssessmentQuestion::factory(),
            'answer' => ['text' => fake()->sentence()],
            'is_correct' => null,
        ];
    }

    public function choice(int $choiceId, bool $isCorrect): static
    {
        return $this->state([
            'answer' => ['choice_id' => $choiceId],
            'is_correct' => $isCorrect,
        ]);
    }
}
