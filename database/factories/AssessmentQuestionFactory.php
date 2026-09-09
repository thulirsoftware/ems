<?php

namespace Database\Factories;

use App\Models\Assessment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssessmentQuestion>
 */
class AssessmentQuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'type' => 'mcq',
            'question_text' => fake()->sentence() . '?',
            'config' => null,
            'order' => 1,
        ];
    }

    public function descriptive(): static
    {
        return $this->state([
            'type' => 'descriptive',
        ]);
    }
}
